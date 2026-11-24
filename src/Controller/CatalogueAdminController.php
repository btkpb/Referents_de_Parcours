<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/catalogue', name: 'admin_catalogue_')]
class CatalogueAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        return $this->render('admin/catalogue/index.html.twig', [
            'etablissements' => $db->fetchAllAssociative(
                'SELECT id, nom, ville, description, actif, created_at
                 FROM etablissement_catalogue
                 ORDER BY actif DESC, nom ASC'
            ),
            'metiers' => $db->fetchAllAssociative(
                'SELECT id, nom, secteur, description, actif, created_at
                 FROM metier_catalogue
                 ORDER BY actif DESC, nom ASC'
            ),
        ]);
    }

    #[Route('/etablissements/nouveau', name: 'etablissement_new', methods: ['GET', 'POST'])]
    public function nouvelEtablissement(Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        return $this->formulaireEtablissement(null, $request, $db);
    }

    #[Route('/etablissements/{id}/modifier', name: 'etablissement_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierEtablissement(int $id, Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $item = $db->fetchAssociative(
            'SELECT id, nom, ville, description, actif
             FROM etablissement_catalogue WHERE id = :id',
            ['id' => $id]
        );

        if (!$item) {
            throw $this->createNotFoundException('Etablissement introuvable.');
        }

        return $this->formulaireEtablissement($item, $request, $db);
    }

    private function formulaireEtablissement(?array $item, Request $request, Connection $db): Response
    {
        $edition = $item !== null;
        $id = $edition ? (int) $item['id'] : null;

        $item ??= [
            'nom' => '',
            'ville' => '',
            'description' => '',
            'actif' => 1,
        ];

        if ($request->isMethod('POST')) {
            $item['nom'] = trim((string) $request->request->get('nom', ''));
            $item['ville'] = trim((string) $request->request->get('ville', ''));
            $item['description'] = trim((string) $request->request->get('description', ''));
            $item['actif'] = $request->request->getBoolean('actif') ? 1 : 0;

            if (!$this->isCsrfTokenValid(
                'catalogue_etablissement_form',
                (string) $request->request->get('_token', '')
            )) {
                $this->addFlash('danger', 'Jeton de securite invalide.');
            } elseif ($item['nom'] === '' || mb_strlen($item['nom']) > 180) {
                $this->addFlash('danger', 'Le nom est obligatoire et limite a 180 caracteres.');
            } elseif ($item['ville'] === '' || mb_strlen($item['ville']) > 120) {
                $this->addFlash('danger', 'La ville est obligatoire et limitee a 120 caracteres.');
            } elseif (mb_strlen($item['description']) > 5000) {
                $this->addFlash('danger', 'La description ne doit pas depasser 5000 caracteres.');
            } else {
                try {
                    $donnees = [
                        'nom' => $item['nom'],
                        'ville' => $item['ville'],
                        'description' => $item['description'] !== '' ? $item['description'] : null,
                        'actif' => $item['actif'],
                    ];

                    if ($edition) {
                        $db->update('etablissement_catalogue', $donnees, ['id' => $id]);
                        $this->addFlash('success', 'Etablissement modifie.');
                    } else {
                        $db->insert('etablissement_catalogue', $donnees + [
                            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        ]);
                        $this->addFlash('success', 'Etablissement cree.');
                    }

                    return $this->redirectToRoute('admin_catalogue_index');
                } catch (UniqueConstraintViolationException) {
                    $this->addFlash('danger', 'Un etablissement portant ce nom existe deja dans cette ville.');
                }
            }
        }

        return $this->render('admin/catalogue/form.html.twig', [
            'type' => 'etablissement',
            'item' => $item,
            'titre' => $edition ? 'Modifier un etablissement' : 'Creer un etablissement',
            'action' => $edition
                ? $this->generateUrl('admin_catalogue_etablissement_edit', ['id' => $id])
                : $this->generateUrl('admin_catalogue_etablissement_new'),
            'annulation' => $this->generateUrl('admin_catalogue_index'),
        ]);
    }

    #[Route('/etablissements/{id}/supprimer', name: 'etablissement_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerEtablissement(int $id, Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        if (!$this->isCsrfTokenValid(
            'supprimer_etablissement_' . $id,
            (string) $request->request->get('_token', '')
        )) {
            $this->addFlash('danger', 'Jeton de securite invalide.');
            return $this->redirectToRoute('admin_catalogue_index');
        }

        $nombre = $db->executeStatement(
            'UPDATE etablissement_catalogue SET actif = 0 WHERE id = :id',
            ['id' => $id]
        );

        $this->addFlash(
            $nombre ? 'success' : 'warning',
            $nombre
                ? 'Etablissement retire du catalogue public. Son historique est conserve.'
                : 'Etablissement introuvable ou deja desactive.'
        );

        return $this->redirectToRoute('admin_catalogue_index');
    }

    #[Route('/metiers/nouveau', name: 'metier_new', methods: ['GET', 'POST'])]
    public function nouveauMetier(Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        return $this->formulaireMetier(null, $request, $db);
    }

    #[Route('/metiers/{id}/modifier', name: 'metier_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function modifierMetier(int $id, Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        $item = $db->fetchAssociative(
            'SELECT id, nom, secteur, description, actif
             FROM metier_catalogue WHERE id = :id',
            ['id' => $id]
        );

        if (!$item) {
            throw $this->createNotFoundException('Metier introuvable.');
        }

        return $this->formulaireMetier($item, $request, $db);
    }

    private function formulaireMetier(?array $item, Request $request, Connection $db): Response
    {
        $edition = $item !== null;
        $id = $edition ? (int) $item['id'] : null;

        $item ??= [
            'nom' => '',
            'secteur' => '',
            'description' => '',
            'actif' => 1,
        ];

        if ($request->isMethod('POST')) {
            $item['nom'] = trim((string) $request->request->get('nom', ''));
            $item['secteur'] = trim((string) $request->request->get('secteur', ''));
            $item['description'] = trim((string) $request->request->get('description', ''));
            $item['actif'] = $request->request->getBoolean('actif') ? 1 : 0;

            if (!$this->isCsrfTokenValid(
                'catalogue_metier_form',
                (string) $request->request->get('_token', '')
            )) {
                $this->addFlash('danger', 'Jeton de securite invalide.');
            } elseif ($item['nom'] === '' || mb_strlen($item['nom']) > 120) {
                $this->addFlash('danger', 'Le nom du metier est obligatoire (120 caracteres maximum).');
            } elseif ($item['secteur'] === '' || mb_strlen($item['secteur']) > 120) {
                $this->addFlash('danger', 'Le secteur est obligatoire (120 caracteres maximum).');
            } elseif (mb_strlen($item['description']) > 5000) {
                $this->addFlash('danger', 'La description ne doit pas depasser 5000 caracteres.');
            } else {
                try {
                    $donnees = [
                        'nom' => $item['nom'],
                        'secteur' => $item['secteur'],
                        'description' => $item['description'] !== '' ? $item['description'] : null,
                        'actif' => $item['actif'],
                    ];

                    if ($edition) {
                        $db->update('metier_catalogue', $donnees, ['id' => $id]);
                        $this->addFlash('success', 'Metier modifie.');
                    } else {
                        $db->insert('metier_catalogue', $donnees + [
                            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                        ]);
                        $this->addFlash('success', 'Metier cree.');
                    }

                    return $this->redirectToRoute('admin_catalogue_index');
                } catch (UniqueConstraintViolationException) {
                    $this->addFlash('danger', 'Ce nom de metier existe deja. Modifiez le metier existant si vous souhaitez le reactiver.');
                }
            }
        }

        return $this->render('admin/catalogue/form.html.twig', [
            'type' => 'metier',
            'item' => $item,
            'titre' => $edition ? 'Modifier un metier' : 'Creer un metier',
            'action' => $edition
                ? $this->generateUrl('admin_catalogue_metier_edit', ['id' => $id])
                : $this->generateUrl('admin_catalogue_metier_new'),
            'annulation' => $this->generateUrl('admin_catalogue_index'),
        ]);
    }

    #[Route('/metiers/{id}/supprimer', name: 'metier_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function supprimerMetier(int $id, Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_GESTIONNAIRE');

        if (!$this->isCsrfTokenValid(
            'supprimer_metier_' . $id,
            (string) $request->request->get('_token', '')
        )) {
            $this->addFlash('danger', 'Jeton de securite invalide.');
            return $this->redirectToRoute('admin_catalogue_index');
        }

        $nombre = $db->executeStatement(
            'UPDATE metier_catalogue SET actif = 0 WHERE id = :id',
            ['id' => $id]
        );

        $this->addFlash(
            $nombre ? 'success' : 'warning',
            $nombre
                ? 'Metier retire du catalogue public. Son historique est conserve.'
                : 'Metier introuvable ou deja desactive.'
        );

        return $this->redirectToRoute('admin_catalogue_index');
    }
}