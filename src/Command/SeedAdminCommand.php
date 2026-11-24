<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un compte administrateur E2C de manière sécurisée'
)]
class SeedAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $repository = $this->em->getRepository(User::class);

        if ($repository->findOneBy(['email' => 'admin@e2c.ci'])) {
            $output->writeln('<error>Le compte admin@e2c.ci existe déjà. Aucun changement effectué.</error>');
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');

        $question = new Question('Nouveau mot de passe administrateur (12 caractères minimum) : ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);
        $question->setValidator(function ($answer) {
            if (!is_string($answer) || strlen($answer) < 12) {
                throw new \RuntimeException('Le mot de passe doit contenir au moins 12 caractères.');
            }

            return $answer;
        });

        $password = $helper->ask($input, $output, $question);

        $confirmation = new Question('Confirmez le mot de passe : ');
        $confirmation->setHidden(true);
        $confirmation->setHiddenFallback(false);
        $confirmation->setValidator(function ($answer) use ($password) {
            if (!is_string($answer) || !hash_equals($password, $answer)) {
                throw new \RuntimeException('Les deux mots de passe ne correspondent pas.');
            }

            return $answer;
        });

        $helper->ask($input, $output, $confirmation);

        $user = new User();
        $user
            ->setEmail('admin@e2c.ci')
            ->setNom('Administrateur')
            ->setPrenoms('E2C')
            ->setProfil('gestionnaire')
            ->setRoles(['ROLE_ADMIN'])
            ->setActive(true);

        $user->setPassword(
            $this->hasher->hashPassword($user, $password)
        );

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('<info>Le compte administrateur a été créé avec succès.</info>');
        $output->writeln('Identifiant : admin@e2c.ci');
        $output->writeln('Le mot de passe n’est pas affiché.');

        return Command::SUCCESS;
    }
}