<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AffectationResponsableApprenti;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MaitreStageController extends AbstractController
{
    private const ELEMENTS_PAR_PAGE = 20;

    #[Route('/espace-maitre-stage', name: 'maitre_stage_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User || !$utilisateur->isActive()) {
            throw $this->createAccessDeniedException(
                'Compte utilisateur inactif ou non connecté.'
            );
        }

        $roles = $utilisateur->getRoles();
        $utilisateurId = $utilisateur->getId();
        $utilisateurNomComplet = trim($utilisateur->getNom() . ' ' . $utilisateur->getPrenoms());

        if ($utilisateurId === null) {
            throw $this->createAccessDeniedException(
                'Identifiant utilisateur introuvable.'
            );
        }

        $rolesSupervision = [
            'ROLE_GESTIONNAIRE',
            'ROLE_ADMIN',
            'ROLE_ADMINISTRATEUR',
            'ROLE_SUPER_ADMIN',
        ];

        $estSuperviseur = count(array_intersect(
            $rolesSupervision,
            $roles
        )) > 0;

        if ($estSuperviseur) {
            $typeResponsabilite = 'MAITRE_STAGE';
            $responsableId = null;
            $profilEspace = 'Supervision';

        } elseif (in_array('ROLE_MAITRE_STAGE', $roles, true)) {
            $typeResponsabilite = 'MAITRE_STAGE';
            $responsableId = $utilisateurId;
            $profilEspace = 'Maître de stage';

        } elseif (
            in_array('ROLE_REFERENT_PARCOURS', $roles, true)
            || in_array('ROLE_REFERENT', $roles, true)
        ) {
            $typeResponsabilite = 'REFERENT_PARCOURS';
            $responsableId = $utilisateurId;
            $profilEspace = 'Référent de parcours';

        } else {
            throw $this->createAccessDeniedException(
                'Votre profil ne permet pas cet accès.'
            );
        }

        // Connexion directe à la base déjà utilisée par Symfony.
        $connexion = $entityManager->getConnection();

        $conditions = [
            'a.type_responsabilite = :typeResponsabilite',
        ];

        $parametres = [
            'typeResponsabilite' => $typeResponsabilite,
        ];

        // Limitation stricte aux affectations de l'utilisateur connecté.
        if ($responsableId !== null) {
            $conditions[] = 'a.responsable_id = :responsableId';
            $parametres['responsableId'] = $responsableId;
        }

        // Recherche des apprentis.
        $recherche = trim((string) $request->query->get('q', ''));
        $recherche = substr($recherche, 0, 80);

        if ($recherche !== '') {
            $conditions[] = "(
                LOWER(COALESCE(c.numero, '')) LIKE :recherche
                OR LOWER(COALESCE(c.nom, '')) LIKE :recherche
                OR LOWER(COALESCE(c.prenoms, '')) LIKE :recherche
                OR LOWER(COALESCE(c.metier, '')) LIKE :recherche
            )";

            $parametres['recherche'] =
                '%' . strtolower($recherche) . '%';
        }

        $whereBase = implode(' AND ', $conditions);

        // Nombre total d'affectations.
        $sqlTotal = "
            SELECT COUNT(a.id)
            FROM affectation_responsable_apprenti a
            INNER JOIN candidat c ON c.id = a.candidat_id
            WHERE $whereBase
        ";

        $totalAffectations = (int) $connexion->fetchOne(
            $sqlTotal,
            $parametres
        );

        // Nombre d'affectations effectivement actives.
        $sqlActives = "
            SELECT COUNT(a.id)
            FROM affectation_responsable_apprenti a
            INNER JOIN candidat c ON c.id = a.candidat_id
            WHERE $whereBase
              AND a.statut = :statutActif
              AND a.dateFin IS NULL
        ";

        $parametresActifs = $parametres;
        $parametresActifs['statutActif'] = 'ACTIVE';

        $affectationsActives = (int) $connexion->fetchOne(
            $sqlActives,
            $parametresActifs
        );

        $affectationsHistoriques = max(
            0,
            $totalAffectations - $affectationsActives
        );

        // Filtre par situation.
        $etat = (string) $request->query->get('etat', 'tous');

        if (!in_array($etat, ['tous', 'actives', 'historique'], true)) {
            $etat = 'tous';
        }

        $conditionsListe = $conditions;
        $parametresListe = $parametres;

        if ($etat === 'actives') {
            $conditionsListe[] = 'a.statut = :statutActif';
            $conditionsListe[] = 'a.dateFin IS NULL';
            $parametresListe['statutActif'] = 'ACTIVE';

        } elseif ($etat === 'historique') {
            $conditionsListe[] =
                '(a.statut <> :statutActif OR a.dateFin IS NOT NULL)';
            $parametresListe['statutActif'] = 'ACTIVE';
        }

        $whereListe = implode(' AND ', $conditionsListe);

        $sqlCompte = "
            SELECT COUNT(a.id)
            FROM affectation_responsable_apprenti a
            INNER JOIN candidat c ON c.id = a.candidat_id
            WHERE $whereListe
        ";

        $totalResultats = (int) $connexion->fetchOne(
            $sqlCompte,
            $parametresListe
        );

        // Pagination.
        $parPage = self::ELEMENTS_PAR_PAGE;
        $totalPages = max(1, (int) ceil($totalResultats / $parPage));

        $page = max(1, $request->query->getInt('page', 1));
        $page = min($page, $totalPages);

        $offset = ($page - 1) * $parPage;

        // Lecture directe des affectations et de leurs candidats.
        $sqlListe = "
            SELECT
                a.id AS affectation_id,
                a.dateDebut AS affectation_date_debut,
                a.dateFin AS affectation_date_fin,
                a.statut AS affectation_statut,
                a.responsable_id AS responsable_id,
                c.id AS candidat_id,
                c.numero AS candidat_numero,
                c.nom AS candidat_nom,
                c.prenoms AS candidat_prenoms,
                c.metier AS candidat_metier
            FROM affectation_responsable_apprenti a
            INNER JOIN candidat c ON c.id = a.candidat_id
            WHERE $whereListe
            ORDER BY a.dateDebut DESC, a.id DESC
            LIMIT $parPage OFFSET $offset
        ";

        $resultats = $connexion->fetchAllAssociative(
            $sqlListe,
            $parametresListe
        );

        $lignes = [];

        foreach ($resultats as $row) {
            $active = (
                $row['affectation_statut'] === 'ACTIVE'
                && $row['affectation_date_fin'] === null
            );

            $lignes[] = [
                'affectation' => [
                    'id' => $row['affectation_id'],
                    'dateDebut' => $row['affectation_date_debut'],
                    'dateFin' => $row['affectation_date_fin'],
                    'statut' => $row['affectation_statut'],
                ],
                'candidat' => [
                    'id' => $row['candidat_id'],
                    'numero' => $row['candidat_numero'],
                    'nom' => $row['candidat_nom'],
                    'prenoms' => $row['candidat_prenoms'],
                    'metier' => $row['candidat_metier'],
                ],
                'active' => $active,
            ];
        }

        // Conservation des deux présentations.
        $affichage = strtolower(trim(
            (string) $request->query->get('affichage', 'moderne')
        ));

        if (!in_array($affichage, ['moderne', 'classique'], true)) {
            $affichage = 'moderne';
        }

        $template = $affichage === 'classique'
            ? 'maitre_stage/index_classique.html.twig'
            : 'maitre_stage/index.html.twig';

        return $this->render($template, [
            'lignes' => $lignes,
            'profilEspace' => $profilEspace,
            'utilisateurId' => $utilisateurId,
            'utilisateurNomComplet' => $utilisateurNomComplet,
            'responsableFiltreId' => $responsableId,
            'totalAffectations' => $totalAffectations,
            'affectationsActives' => $affectationsActives,
            'affectationsHistoriques' => $affectationsHistoriques,
            'totalResultats' => $totalResultats,
            'pageCourante' => $page,
            'totalPages' => $totalPages,
            'recherche' => $recherche,
            'etat' => $etat,
            'parPage' => $parPage,
        ]);
    }
}