<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Entretien
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Candidat $candidat = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateHeure = null;

    #[ORM\Column(length: 180)]
    private string $lieu = '';

    #[ORM\Column]
    private int $nombreJury = 1;

    #[ORM\Column(length: 30)]
    private string $statut = 'PLANIFIE';

    #[ORM\Column(nullable: true)]
    private ?string $juryNoms = null;

    #[ORM\Column(nullable: true)]
    private ?string $convocationEnvoyeeAt = null;

    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(
        name: 'entretien_jury',
        joinColumns: [
            new ORM\JoinColumn(
                name: 'entretien_id',
                referencedColumnName: 'id',
                onDelete: 'CASCADE'
            )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'jury_id',
                referencedColumnName: 'id',
                onDelete: 'CASCADE'
            )
        ]
    )]
    private Collection $jurys;

    public function __construct()
    {
        $this->jurys = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCandidat(): ?Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(?Candidat $candidat): self
    {
        $this->candidat = $candidat;
        return $this;
    }

    public function getDateHeure(): ?\DateTimeInterface
    {
        return $this->dateHeure;
    }

    public function setDateHeure(?\DateTimeInterface $dateHeure): self
    {
        $this->dateHeure = $dateHeure;
        return $this;
    }

    public function getLieu(): string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getNombreJury(): int
    {
        return $this->nombreJury;
    }

    public function setNombreJury(int $nombreJury): self
    {
        if ($nombreJury < 1) {
            throw new \InvalidArgumentException(
                'Un entretien doit prévoir au moins un juré.'
            );
        }

        $this->nombreJury = $nombreJury;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getJuryNoms(): ?string
    {
        return $this->juryNoms;
    }

    public function setJuryNoms(?string $juryNoms): self
    {
        $this->juryNoms = $juryNoms;
        return $this;
    }

    public function getConvocationEnvoyeeAt(): ?string
    {
        return $this->convocationEnvoyeeAt;
    }

    public function setConvocationEnvoyeeAt(?string $date): self
    {
        $this->convocationEnvoyeeAt = $date;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getJurys(): Collection
    {
        return $this->jurys;
    }

    public function addJury(User $jury): self
    {
        if (!$this->jurys->contains($jury)) {
            $this->jurys->add($jury);
            $this->synchroniserInformationsJury();
        }

        return $this;
    }

    public function removeJury(User $jury): self
    {
        if ($this->jurys->removeElement($jury)) {
            $this->synchroniserInformationsJury();
        }

        return $this;
    }

    private function synchroniserInformationsJury(): void
    {
        $noms = [];

        foreach ($this->jurys as $jury) {
            $nomComplet = trim($jury->getPrenoms() . ' ' . $jury->getNom());

            if ($nomComplet !== '') {
                $noms[] = $nomComplet;
            }
        }

        $this->nombreJury = $this->jurys->count();
        $this->juryNoms = $noms !== [] ? implode(', ', $noms) : null;
    }
}