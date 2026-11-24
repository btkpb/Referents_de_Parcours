<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'campagne')]
class Campagne
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $nom = '';

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(nullable: true)]
    private ?int $ageMin = 12;

    #[ORM\Column(nullable: true)]
    private ?int $ageMax = 35;

    #[ORM\Column(nullable: true)]
    private ?int $quotaGlobal = null;

    #[ORM\Column(name: 'NiveauRequis', type: 'text', length: 65535, nullable: true)]
    private ?string $niveauRequis = null;

    #[ORM\Column(type: 'text', length: 65535, nullable: true)]
    private ?string $texteCampagne = null;

    #[ORM\Column(length: 7, options: ['default' => '#5B9BD5'])]
    private string $couleurAccueil = '#5B9BD5';

    #[ORM\Column(name: 'DateCandidatures', type: 'text', length: 65535, nullable: true)]
    private ?string $dateCandidatures = null;

    #[ORM\Column(name: 'DateEtudeDossiers', type: 'text', length: 65535, nullable: true)]
    private ?string $dateEtudeDossiers = null;

    #[ORM\Column(name: 'DateSelection', type: 'text', length: 65535, nullable: true)]
    private ?string $dateSelection = null;

    #[ORM\Column(name: 'DateDebutFormations', type: 'text', length: 65535, nullable: true)]
    private ?string $dateDebutFormations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getAgeMin(): ?int
    {
        return $this->ageMin;
    }

    public function setAgeMin(?int $ageMin): self
    {
        $this->ageMin = $ageMin;
        return $this;
    }

    public function getAgeMax(): ?int
    {
        return $this->ageMax;
    }

    public function setAgeMax(?int $ageMax): self
    {
        $this->ageMax = $ageMax;
        return $this;
    }

    public function getQuotaGlobal(): ?int
    {
        return $this->quotaGlobal;
    }

    public function setQuotaGlobal(?int $quotaGlobal): self
    {
        $this->quotaGlobal = $quotaGlobal;
        return $this;
    }

    public function getNiveauRequis(): ?string
    {
        return $this->niveauRequis;
    }

    public function setNiveauRequis(?string $niveauRequis): self
    {
        $this->niveauRequis = $niveauRequis;
        return $this;
    }

    public function getTexteCampagne(): ?string
    {
        return $this->texteCampagne;
    }

    public function setTexteCampagne(?string $texteCampagne): self
    {
        $this->texteCampagne = $texteCampagne;
        return $this;
    }

    public function getCouleurAccueil(): string
    {
        return $this->couleurAccueil;
    }

    public function setCouleurAccueil(string $couleurAccueil): self
    {
        $this->couleurAccueil = $couleurAccueil;
        return $this;
    }
    public function getDateCandidatures(): ?string
    {
        return $this->dateCandidatures;
    }

    public function setDateCandidatures(?string $dateCandidatures): self
    {
        $this->dateCandidatures = $dateCandidatures;
        return $this;
    }

    public function getDateEtudeDossiers(): ?string
    {
        return $this->dateEtudeDossiers;
    }

    public function setDateEtudeDossiers(?string $dateEtudeDossiers): self
    {
        $this->dateEtudeDossiers = $dateEtudeDossiers;
        return $this;
    }

    public function getDateSelection(): ?string
    {
        return $this->dateSelection;
    }

    public function setDateSelection(?string $dateSelection): self
    {
        $this->dateSelection = $dateSelection;
        return $this;
    }

    public function getDateDebutFormations(): ?string
    {
        return $this->dateDebutFormations;
    }

    public function setDateDebutFormations(?string $dateDebutFormations): self
    {
        $this->dateDebutFormations = $dateDebutFormations;
        return $this;
    }
}
