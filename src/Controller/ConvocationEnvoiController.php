<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ConvocationEnvoi;
use App\Entity\Entretien;
use App\Entity\User;
use App\Service\ConvocationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ConvocationEnvoiController extends AbstractController
{
    private function verifierGestionnaireActif(): void
    {
        $utilisateur = $this->getUser();

        if (
            !$utilisateur instanceof User
            || !$utilisateur->isActive()
            || !$this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            throw $this->createAccessDeniedException(
                'Accès réservé aux gestionnaires actifs.'
            );
        }
    }

    #[Route(
        '/recrutement/entretiens/{id}/convocation/envoyer',
        name: 'recruitment_convocation_send',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function envoyer(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ConvocationEmailService $service
    ): Response {
        $this->verifierGestionnaireActif();

        if (!$this->isCsrfTokenValid(
            'convocation_send_' . $id,
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Le formulaire a expiré. Veuillez réessayer.');

            return $this->redirectToRoute('recruitment_interviews');
        }

        $entretien = $em->find(Entretien::class, $id);

        if (!$entretien instanceof Entretien) {
            throw $this->createNotFoundException('Entretien introuvable.');
        }

        try {
            $envoi = $service->envoyer($entretien);

            if ($envoi->getStatut() === 'ENVOYE') {
                $this->addFlash(
                    'success',
                    'La convocation a été envoyée à ' . $envoi->getDestinataire() . '.'
                );
            } elseif ($envoi->getStatut() === 'SANS_EMAIL') {
                $this->addFlash(
                    'warning',
                    'Aucune adresse e-mail n’est renseignée pour ce candidat.'
                );
            } else {
                $this->addFlash(
                    'danger',
                    'La tentative d’envoi a échoué. Consultez l’historique des convocations.'
                );
            }
        } catch (\Throwable $exception) {
            $this->addFlash(
                'danger',
                'La tentative n’a pas pu être finalisée. Vérifiez l’historique avant toute nouvelle relance.'
            );
        }

        return $this->redirectToRoute('recruitment_interviews');
    }

    #[Route(
        '/recrutement/entretiens/convocations/envoyer-groupe',
        name: 'recruitment_convocation_send_group',
        methods: ['POST']
    )]
    public function envoyerGroupe(
        Request $request,
        EntityManagerInterface $em,
        ConvocationEmailService $service
    ): Response {
        $this->verifierGestionnaireActif();

        if (!$this->isCsrfTokenValid(
            'convocation_send_group',
            (string) $request->request->get('_token')
        )) {
            $this->addFlash('danger', 'Le formulaire groupé a expiré.');

            return $this->redirectToRoute('recruitment_interviews');
        }

        $donnees = $request->request->all();
        $idsBruts = $donnees['entretien_ids'] ?? [];

        if (!is_array($idsBruts)) {
            $this->addFlash('danger', 'La sélection des entretiens est invalide.');

            return $this->redirectToRoute('recruitment_interviews');
        }

        $ids = [];

        foreach ($idsBruts as $idBrut) {
            $id = filter_var($idBrut, FILTER_VALIDATE_INT);

            if ($id !== false && $id > 0) {
                $ids[] = $id;
            }
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            $this->addFlash('warning', 'Sélectionnez au moins un entretien.');

            return $this->redirectToRoute('recruitment_interviews');
        }

        if (count($ids) > 50) {
            $this->addFlash(
                'warning',
                'La limite est de 50 entretiens par opération groupée.'
            );

            return $this->redirectToRoute('recruitment_interviews');
        }

        $entretiens = $em->getRepository(Entretien::class)->findBy([
            'id' => $ids,
        ]);

        $envoyes = 0;
        $sansEmail = 0;
        $echecs = 0;
        $nonTraites = count($ids) - count($entretiens);

        foreach ($entretiens as $entretien) {
            if (!$em->isOpen()) {
                $nonTraites++;
                break;
            }

            try {
                $envoi = $service->envoyer($entretien);

                switch ($envoi->getStatut()) {
                    case 'ENVOYE':
                        $envoyes++;
                        break;

                    case 'SANS_EMAIL':
                        $sansEmail++;
                        break;

                    default:
                        $echecs++;
                        break;
                }
            } catch (\Throwable $exception) {
                $echecs++;

                // Une erreur Doctrine peut fermer l'EntityManager.
                // Dans ce cas, ne pas lancer les tentatives suivantes.
                if (!$em->isOpen()) {
                    break;
                }
            }
        }

        $this->addFlash(
            'success',
            sprintf(
                'Envoi groupé terminé : %d envoyé(s), %d sans adresse e-mail, %d échec(s), %d non traité(s).',
                $envoyes,
                $sansEmail,
                $echecs,
                $nonTraites
            )
        );

        return $this->redirectToRoute('recruitment_interviews');
    }

    #[Route(
        '/recrutement/convocations/historique',
        name: 'recruitment_convocation_history',
        methods: ['GET']
    )]
    public function historique(
        EntityManagerInterface $em
    ): Response {
        $this->verifierGestionnaireActif();

        $envois = $em->getRepository(ConvocationEnvoi::class)->findBy(
            [],
            ['dateTentative' => 'DESC'],
            100
        );

        return $this->render('recrutement/convocations_historique.html.twig', [
            'envois' => $envois,
        ]);
    }
}