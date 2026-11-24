<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserTest extends TestCase
{
    private function creerUtilisateur(
        string $email = 'test@example.com',
        string $password = 'hash-test',
        array $roles = ['ROLE_GESTIONNAIRE'],
        bool $active = true
    ): User {
        return (new User())
            ->setEmail($email)
            ->setPassword($password)
            ->setRoles($roles)
            ->setActive($active);
    }

    public function testDeuxUtilisateursIdentiquesSontEgaux(): void
    {
        $user1 = $this->creerUtilisateur();
        $user2 = $this->creerUtilisateur();

        self::assertTrue($user1->isEqualTo($user2));
    }

    public function testOrdreDesRolesNeChangePasLEgalite(): void
    {
        $user1 = $this->creerUtilisateur(
            roles: ['ROLE_GESTIONNAIRE', 'ROLE_FORMATEUR']
        );

        $user2 = $this->creerUtilisateur(
            roles: ['ROLE_FORMATEUR', 'ROLE_GESTIONNAIRE']
        );

        self::assertTrue($user1->isEqualTo($user2));
    }

    public function testDesactivationDuCompteInvalideLEgalite(): void
    {
        $user1 = $this->creerUtilisateur(active: true);
        $user2 = $this->creerUtilisateur(active: false);

        self::assertFalse($user1->isEqualTo($user2));
    }

    public function testChangementEmailInvalideLEgalite(): void
    {
        $user1 = $this->creerUtilisateur();
        $user2 = $this->creerUtilisateur(email: 'autre@example.com');

        self::assertFalse($user1->isEqualTo($user2));
    }

    public function testChangementMotDePasseInvalideLEgalite(): void
    {
        $user1 = $this->creerUtilisateur();
        $user2 = $this->creerUtilisateur(password: 'nouveau-hash');

        self::assertFalse($user1->isEqualTo($user2));
    }

    public function testChangementRolesInvalideLEgalite(): void
    {
        $user1 = $this->creerUtilisateur(
            roles: ['ROLE_GESTIONNAIRE']
        );

        $user2 = $this->creerUtilisateur(
            roles: ['ROLE_JURY']
        );

        self::assertFalse($user1->isEqualTo($user2));
    }

    public function testUnAutreTypeUtilisateurNestPasEquivalent(): void
    {
        $user = $this->creerUtilisateur();
        $other = $this->createStub(UserInterface::class);

        self::assertFalse($user->isEqualTo($other));
    }
}