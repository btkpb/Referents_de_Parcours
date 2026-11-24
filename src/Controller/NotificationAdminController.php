<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gestion/notifications', name: 'notification_admin_')]
#[IsGranted('ROLE_GESTIONNAIRE')]
class NotificationAdminController extends AbstractController
{
    private const TYPES = [
        'INFORMATION',
        'RAPPEL',
        'CONVOCATION',
        'IMPORTANT',
    ];

    #[Route('', name: 'envoyer', methods: ['GET', 'POST'])]
    public function envoyer(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $repositoryUsers = $em->getRepository(User::class);

        $apprentis = array_values(array_filter(
            $repositoryUsers->findBy(
                ['active' => true, 'profil' => 'apprenti'],
                ['nom' => 'ASC', 'prenoms' => 'ASC']
            ),
            static fn (User $user): bool =>
                $user->isActive()
                && in_array('ROLE_APPRENTI', $user->getRoles(), true)
        ));

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'notification_manuelle',
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException(
                    'Jeton de sécurité invalide.'
                );
            }

            $mode = (string) $request->request->get('mode', '');
            $titre = trim((string) $request->request->get('titre', ''));
            $message = trim((string) $request->request->get('message', ''));
            $type = (string) $request->request->get('type', 'INFORMATION');

            if (
                $titre === ''
                || mb_strlen($titre) > 180
                || $message === ''
                || mb_strlen($message) > 5000
                || !in_array($type, self::TYPES, true)
            ) {
                $this->addFlash(
                    'danger',
                    'Vérifie le titre, le message et la catégorie.'
                );
            } else {
                $destinataires = [];

                if ($mode === 'tous') {
                    $destinataires = $apprentis;
                } elseif ($mode === 'unique') {
                    $id = filter_var(
                        $request->request->get('destinataire_id'),
                        FILTER_VALIDATE_INT
                    );

                    if ($id) {
                        $user = $repositoryUsers->find($id);

                        if (
                            $user instanceof User
                            && $user->isActive()
                            && $user->getProfil() === 'apprenti'
                            && in_array('ROLE_APPRENTI', $user->getRoles(), true)
                        ) {
                            $destinataires = [$user];
                        }
                    }
                } elseif ($mode === 'selection') {
                    $ids = $request->request->all('destinataires');
                    $ids = array_values(array_unique(array_filter(
                        array_map(
                            static fn ($id) => filter_var(
                                $id,
                                FILTER_VALIDATE_INT
                            ),
                            $ids
                        ),
                        static fn ($id) => $id !== false && $id > 0
                    )));

                    if ($ids !== []) {
                        $users = $repositoryUsers->findBy(['id' => $ids]);

                        foreach ($users as $user) {
                            if (
                                $user instanceof User
                                && $user->isActive()
                                && $user->getProfil() === 'apprenti'
                                && in_array(
                                    'ROLE_APPRENTI',
                                    $user->getRoles(),
                                    true
                                )
                            ) {
                                $destinataires[] = $user;
                            }
                        }
                    }
                }

                if ($destinataires === []) {
                    $this->addFlash(
                        'warning',
                        'Aucun apprenti actif valide n’a été sélectionné.'
                    );
                } else {
                    foreach ($destinataires as $destinataire) {
                        $notification = (new Notification())
                            ->setDestinataire($destinataire)
                            ->setTitre($titre)
                            ->setMessage($message)
                            ->setType($type)
                            ->setDeduplicationKey(
                                'MANUEL-' . bin2hex(random_bytes(16))
                            );

                        $em->persist($notification);
                    }

                    $em->flush();

                    $this->addFlash(
                        'success',
                        sprintf(
                            'Notification enregistrée pour %d apprenant(s).',
                            count($destinataires)
                        )
                    );

                    return $this->redirectToRoute(
                        'notification_admin_envoyer'
                    );
                }
            }
        }

        $historique = $em->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->innerJoin('n.destinataire', 'u')
            ->addSelect('u')
            ->where('n.deduplicationKey LIKE :prefix')
            ->setParameter('prefix', 'MANUEL-%')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult();

        return $this->render('notification_admin/envoyer.html.twig', [
            'apprentis' => $apprentis,
            'historique' => $historique,
            'types' => self::TYPES,
        ]);
    }
}