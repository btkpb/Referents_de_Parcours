<?php

namespace App\Controller;

use App\Entity\Candidat;
use App\Entity\Entretien;
use App\Entity\VisiteMedicale;
use App\Entity\Admission;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(
        EntityManagerInterface $em,
        Request $request
    ): Response {
        // Redirection du Maître de stage vers son espace de travail.
        // Les administrateurs et gestionnaires conservent le tableau de bord général.
        if (
            $this->isGranted('ROLE_MAITRE_STAGE')
            && !$this->isGranted('ROLE_GESTIONNAIRE')
        ) {
            return $this->redirectToRoute('maitre_stage_index');
        }

        $session = $request->getSession();
        $design = $request->query->get('design');

        if (in_array($design, ['classique', 'professionnel'], true)) {
            $session->set('dashboard_design', $design);
        }

        $designActif = $session->get(
            'dashboard_design',
            'professionnel'
        );

        $stats = [
            'candidats' => $em->getRepository(Candidat::class)->count([]),
            'entretiens' => $em->getRepository(Entretien::class)->count([]),
            'visites' => $em->getRepository(VisiteMedicale::class)->count([]),
            'admissions' => $em->getRepository(Admission::class)->count([
                'decision' => 'ADMIS'
            ]),
        ];


        // Nombre de notifications non lues de l'apprenti connecté.
        $notificationsNonLuesDashboard = 0;

        $utilisateurConnecte = $this->getUser();

        if (
            $this->isGranted('ROLE_APPRENTI')
            && $utilisateurConnecte instanceof User
        ) {
            $notificationsNonLuesDashboard = $em
                ->getRepository(Notification::class)
                ->count([
                    'destinataire' => $utilisateurConnecte,
                    'archivedAt' => null,
                    'readAt' => null,
                ]);
        }
        $template = $designActif === 'classique'
            ? 'dashboard/index_classique.html.twig'
            : 'dashboard/index.html.twig';

        return $this->render($template, [
            'stats' => $stats,
            'designActif' => $designActif,
            'notificationsNonLues' => $notificationsNonLuesDashboard,
        ]);
    }
}