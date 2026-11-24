<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Entretien;
use App\Entity\User;
use App\Service\ConvocationPdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConvocationController extends AbstractController
{
    #[Route(
        '/recrutement/entretiens/{id}/convocation/preview',
        name: 'recruitment_convocation_preview',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function preview(
        int $id,
        EntityManagerInterface $em,
        ConvocationPdfService $pdfService
    ): Response {
        $utilisateur = $this->getUser();

        if (
            !$utilisateur instanceof User
            || !$utilisateur->isActive()
            || !$this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            throw $this->createAccessDeniedException(
                'Seul un gestionnaire actif peut prévisualiser les convocations.'
            );
        }

        $entretien = $em->getRepository(Entretien::class)->find($id);

        if (!$entretien instanceof Entretien) {
            throw $this->createNotFoundException(
                'Entretien introuvable.'
            );
        }

        $chemin = $pdfService->generer($entretien);
        $contenu = file_get_contents($chemin);

        if ($contenu === false) {
            throw new \RuntimeException(
                'Impossible de lire le PDF généré.'
            );
        }

        $nomFichier = basename($chemin);

        return new Response(
            $contenu,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $nomFichier . '"',
                'Cache-Control' => 'private, no-store, max-age=0',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}