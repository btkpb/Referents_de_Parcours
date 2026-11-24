<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CatalogueController extends AbstractController
{
    #[Route('/etablissements', name: 'app_etablissements_list', methods: ['GET'])]
    public function etablissements(): RedirectResponse
    {
        return $this->redirect($this->generateUrl('daip_accueil') . '#etablissements');
    }

    #[Route('/metiers', name: 'app_metiers_list', methods: ['GET'])]
    public function metiers(): RedirectResponse
    {
        return $this->redirect($this->generateUrl('daip_accueil') . '#metiers');
    }

    #[Route('/load-etablissements', name: 'load_etablissements', methods: ['GET'])]
    public function chargerEtablissements(Request $request, Connection $connection): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $parPage = 6;
        $recherche = trim((string) ($request->query->get('q')
            ?? $request->query->get('recherche', '')));

        $conditions = ['actif = 1'];
        $params = [];

        if ($recherche !== '') {
            $conditions[] = '(nom LIKE :recherche OR ville LIKE :recherche OR description LIKE :recherche)';
            $params['recherche'] = '%' . $recherche . '%';
        }

        $where = implode(' AND ', $conditions);

        $total = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM etablissement_catalogue WHERE $where",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $etablissements = $connection->fetchAllAssociative(
            "SELECT id, nom, ville, description
             FROM etablissement_catalogue
             WHERE $where
             ORDER BY nom ASC
             LIMIT $parPage OFFSET $offset",
            $params
        );

        $html = $this->renderView(
            'partials/_etablissements_cards.html.twig',
            ['etablissements' => $etablissements]
        );

        return $this->json([
            'html' => $html,
            'page' => $page,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $parPage)),
        ]);
    }

    #[Route('/load-metiers', name: 'load_metiers', methods: ['GET'])]
    public function chargerMetiers(Request $request, Connection $connection): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $parPage = 6;
        $recherche = trim((string) ($request->query->get('q')
            ?? $request->query->get('recherche', '')));
        $secteur = trim((string) $request->query->get('secteur', ''));

        $conditions = ['actif = 1'];
        $params = [];

        if ($recherche !== '') {
            $conditions[] = '(nom LIKE :recherche OR description LIKE :recherche OR secteur LIKE :recherche)';
            $params['recherche'] = '%' . $recherche . '%';
        }

        if ($secteur !== '') {
            $conditions[] = 'secteur = :secteur';
            $params['secteur'] = $secteur;
        }

        $where = implode(' AND ', $conditions);

        $total = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM metier_catalogue WHERE $where",
            $params
        );

        $offset = ($page - 1) * $parPage;

        $metiers = $connection->fetchAllAssociative(
            "SELECT id, nom, secteur, description
             FROM metier_catalogue
             WHERE $where
             ORDER BY nom ASC
             LIMIT $parPage OFFSET $offset",
            $params
        );

        $html = $this->renderView(
            'partials/_metiers_cards.html.twig',
            ['metiers' => $metiers]
        );

        return $this->json([
            'html' => $html,
            'page' => $page,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $parPage)),
        ]);
    }
}