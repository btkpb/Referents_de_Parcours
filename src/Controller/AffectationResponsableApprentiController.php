<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AffectationResponsableApprenti;
use App\Entity\Candidat;
use App\Entity\User;
use App\Service\AffectationResponsableApprentiService;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use InvalidArgumentException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AffectationResponsableApprentiController extends AbstractController
{
    #[Route(
        '/candidats/{id}/responsables/{type}',
        name: 'candidat_responsable_affecter',
        methods: ['POST']
    )]
    public function affecter(
        Candidat $candidat,
        string $type,
        Request $request,
        EntityManagerInterface $em,
        AffectationResponsableApprentiService $service
    ): Response {
        // Seuls les gestionnaires et les profils administrateurs
        // disposant de l'héritage ROLE_GESTIONNAIRE peuvent agir.
        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            throw $this->createAccessDeniedException(
                'Vous ne pouvez pas gérer les responsables de cet apprenti.'
            );
        }

        $typesAutorises = [
            AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS,
            AffectationResponsableApprenti::TYPE_FORMATEUR,
            AffectationResponsableApprenti::TYPE_MAITRE_STAGE,
        ];

        if (!in_array($type, $typesAutorises, true)) {
            throw $this->createNotFoundException(
                'Type de responsabilité inconnu.'
            );
        }

        $tokenId = sprintf(
            'affectation_responsable_%d_%s',
            $candidat->getId(),
            $type
        );

        if (!$this->isCsrfTokenValid(
            $tokenId,
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException(
                'Le formulaire a expiré ou son jeton de sécurité est invalide.'
            );
        }

        $auteur = $this->getUser();

        if (!$auteur instanceof User) {
            throw $this->createAccessDeniedException(
                'Votre session ne permet pas cette opération.'
            );
        }

        $responsableId = $request->request->getInt('responsable_id');

        if ($responsableId <= 0) {
            $this->addFlash('danger', 'Veuillez sélectionner un responsable.');
            return $this->redirectToRoute('candidat_show', [
                'id' => $candidat->getId(),
            ]);
        }

        $responsable = $em->getRepository(User::class)->find($responsableId);

        if (!$responsable instanceof User) {
            $this->addFlash('danger', 'Le compte sélectionné est introuvable.');
            return $this->redirectToRoute('candidat_show', [
                'id' => $candidat->getId(),
            ]);
        }

        try {
            $affectation = $service->affecter(
                $candidat,
                $responsable,
                $type,
                $auteur
            );

            $libelles = [
                AffectationResponsableApprenti::TYPE_REFERENT_PARCOURS
                    => 'Référent de parcours',
                AffectationResponsableApprenti::TYPE_FORMATEUR
                    => 'Formateur',
                AffectationResponsableApprenti::TYPE_MAITRE_STAGE
                    => 'Maître de stage',
            ];

            $this->addFlash(
                'success',
                sprintf(
                    '%s affecté avec succès : %s %s.',
                    $libelles[$type],
                    $affectation->getResponsable()?->getPrenoms() ?? '',
                    $affectation->getResponsable()?->getNom() ?? ''
                )
            );
        } catch (DomainException | InvalidArgumentException | LogicException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('candidat_show', [
            'id' => $candidat->getId(),
        ]);
    }
}