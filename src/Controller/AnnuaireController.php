<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Admission;
use App\Entity\AffectationResponsableApprenti;
use App\Entity\User;
use App\Entity\Campagne;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AnnuaireController extends AbstractController
{
    #[Route('/annuaire', name: 'annuaire', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $campagne = $em->getRepository(Campagne::class)->findOneBy(
            ['active' => true],
            ['dateDebut' => 'DESC']
        );

        $recherche = trim((string) $request->query->get('q', ''));
        $metier = trim((string) $request->query->get('metier', ''));
        $dateDebut = trim((string) $request->query->get('dateDebut', ''));
        $dateFin = trim((string) $request->query->get('dateFin', ''));

        $triAutorise = [
            'numero' => 'c.numero',
            'nom' => 'c.nom',
            'prenoms' => 'c.prenoms',
            'metier' => 'a.metierAffecte',
            'date' => 'a.dateAdmission',
        ];

        $tri = (string) $request->query->get('tri', 'date');
        $ordre = strtoupper((string) $request->query->get('ordre', 'DESC'));

        if (!isset($triAutorise[$tri])) {
            $tri = 'date';
        }

        if (!in_array($ordre, ['ASC', 'DESC'], true)) {
            $ordre = 'DESC';
        }

        $items = [];
        $metiers = [];
        $admissionsParMetier = [];
        $totalCampagne = 0;
        $totalFiltres = 0;
        $nombreMetiers = 0;

        if ($campagne instanceof Campagne) {
            $baseQb = $em->getRepository(Admission::class)
                ->createQueryBuilder('a')
                ->innerJoin('a.candidat', 'c')
                ->andWhere('a.decision = :decision')
                ->andWhere('c.campagne = :campagne')
                ->setParameter('decision', 'ADMIS')
                ->setParameter('campagne', $campagne);

            $totalCampagne = (int) (clone $baseQb)
                ->select('COUNT(a.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $metiers = $em->getRepository(Admission::class)
                ->createQueryBuilder('a')
                ->select('DISTINCT a.metierAffecte')
                ->innerJoin('a.candidat', 'c')
                ->andWhere('a.decision = :decision')
                ->andWhere('c.campagne = :campagne')
                ->andWhere('a.metierAffecte IS NOT NULL')
                ->andWhere("a.metierAffecte <> ''")
                ->setParameter('decision', 'ADMIS')
                ->setParameter('campagne', $campagne)
                ->orderBy('a.metierAffecte', 'ASC')
                ->getQuery()
                ->getSingleColumnResult();

            $qb = clone $baseQb;

            if ($recherche !== '') {
                $qb->andWhere(
                    'LOWER(a.codeAdmission) LIKE :recherche
                    OR LOWER(c.numero) LIKE :recherche
                    OR LOWER(c.nom) LIKE :recherche
                    OR LOWER(c.prenoms) LIKE :recherche'
                )->setParameter(
                    'recherche',
                    '%' . (function_exists('mb_strtolower') ? mb_strtolower($recherche, 'UTF-8') : strtolower($recherche)) . '%'
                );
            }

            if ($metier !== '') {
                $qb->andWhere('a.metierAffecte = :metier')
                    ->setParameter('metier', $metier);
            }

            $debut = $this->dateValide($dateDebut);
            if ($debut !== null) {
                $qb->andWhere('a.dateAdmission >= :dateDebut')
                    ->setParameter('dateDebut', $debut);
            }

            $fin = $this->dateValide($dateFin);
            if ($fin !== null) {
                $finExclusive = $fin->modify('+1 day');
                $qb->andWhere('a.dateAdmission < :dateFin')
                    ->setParameter('dateFin', $finExclusive);
            }

            $items = $qb
                ->addSelect('c')
                ->orderBy($triAutorise[$tri], $ordre)
                ->addOrderBy('c.nom', 'ASC')
                ->getQuery()
                ->getResult();

            $totalFiltres = count($items);

            foreach ($items as $admission) {
                $nomMetier = $admission->getMetierAffecte() ?: 'Non renseigné';

                $admissionsParMetier[$nomMetier] =
                    ($admissionsParMetier[$nomMetier] ?? 0) + 1;
            }

            ksort($admissionsParMetier, SORT_NATURAL | SORT_FLAG_CASE);
            $nombreMetiers = count($admissionsParMetier);
        }

        // Dossiers individuels accessibles au responsable connecté.
        // Le Gestionnaire conserve son accès global.
        $utilisateur = $this->getUser();
        $estGestionnaire = $this->isGranted('ROLE_GESTIONNAIRE');
        $dossiersAccessibles = [];

        if (!$estGestionnaire && $utilisateur instanceof User) {
            $typesAutorises = [];

            if (
                $this->isGranted('ROLE_REFERENT')
                || $this->isGranted('ROLE_REFERENT_PARCOURS')
            ) {
                $typesAutorises[] =
                    AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS;
            }

            if ($this->isGranted('ROLE_FORMATEUR')) {
                $typesAutorises[] =
                    AffectationResponsableApprenti::TYPE_FORMATEUR;
            }

            if ($typesAutorises !== []) {
                $affectations = $em
                    ->getRepository(AffectationResponsableApprenti::class)
                    ->findBy([
                        'responsable' => $utilisateur,
                        'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                        'dateFin' => null,
                    ]);

                foreach ($affectations as $affectation) {
                    if (
                        !$affectation instanceof AffectationResponsableApprenti
                        || !in_array(
                            $affectation->getTypeResponsabilite(),
                            $typesAutorises,
                            true
                        )
                    ) {
                        continue;
                    }

                    $candidatAffecte = $affectation->getCandidat();

                    if ($candidatAffecte !== null) {
                        $dossiersAccessibles[] = $candidatAffecte->getId();
                    }
                }

                $dossiersAccessibles = array_values(
                    array_unique($dossiersAccessibles)
                );
            }
        }

        return $this->render('annuaire/index.html.twig', [
            'items' => $items,
            'campagne' => $campagne,
            'recherche' => $recherche,
            'metier' => $metier,
            'metiers' => $metiers,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'tri' => $tri,
            'ordre' => $ordre,
            'totalCampagne' => $totalCampagne,
            'totalFiltres' => $totalFiltres,
            'nombreMetiers' => $nombreMetiers,
            'admissionsParMetier' => $admissionsParMetier,
            'estGestionnaire' => $estGestionnaire,
            'dossiersAccessibles' => $dossiersAccessibles,
        ]);
    }

    private function dateValide(string $valeur): ?\DateTimeImmutable
    {
        if ($valeur === '') {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);
        $erreurs = \DateTimeImmutable::getLastErrors();

        if (
            $date === false
            || ($erreurs !== false && (
                $erreurs['warning_count'] > 0
                || $erreurs['error_count'] > 0
            ))
            || $date->format('Y-m-d') !== $valeur
        ) {
            return null;
        }

        return $date;
    }
}