<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/configuration-emails', name: 'admin_configuration_email_')]
class ConfigurationEmailController extends AbstractController
{
    private const CLE_NOM_EXPEDITEUR = 'nom_expediteur_convocation';
    private const NOM_PAR_DEFAUT = 'Admin Référent de Parcours Plateforme';

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMINISTRATEUR');

        $nomExpediteur = $db->fetchOne(
            'SELECT valeur FROM configuration_email WHERE cle = :cle',
            ['cle' => self::CLE_NOM_EXPEDITEUR]
        );

        if (!is_string($nomExpediteur) || trim($nomExpediteur) === '') {
            $nomExpediteur = self::NOM_PAR_DEFAUT;
        }

        return $this->render('admin/configuration_email/index.html.twig', [
            'nomExpediteur' => $nomExpediteur,
            'adresseExpediteur' => 'bakari_parfait@yahoo.fr',
        ]);
    }

    #[Route('/enregistrer', name: 'save', methods: ['POST'])]
    public function enregistrer(Request $request, Connection $db): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMINISTRATEUR');

        if (!$this->isCsrfTokenValid(
            'configuration_email',
            (string) $request->request->get('_token', '')
        )) {
            $this->addFlash('danger', 'Le formulaire a expiré. Veuillez réessayer.');

            return $this->redirectToRoute('admin_configuration_email_index');
        }

        $nomExpediteur = trim((string) $request->request->get('nom_expediteur', ''));

        if ($nomExpediteur === '') {
            $this->addFlash('danger', 'Le nom de l’expéditeur est obligatoire.');

            return $this->redirectToRoute('admin_configuration_email_index');
        }

        if (mb_strlen($nomExpediteur) > 120) {
            $this->addFlash('danger', 'Le nom ne doit pas dépasser 120 caractères.');

            return $this->redirectToRoute('admin_configuration_email_index');
        }

        $db->executeStatement(
            'INSERT INTO configuration_email (cle, valeur)
             VALUES (:cle, :valeur)
             ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)',
            [
                'cle' => self::CLE_NOM_EXPEDITEUR,
                'valeur' => $nomExpediteur,
            ]
        );

        $this->addFlash('success', 'Le nom affiché de l’expéditeur a été enregistré.');

        return $this->redirectToRoute('admin_configuration_email_index');
    }
}