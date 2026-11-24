<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PublicAccountController extends AbstractController
{
    #[Route('/creer-compte', name: 'public_compte_creer', methods: ['GET', 'POST'])]
    public function creer(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom', ''));
            $prenoms = trim((string) $request->request->get('prenoms', ''));
            $email = mb_strtolower(trim((string) $request->request->get('email', '')));
            $telephone = trim((string) $request->request->get('telephone', ''));
            $password = (string) $request->request->get('password', '');
            $confirmation = (string) $request->request->get('confirmation', '');

            if (!$this->isCsrfTokenValid('creation_compte_public', (string) $request->request->get('_token'))) {
                $this->addFlash('danger', 'Le formulaire a expiré. Veuillez réessayer.');
            } elseif ($nom === '' || $prenoms === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('danger', 'Veuillez renseigner correctement votre nom, vos prénoms et votre adresse e-mail.');
            } elseif (mb_strlen($nom) > 120 || mb_strlen($prenoms) > 120 || mb_strlen($email) > 180 || mb_strlen($telephone) > 30) {
                $this->addFlash('danger', 'Un ou plusieurs champs dépassent la longueur autorisée.');
            } elseif ($entityManager->getRepository(User::class)->findOneBy(['email' => $email]) !== null) {
                $this->addFlash('danger', 'Cette adresse e-mail est déjà associée à un compte.');
            } elseif (mb_strlen($password) < 12) {
                $this->addFlash('danger', 'Le mot de passe doit contenir au moins 12 caractères.');
            } elseif ($password !== $confirmation) {
                $this->addFlash('danger', 'Les deux mots de passe ne correspondent pas.');
            } else {
                $user = new User();
                $user
                    ->setNom($nom)
                    ->setPrenoms($prenoms)
                    ->setEmail($email)
                    ->setTelephone($telephone !== '' ? $telephone : null)
                    ->setProfil('demandeur')
                    ->setRoles([])
                    ->setActive(false);

                $user->setPassword($passwordHasher->hashPassword($user, $password));

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash(
                    'success',
                    'Votre demande de création de compte a été enregistrée. Votre accès sera disponible après validation par un administrateur.'
                );

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('public_account/creer.html.twig');
    }
}
