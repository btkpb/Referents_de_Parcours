<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'entretienparcours')]
#[ORM\Index(name: 'IDX_4766466B8D0EB82', columns: ['candidat_id'])]
class EntretienParcours
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Candidat $candidat = null;

    #[ORM\Column(length: 40)]
    private string $typeEntretien = 'ACCUEIL';

    #[ORM\Column(type: 'date', nullable: false)]
    private ?\DateTimeInterface $dateEntretien = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $synthese = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $planAction = null;

    #[ORM\Column(length: 30)]
    private string $statut = 'REALISE';

    /** Champs complémentaires propres aux formulaires d'accompagnement. */
    #[ORM\Column(type: 'json', nullable: true)]
    private array $details = [];

    public function getId(): ?int { return $this->id; }
    public function getCandidat(): ?Candidat { return $this->candidat; }
    public function setCandidat(?Candidat $v): self { $this->candidat = $v; return $this; }
    public function getTypeEntretien(): string { return $this->typeEntretien; }
    public function setTypeEntretien(string $v): self { $this->typeEntretien = $v; return $this; }
    public function getDateEntretien(): ?\DateTimeInterface { return $this->dateEntretien; }
    public function setDateEntretien(\DateTimeInterface $v): self { $this->dateEntretien = $v; return $this; }
    public function getSynthese(): ?string { return $this->synthese; }
    public function setSynthese(?string $v): self { $this->synthese = $v; return $this; }
    public function getPlanAction(): ?string { return $this->planAction; }
    public function setPlanAction(?string $v): self { $this->planAction = $v; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): self { $this->statut = $v; return $this; }
    public function getDetails(): array { return $this->details; }
    public function setDetails(array $v): self { $this->details = $v; return $this; }
}
