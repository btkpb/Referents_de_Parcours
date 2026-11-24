<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\EmailInformationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gestion/emails-information', name: 'email_information_')]
#[IsGranted('ROLE_GESTIONNAIRE')]
final class EmailInformationController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EmailInformationService $service
    ): Response {
        $audience = strtoupper((string) $request->query->get('audience', 'CANDIDAT'));

        if (!in_array($audience, ['CANDIDAT', 'APPRENTI'], true)) {
            $audience = 'CANDIDAT';
        }

        $campagneId = filter_var(
            $request->query->get('campagne_id'),
            FILTER_VALIDATE_INT
        );

        $filtres = [
            'campagne_id' => $campagneId !== false ? $campagneId : null,
            'metier' => trim((string) $request->query->get('metier', '')),
            'statut' => trim((string) $request->query->get('statut', '')),
        ];

        return $this->render('emails_information/index.html.twig', [
            'audience' => $audience,
            'filtres' => $filtres,
            'destinataires' => $service->lister($audience, $filtres),
            'campagnes' => $service->campagnes(),
            'metiers' => $service->metiers(),
        ]);
    }

    #[Route('/previsualiser', name: 'preview', methods: ['POST'])]
    public function preview(
        Request $request,
        EmailInformationService $service
    ): Response {
        if (!$this->isCsrfTokenValid(
            'email_information_preview',
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $audience = strtoupper((string) $request->request->get('audience', ''));
        $ids = $request->request->all('destinataires');
        $sujet = trim((string) $request->request->get('sujet', ''));
        $contenu = trim((string) $request->request->get('contenu', ''));

        if (!in_array($audience, ['CANDIDAT', 'APPRENTI'], true)) {
            throw $this->createNotFoundException('Audience inconnue.');
        }

        if (
            $sujet === ''
            || mb_strlen($sujet) > 255
            || $contenu === ''
            || mb_strlen($contenu) > 10000
        ) {
            $this->addFlash('danger', 'Le sujet ou le contenu du message est invalide.');

            return $this->redirectToRoute('email_information_index', [
                'audience' => strtolower($audience),
            ]);
        }

        $destinataires = $service->selectionner($audience, $ids);

        if ($destinataires === []) {
            $this->addFlash('warning', 'Aucun destinataire valide n’a été sélectionné.');

            return $this->redirectToRoute('email_information_index', [
                'audience' => strtolower($audience),
            ]);
        }

        $request->getSession()->set('email_information_brouillon', [
            'audience' => $audience,
            'ids' => array_map(
                static fn (array $d): int => (int) $d['destinataire_id'],
                $destinataires
            ),
            'sujet' => $sujet,
            'contenu' => $contenu,
        ]);

        $valides = array_values(array_filter(
            $destinataires,
            static fn (array $d): bool => $d['email_valide']
        ));

        $exclus = array_values(array_filter(
            $destinataires,
            static fn (array $d): bool => !$d['email_valide']
        ));

        return $this->render('emails_information/preview.html.twig', [
            'audience' => $audience,
            'sujet' => $sujet,
            'contenu' => $contenu,
            'destinataires' => $valides,
            'exclus' => $exclus,
        ]);
    }

    #[Route('/confirmer', name: 'send', methods: ['POST'])]
    public function confirmer(
        Request $request,
        EmailInformationService $service
    ): Response {
        if (!$this->isCsrfTokenValid(
            'email_information_send',
            (string) $request->request->get('_token', '')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $brouillon = $request->getSession()->get('email_information_brouillon');

        if (
            !is_array($brouillon)
            || !isset(
                $brouillon['audience'],
                $brouillon['ids'],
                $brouillon['sujet'],
                $brouillon['contenu']
            )
        ) {
            $this->addFlash('warning', 'La préparation de l’envoi a expiré.');

            return $this->redirectToRoute('email_information_index');
        }

        // L'envoi réel ne se produit qu'après cette confirmation explicite.
        $resultat = $service->envoyer(
            $brouillon['audience'],
            $brouillon['ids'],
            $brouillon['sujet'],
            $brouillon['contenu']
        );

        $request->getSession()->remove('email_information_brouillon');

        $this->addFlash(
            'success',
            sprintf(
                'Envoi terminé : %d envoyé(s), %d échec(s), %d adresse(s) exclue(s).',
                $resultat['envoyes'],
                $resultat['echecs'],
                $resultat['exclus']
            )
        );

        return $this->redirectToRoute('email_information_history');
    }

    #[Route('/historique', name: 'history', methods: ['GET'])]
    public function historique(EmailInformationService $service): Response
    {
        return $this->render('emails_information/historique.html.twig', [
            'historique' => $service->historique(),
        ]);
    }
}