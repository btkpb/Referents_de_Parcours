<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'entreprise_stage')]
class EntrepriseStage
{
    public const STATUT_ACTIVE = 'ACTIVE';
    public const STATUT_INACTIVE = 'INACTIVE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $raisonSociale = '';

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $sigle = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $secteurActivite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephoneSecondaire = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $nomResponsable = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $fonctionResponsable = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephoneResponsable = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $nomTuteur = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $fonctionTuteur = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephoneTuteur = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $emailTuteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cachetFichier = null;

    #[ORM\Column(length: 30)]
    private string $statut = self::STATUT_ACTIVE;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRaisonSociale(): string
    {
        return $this->raisonSociale;
    }

    public function setRaisonSociale(string $raisonSociale): self
    {
        $this->raisonSociale = $raisonSociale;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSigle(): ?string
    {
        return $this->sigle;
    }

    public function setSigle(?string $sigle): self
    {
        $this->sigle = $sigle;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getSecteurActivite(): ?string
    {
        return $this->secteurActivite;
    }

    public function setSecteurActivite(?string $secteurActivite): self
    {
        $this->secteurActivite = $secteurActivite;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): self
    {
        $this->ville = $ville;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTelephoneSecondaire(): ?string
    {
        return $this->telephoneSecondaire;
    }

    public function setTelephoneSecondaire(?string $telephoneSecondaire): self
    {
        $this->telephoneSecondaire = $telephoneSecondaire;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNomResponsable(): ?string
    {
        return $this->nomResponsable;
    }

    public function setNomResponsable(?string $nomResponsable): self
    {
        $this->nomResponsable = $nomResponsable;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFonctionResponsable(): ?string
    {
        return $this->fonctionResponsable;
    }

    public function setFonctionResponsable(?string $fonctionResponsable): self
    {
        $this->fonctionResponsable = $fonctionResponsable;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTelephoneResponsable(): ?string
    {
        return $this->telephoneResponsable;
    }

    public function setTelephoneResponsable(?string $telephoneResponsable): self
    {
        $this->telephoneResponsable = $telephoneResponsable;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getNomTuteur(): ?string
    {
        return $this->nomTuteur;
    }

    public function setNomTuteur(?string $nomTuteur): self
    {
        $this->nomTuteur = $nomTuteur;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getFonctionTuteur(): ?string
    {
        return $this->fonctionTuteur;
    }

    public function setFonctionTuteur(?string $fonctionTuteur): self
    {
        $this->fonctionTuteur = $fonctionTuteur;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getTelephoneTuteur(): ?string
    {
        return $this->telephoneTuteur;
    }

    public function setTelephoneTuteur(?string $telephoneTuteur): self
    {
        $this->telephoneTuteur = $telephoneTuteur;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getEmailTuteur(): ?string
    {
        return $this->emailTuteur;
    }

    public function setEmailTuteur(?string $emailTuteur): self
    {
        $this->emailTuteur = $emailTuteur;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getLogoFichier(): ?string
    {
        return $this->logoFichier;
    }

    public function setLogoFichier(?string $logoFichier): self
    {
        $this->logoFichier = $logoFichier;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCachetFichier(): ?string
    {
        return $this->cachetFichier;
    }

    public function setCachetFichier(?string $cachetFichier): self
    {
        $this->cachetFichier = $cachetFichier;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}