<?php

namespace App\Controller;

use App\Entity\AffectationChantier;
use App\Entity\AffectationResponsableApprenti;
use App\Entity\Admission;
use App\Entity\Candidat;
use App\Entity\Emploi;
use App\Entity\Entretien;
use App\Entity\EntretienParcours;
use App\Entity\Evaluation;
use App\Entity\User;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espace-apprenti', name: 'apprenti_')]
#[IsGranted('ROLE_APPRENTI')]
class EspaceApprentiController extends AbstractController
{
    private function candidatConnecte(): ?Candidat
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            return null;
        }

        return $utilisateur->getCandidat();
    }

    private function photoDisponible(
        Candidat $candidat,
        EntityManagerInterface $em
    ): bool {
        $admission = $em->getRepository(Admission::class)
            ->findOneBy(['candidat' => $candidat]);

        return $admission !== null
            && $admission->getDecision() === 'ADMIS'
            && (bool) $candidat->getPhotoIdentite();
    }

    private function pageNonAssociee(): Response
    {
        return $this->render('espace_apprenti/non_associe.html.twig');
    }

    #[Route('', name: 'accueil', methods: ['GET'])]
    public function accueil(EntityManagerInterface $em): Response
    {
        $candidat = $this->candidatConnecte();

        if (!$candidat) {
            return $this->pageNonAssociee();
        }

        return $this->render('espace_apprenti/accueil.html.twig', [
            'candidat' => $candidat,
        ]);
    }

    #[Route('/profil', name: 'profil', methods: ['GET'])]
    public function profil(EntityManagerInterface $em): Response
    {
        $candidat = $this->candidatConnecte();

        if (!$candidat) {
            return $this->pageNonAssociee();
        }

        return $this->render('espace_apprenti/profil.html.twig', [
            'candidat' => $candidat,
            'photoDisponible' => $this->photoDisponible($candidat, $em),
        ]);
    }

    #[Route('/rendez-vous', name: 'rendez_vous', methods: ['GET'])]
    public function rendezVous(EntityManagerInterface $em): Response
    {
        $candidat = $this->candidatConnecte();

        if (!$candidat) {
            return $this->pageNonAssociee();
        }

        $rendezVous = [];

        // Entretiens physiques de recrutement
        $entretiensRecrutement = $em->getRepository(Entretien::class)->findBy(
            ['candidat' => $candidat],
            ['dateHeure' => 'DESC']
        );

        foreach ($entretiensRecrutement as $entretien) {
            $rendezVous[] = [
                'type' => 'RECRUTEMENT',
                'libelle' => 'Entretien de recrutement',
                'date' => $entretien->getDateHeure(),
                'lieu' => $entretien->getLieu() ?: null,
                'interlocuteur' => $entretien->getJuryNoms() ?: null,
                'statut' => $entretien->getStatut() ?: 'PLANIFIE',
                'heureDisponible' => true,
            ];
        }

        // Entretiens d'accueil, de mi-parcours et de sortie
        $entretiensParcours = $em->getRepository(EntretienParcours::class)->findBy(
            ['candidat' => $candidat],
            ['dateEntretien' => 'DESC']
        );

        $libellesParcours = [
            'ACCUEIL' => 'Entretien d’accueil et d’entrée en parcours',
            'MI_PARCOURS' => 'Entretien de suivi à mi-parcours',
            'SORTIE' => 'Entretien de sortie de parcours',
        ];

        foreach ($entretiensParcours as $entretien) {
            $type = strtoupper($entretien->getTypeEntretien());
            $details = $entretien->getDetails();

            $rendezVous[] = [
                'type' => $type,
                'libelle' => $libellesParcours[$type] ?? 'Entretien de parcours',
                'date' => $entretien->getDateEntretien(),
                'lieu' => null,
                'interlocuteur' => $details['nomReferent'] ?? null,
                'statut' => $entretien->getStatut() ?: 'REALISE',
                'heureDisponible' => false,
            ];
        }

        // Classement chronologique décroissant
        usort($rendezVous, static function (array $a, array $b): int {
            $dateA = $a['date']?->getTimestamp() ?? 0;
            $dateB = $b['date']?->getTimestamp() ?? 0;

            return $dateB <=> $dateA;
        });

        return $this->render('espace_apprenti/rendez_vous.html.twig', [
            'rendezVous' => $rendezVous,
        ]);
    }
    #[Route('/parcours', name: 'parcours', methods: ['GET'])]
    public function parcours(EntityManagerInterface $em): Response
    {
        $candidat = $this->candidatConnecte();

        if (!$candidat) {
            return $this->pageNonAssociee();
        }

        $entretiensParcours = $em->getRepository(EntretienParcours::class)
            ->findBy(['candidat' => $candidat], ['dateEntretien' => 'DESC']);

        $affectations = $em->getRepository(AffectationChantier::class)
            ->findBy(['candidat' => $candidat], ['dateDebut' => 'DESC']);

        $emplois = $em->getRepository(Emploi::class)
            ->findBy(['candidat' => $candidat], ['dateInsertion' => 'DESC']);

        // Seules les affectations actives de cet apprenti sont affichées.
        $historiqueResponsables = $em
            ->getRepository(AffectationResponsableApprenti::class)
            ->findBy(
                [
                    'candidat' => $candidat,
                    'statut' => AffectationResponsableApprenti::STATUT_ACTIVE,
                    'dateFin' => null,
                ],
                ['dateDebut' => 'DESC', 'id' => 'DESC']
            );

        $responsablesApprenti = [];

        foreach ($historiqueResponsables as $affectationResponsable) {
            if (!$affectationResponsable instanceof AffectationResponsableApprenti) {
                continue;
            }

            $type = $affectationResponsable->getTypeResponsabilite();

            if (!isset($responsablesApprenti[$type])) {
                $responsablesApprenti[$type] = $affectationResponsable;
            }
        }

        return $this->render('espace_apprenti/parcours.html.twig', [
            'entretiensParcours' => $entretiensParcours,
            'affectations' => $affectations,
            'emplois' => $emplois,
            'responsablesApprenti' => $responsablesApprenti,
        ]);
    }
    #[Route('/evaluations', name: 'evaluations', methods: ['GET'])]
    public function evaluations(EntityManagerInterface $em): Response
    {
        $candidat = $this->candidatConnecte();

        if (!$candidat) {
            return $this->pageNonAssociee();
        }

        $evaluations = $em->getRepository(Evaluation::class)
            ->createQueryBuilder('evaluation')
            ->innerJoin('evaluation.entretien', 'entretien')
            ->andWhere('entretien.candidat = :candidat')
            ->setParameter('candidat', $candidat)
            ->orderBy('entretien.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('espace_apprenti/evaluations.html.twig', [
            'evaluations' => $evaluations,
        ]);
    }

    #[Route('/accompagnement', name: 'accompagnement', methods: ['GET'])]
    public function accompagnement(EntityManagerInterface $em): Response
    {
        $candidat = $this->candidatConnecte();

        if (!$candidat) {
            return $this->pageNonAssociee();
        }

        $entretiensParcours = $em->getRepository(EntretienParcours::class)
            ->findBy(['candidat' => $candidat], ['dateEntretien' => 'DESC']);

        return $this->render('espace_apprenti/accompagnement.html.twig', [
            'entretiensParcours' => $entretiensParcours,
        ]);
    }

    #[Route('/notifications', name: 'notifications', methods: ['GET'])]
    public function notifications(EntityManagerInterface $em): Response
    {
        if (!$this->candidatConnecte()) {
            return $this->pageNonAssociee();
        }

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $repository = $em->getRepository(Notification::class);

        $notifications = $repository->findBy(
            [
                'destinataire' => $utilisateur,
                'archivedAt' => null,
            ],
            ['createdAt' => 'DESC']
        );

        $notificationsNonLues = $repository->count([
            'destinataire' => $utilisateur,
            'archivedAt' => null,
            'readAt' => null,
        ]);

        return $this->render('espace_apprenti/notifications.html.twig', [
            'notifications' => $notifications,
            'notificationsNonLues' => $notificationsNonLues,
        ]);
    }

    #[Route('/notifications/{id}/lire', name: 'notification_lire', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function lireNotification(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $notification = $em->getRepository(Notification::class)->find($id);

        if (!$notification instanceof Notification
            || $notification->getDestinataire() !== $this->getUser()
        ) {
            throw $this->createNotFoundException('Notification introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'lire_notification_' . $id,
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $notification->markAsRead();
        $em->flush();

        $this->addFlash('success', 'Notification marquée comme lue.');

        return $this->redirectToRoute('apprenti_notifications');
    }

    #[Route('/notifications/tout-lire', name: 'notifications_tout_lire', methods: ['POST'])]
    public function toutLireNotifications(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid(
            'notifications_tout_lire',
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $notifications = $em->getRepository(Notification::class)->findBy([
            'destinataire' => $utilisateur,
            'archivedAt' => null,
            'readAt' => null,
        ]);

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        $em->flush();

        $this->addFlash('success', 'Toutes vos notifications ont été marquées comme lues.');

        return $this->redirectToRoute('apprenti_notifications');
    }

    #[Route('/notifications/{id}/archiver', name: 'notification_archiver', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archiverNotification(
        int $id,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $notification = $em->getRepository(Notification::class)->find($id);

        if (!$notification instanceof Notification
            || $notification->getDestinataire() !== $this->getUser()
        ) {
            throw $this->createNotFoundException('Notification introuvable.');
        }

        if (!$this->isCsrfTokenValid(
            'archiver_notification_' . $id,
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $notification->setArchivedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Notification archivée.');

        return $this->redirectToRoute('apprenti_notifications');
    }
}