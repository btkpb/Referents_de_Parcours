<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Campagne;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DaipHomeController extends AbstractController
{
    #[Route('/accueil', name: 'daip_accueil', methods: ['GET'])]
    public function index(
        EntityManagerInterface $em,
        Connection $connection
    ): Response {
        $campagne = $em->getRepository(Campagne::class)
            ->findOneBy(['active' => true]);
        $quota = $campagne
            ? (int) $connection->fetchOne(
                'SELECT COALESCE(SUM(quota), 0)
                 FROM quota_metier
                 WHERE campagne_id = :campagne',
                ['campagne' => $campagne->getId()]
            )
            : 0;
        $metiers = $connection->fetchAllAssociative(
            'SELECT id, nom, secteur, description
             FROM metier_catalogue
             WHERE actif = 1
             ORDER BY nom ASC'
        );

        $secteurs = $connection->fetchAllAssociative(
            'SELECT MIN(id) AS id, secteur AS nom
             FROM metier_catalogue
             WHERE actif = 1
             GROUP BY secteur
             ORDER BY secteur ASC'
        );

        $totalMetiers = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM metier_catalogue WHERE actif = 1'
        );

        $totalSecteurs = (int) $connection->fetchOne(
            'SELECT COUNT(DISTINCT secteur)
             FROM metier_catalogue
             WHERE actif = 1'
        );

        return $this->render('home/index.html.twig', [
            'stats' => [
                'metiers' => $totalMetiers,
                'secteurs' => $totalSecteurs,
                'etablissements' => (int) $connection->fetchOne('SELECT COUNT(*) FROM etablissement_catalogue WHERE actif = 1'),
                'places' => $quota,
                'candidatures' => $em->getRepository(Candidat::class)->count([]),
            ],
            'campagne' => $campagne,
            'directions' => [],
            'etablissements' => $connection->fetchAllAssociative('SELECT id, nom, ville, description FROM etablissement_catalogue WHERE actif = 1 ORDER BY nom ASC LIMIT 6'),
            'rechercheEtablissement' => '',
            'directionSelectionnee' => '',
            'pageEtablissementActuelle' => 1,
            'totalEtablissements' => 0,
            'metiers' => $metiers,
            'secteurs' => $secteurs,
            'rechercheMetier' => '',
            'secteurSelectionne' => '',
            'pageMetierActuelle' => 1,
            'totalMetiers' => $totalMetiers,
            'partenaires' => [],
        ]);
    }
}