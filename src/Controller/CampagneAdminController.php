<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/campagnes', name: 'admin_campagnes_')]
class CampagneAdminController extends AbstractController
{
    private function verifierAcces(): void
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Connection $db, Request $request): Response
    {
        $this->verifierAcces();

        // Recherche et filtres
        $recherche = trim((string) $request->query->get('q', ''));
        $statut = trim((string) $request->query->get('statut', ''));

        if (!in_array($statut, ['', 'active', 'inactive'], true)) {
            $statut = '';
        }

        // Pagination
        $limite = 10;
        $page = max(1, $request->query->getInt('page', 1));

        $conditions = [];
        $parametres = [];

        if ($recherche !== '') {
            $conditions[] = 'LOWER(ca.nom) LIKE :recherche';
            $parametres['recherche'] = '%' . mb_strtolower($recherche) . '%';
        }

        if ($statut === 'active') {
            $conditions[] = 'ca.active = 1';
        } elseif ($statut === 'inactive') {
            $conditions[] = 'ca.active = 0';
        }

        $where = $conditions !== []
            ? ' WHERE ' . implode(' AND ', $conditions)
            : '';

        // Statistiques générales
        $statistiques = $db->fetchAssociative(
            "SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END), 0) AS actives,
                COALESCE(SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END), 0) AS inactives
             FROM campagne"
        );

        // Nombre de campagnes correspondant aux filtres
        $total = (int) $db->fetchOne(
            'SELECT COUNT(*) FROM campagne ca' . $where,
            $parametres
        );

        $nombrePages = max(1, (int) ceil($total / $limite));

        if ($page > $nombrePages) {
            $page = $nombrePages;
        }

        $offset = ($page - 1) * $limite;

        // Agrégation des candidats et des quotas en une seule lecture
        $sql = "
            SELECT
                ca.*,
                COALESCE(c.nombre_candidats, 0) AS nombre_candidats,
                COALESCE(q.total_quotas, 0) AS total_quotas
            FROM campagne ca
            LEFT JOIN (
                SELECT campagne_id, COUNT(*) AS nombre_candidats
                FROM candidat
                GROUP BY campagne_id
            ) c ON c.campagne_id = ca.id
            LEFT JOIN (
                SELECT campagne_id, SUM(quota) AS total_quotas
                FROM quota_metier
                GROUP BY campagne_id
            ) q ON q.campagne_id = ca.id
            " . $where . "
            ORDER BY ca.active DESC, ca.dateDebut DESC, ca.id DESC
            LIMIT " . (int) $limite . "
            OFFSET " . (int) $offset;

        $campagnes = $db->fetchAllAssociative($sql, $parametres);

        return $this->render('admin/campagnes/index.html.twig', [
            'campagnes' => $campagnes,
            'recherche' => $recherche,
            'statutFiltre' => $statut,
            'statistiques' => $statistiques,
            'page' => $page,
            'nombrePages' => $nombrePages,
            'total' => $total,
            'limite' => $limite,
        ]);
    }
    #[Route('/nouvelle', name: 'new', methods: ['GET', 'POST'])]
    public function nouvelle(Request $request, Connection $db): Response
    {
        $this->verifierAcces();

        return $this->formulaire(null, $request, $db);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifier(int $id, Request $request, Connection $db): Response
    {
        $this->verifierAcces();

        $campagne = $db->fetchAssociative(
            'SELECT * FROM campagne WHERE id = :id',
            ['id' => $id]
        );

        if (!$campagne) {
            throw $this->createNotFoundException('Campagne introuvable.');
        }

        return $this->formulaire($campagne, $request, $db);
    }

    private function formulaire(
        ?array $campagne,
        Request $request,
        Connection $db
    ): Response {
        $edition = $campagne !== null;
        $id = $edition ? (int) $campagne['id'] : null;

        $campagne ??= [
            'nom' => '',
            'dateDebut' => '',
            'dateFin' => '',
            'ageMin' => 12,
            'ageMax' => 35,
            'quotaGlobal' => '',
            'NiveauRequis' => '',
            'texteCampagne' => '',
            'couleurAccueil' => '#5B9BD5',
            'DateCandidatures' => '',
            'DateEtudeDossiers' => '',
            'DateSelection' => '',
            'DateDebutFormations' => '',
        ];

        $campagne['quotaGlobal'] = $edition
            ? (int) $db->fetchOne(
                'SELECT COALESCE(SUM(quota), 0) FROM quota_metier WHERE campagne_id = :id',
                ['id' => $id]
            )
            : 0;

        if ($request->isMethod('POST')) {
            $campagne['nom'] = trim((string) $request->request->get('nom', ''));
            $campagne['dateDebut'] = trim((string) $request->request->get('dateDebut', ''));
            $campagne['dateFin'] = trim((string) $request->request->get('dateFin', ''));
            $campagne['ageMin'] = trim((string) $request->request->get('ageMin', ''));
            $campagne['ageMax'] = trim((string) $request->request->get('ageMax', ''));

            $campagne['NiveauRequis'] = trim((string) $request->request->get('NiveauRequis', ''));
            $campagne['texteCampagne'] = trim((string) $request->request->get('texteCampagne', ''));
            $campagne['couleurAccueil'] = trim((string) $request->request->get('couleurAccueil', '#5B9BD5'));
            $campagne['DateCandidatures'] = trim((string) $request->request->get('DateCandidatures', ''));
            $campagne['DateEtudeDossiers'] = trim((string) $request->request->get('DateEtudeDossiers', ''));
            $campagne['DateSelection'] = trim((string) $request->request->get('DateSelection', ''));
            $campagne['DateDebutFormations'] = trim((string) $request->request->get('DateDebutFormations', ''));

            $tokenValide = $this->isCsrfTokenValid(
                'campagne_form',
                (string) $request->request->get('_token', '')
            );

            $debut = \DateTimeImmutable::createFromFormat('!Y-m-d', $campagne['dateDebut']);
            $fin = \DateTimeImmutable::createFromFormat('!Y-m-d', $campagne['dateFin']);

            $ageMinValide = filter_var($campagne['ageMin'], FILTER_VALIDATE_INT);
            $ageMaxValide = filter_var($campagne['ageMax'], FILTER_VALIDATE_INT);

            $couleurValide = preg_match('/^#[0-9A-Fa-f]{6}$/', $campagne['couleurAccueil']) === 1;


            if (!$tokenValide) {
                $this->addFlash('error', 'Jeton de sécurité invalide.');
            } elseif ($campagne['nom'] === '' || mb_strlen($campagne['nom']) > 160) {
                $this->addFlash('error', 'Le nom est obligatoire (160 caractères maximum).');
            } elseif (!$debut || !$fin || $debut->format('Y-m-d') !== $campagne['dateDebut']
                || $fin->format('Y-m-d') !== $campagne['dateFin']) {
                $this->addFlash('error', 'Veuillez renseigner des dates valides.');
            } elseif ($fin < $debut) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
            } elseif ($ageMinValide === false || $ageMaxValide === false
                || $ageMinValide < 1 || $ageMaxValide < $ageMinValide) {
                $this->addFlash('error', 'La tranche d’âge renseignée est invalide.');
            } elseif (!$couleurValide) {
            } elseif (!$couleurValide) {
                $this->addFlash('error', 'Couleur invalide. Exemple attendu : #5B9BD5.');
            } elseif (mb_strlen($campagne['NiveauRequis']) > 60000) {
                $this->addFlash('error', 'Le niveau scolaire requis est trop long.');
            } elseif (mb_strlen($campagne['texteCampagne']) > 60000) {
                $this->addFlash('error', 'Le texte de campagne est trop long.');
            } elseif (
                mb_strlen($campagne['DateCandidatures']) > 60000 ||
                mb_strlen($campagne['DateEtudeDossiers']) > 60000 ||
                mb_strlen($campagne['DateSelection']) > 60000 ||
                mb_strlen($campagne['DateDebutFormations']) > 60000
            ) {
                $this->addFlash('error', 'Une des périodes du chronogramme est trop longue.');
            } else {
                $donnees = [
                    'nom' => $campagne['nom'],
                    'dateDebut' => $campagne['dateDebut'],
                    'dateFin' => $campagne['dateFin'],
                    'ageMin' => (int) $ageMinValide,
                    'ageMax' => (int) $ageMaxValide,
                    'quotaGlobal' => (int) $campagne['quotaGlobal'],
                    'NiveauRequis' => $campagne['NiveauRequis'] !== '' ? $campagne['NiveauRequis'] : null,
                    'texteCampagne' => $campagne['texteCampagne'] !== '' ? $campagne['texteCampagne'] : null,
                    'couleurAccueil' => $campagne['couleurAccueil'],
                    'DateCandidatures' => $campagne['DateCandidatures'] !== '' ? $campagne['DateCandidatures'] : null,
                    'DateEtudeDossiers' => $campagne['DateEtudeDossiers'] !== '' ? $campagne['DateEtudeDossiers'] : null,
                    'DateSelection' => $campagne['DateSelection'] !== '' ? $campagne['DateSelection'] : null,
                    'DateDebutFormations' => $campagne['DateDebutFormations'] !== '' ? $campagne['DateDebutFormations'] : null,
                ];

                if ($edition) {
                    $db->update('campagne', $donnees, ['id' => $id]);
                    $this->addFlash('success', 'Campagne modifiée. Son statut d’activation a été conservé.');
                } else {
                    // Une campagne nouvellement créée reste inactive.
                    $db->insert('campagne', $donnees + ['active' => 0]);
                    $this->addFlash('success', 'Nouvelle campagne créée. Activez-la lorsque vous serez prêt.');
                }

                return $this->redirectToRoute('admin_campagnes_index');
            }
        }

        if ($edition) {
            foreach (['dateDebut', 'dateFin'] as $champ) {
                if ($campagne[$champ] instanceof \DateTimeInterface) {
                    $campagne[$champ] = $campagne[$champ]->format('Y-m-d');
                } elseif ($campagne[$champ] !== '') {
                    $campagne[$champ] = substr((string) $campagne[$champ], 0, 10);
                }
            }
        }

        return $this->render('admin/campagnes/form.html.twig', [
            'campagne' => $campagne,
            'edition' => $edition,
        ]);
    }

    #[Route('/{id}/activer', name: 'activate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function activer(int $id, Request $request, Connection $db): Response
    {
        $this->verifierAcces();

        if (!$this->isCsrfTokenValid(
            'activer_campagne_' . $id,
            (string) $request->request->get('_token', '')
        )) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('admin_campagnes_index');
        }

        $campagne = $db->fetchAssociative(
            'SELECT id, nom FROM campagne WHERE id = :id',
            ['id' => $id]
        );

        if (!$campagne) {
            throw $this->createNotFoundException('Campagne introuvable.');
        }

        $db->transactional(function (Connection $db) use ($id): void {
            $db->executeStatement('UPDATE campagne SET active = 0');
            $db->update('campagne', ['active' => 1], ['id' => $id]);
        });

        $this->addFlash('success', 'La campagne « ' . $campagne['nom'] . ' » est maintenant active.');
        return $this->redirectToRoute('admin_campagnes_index');
    }

    #[Route('/{id}/cloturer', name: 'close', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cloturer(int $id, Request $request, Connection $db): Response
    {
        $this->verifierAcces();

        if (!$this->isCsrfTokenValid(
            'cloturer_campagne_' . $id,
            (string) $request->request->get('_token', '')
        )) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('admin_campagnes_index');
        }

        $nombre = $db->executeStatement(
            'UPDATE campagne SET active = 0 WHERE id = :id',
            ['id' => $id]
        );

        $this->addFlash(
            $nombre ? 'success' : 'error',
            $nombre
                ? 'Campagne clôturée. Son historique et ses candidatures sont conservés.'
                : 'Campagne introuvable.'
        );

        return $this->redirectToRoute('admin_campagnes_index');
    }

    #[Route('/{id}/quotas', name: 'quotas', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function quotas(int $id, Request $request, Connection $db): Response
    {
        $this->verifierAcces();

        $campagne = $db->fetchAssociative(
            'SELECT * FROM campagne WHERE id = :id',
            ['id' => $id]
        );

        if (!$campagne) {
            throw $this->createNotFoundException('Campagne introuvable.');
        }

        $campagne['quotaGlobal'] = (int) $db->fetchOne(
            'SELECT COALESCE(SUM(quota), 0) FROM quota_metier WHERE campagne_id = :id',
            ['id' => $id]
        );

        $metiers = $db->fetchAllAssociative(
            'SELECT m.id, m.nom, m.secteur, m.actif,
                    COALESCE(q.quota, 0) AS quota
             FROM metier_catalogue m
             LEFT JOIN quota_metier q
                ON q.metier_id = m.id AND q.campagne_id = :campagne
             ORDER BY m.actif DESC, m.nom ASC',
            ['campagne' => $id]
        );

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'quotas_campagne_' . $id,
                (string) $request->request->get('_token', '')
            )) {
                $this->addFlash('error', 'Jeton de sécurité invalide.');
                return $this->redirectToRoute('admin_campagnes_quotas', ['id' => $id]);
            }

            $valeurs = $request->request->all('quotas');
            $total = 0;
            $aEnregistrer = [];

            foreach ($metiers as $metier) {
                $metierId = (int) $metier['id'];
                $valeur = (string) ($valeurs[$metierId] ?? '0');

                if (!preg_match('/^\d+$/', $valeur)) {
                    $this->addFlash('error', 'Chaque quota doit être un nombre entier positif ou nul.');
                    return $this->redirectToRoute('admin_campagnes_quotas', ['id' => $id]);
                }

                $quota = (int) $valeur;
                $total += $quota;
                $aEnregistrer[] = ['metier_id' => $metierId, 'quota' => $quota];
            }


            $db->transactional(function (Connection $db) use ($id, $aEnregistrer, $total): void {
                foreach ($aEnregistrer as $ligne) {
                    $existant = $db->fetchOne(
                        'SELECT id FROM quota_metier WHERE campagne_id = :campagne AND metier_id = :metier',
                        ['campagne' => $id, 'metier' => $ligne['metier_id']]
                    );

                    if ($existant) {
                        $db->update('quota_metier', ['quota' => $ligne['quota']], ['id' => $existant]);
                    } else {
                        $db->insert('quota_metier', [
                            'campagne_id' => $id,
                            'metier_id' => $ligne['metier_id'],
                            'quota' => $ligne['quota'],
                        ]);
                    }
                }

                $db->update('campagne', ['quotaGlobal' => $total], ['id' => $id]);
            });

            $campagne['quotaGlobal'] = $total;

            $this->addFlash('success', 'Les quotas par métier ont été enregistrés.');
            return $this->redirectToRoute('admin_campagnes_quotas', ['id' => $id]);
        }

        return $this->render('admin/campagnes/quotas.html.twig', [
            'campagne' => $campagne,
            'metiers' => $metiers,
        ]);
    }
}