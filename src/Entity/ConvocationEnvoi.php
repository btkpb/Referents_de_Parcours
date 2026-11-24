<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'convocation_envoi')]
#[ORM\Index(name: 'IDX_CONVOCATION_ENVOI_ENTRETIEN', columns: ['entretien_id'])]
#[ORM\Index(name: 'IDX_CONVOCATION_ENVOI_CANDIDAT', columns: ['candidat_id'])]
#[ORM\Index(name: 'IDX_CONVOCATION_ENVOI_STATUT', columns: ['statut'])]
class ConvocationEnvoi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Entretien::class)]
    #[ORM\JoinColumn(
        name: 'entretien_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ?Entretien $entretien = null;

    #[ORM\ManyToOne(targetEntity: Candidat::class)]
    #[ORM\JoinColumn(
        name: 'candidat_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ?Candidat $candidat = null;

    #[ORM\Column(length: 20)]
    private string $canal = 'EMAIL';

    #[ORM\Column(length: 254)]
    private string $destinataire = '';

    #[ORM\Column(length: 30)]
    private string $statut = 'EN_ATTENTE';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $referenceExterne = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $erreur = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dateTentative;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateEnvoi = null;

    public function __construct()
    {
        $this->dateTentative = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntretien(): ?Entretien
    {
        return $this->entretien;
    }

    public function setEntretien(?Entretien $entretien): self
    {
        $this->entretien = $entretien;
        return $this;
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

    public function getCanal(): string
    {
        return $this->canal;
    }

    public function setCanal(string $canal): self
    {
        $this->canal = $canal;
        return $this;
    }

    public function getDestinataire(): string
    {
        return $this->destinataire;
    }

    public function setDestinataire(string $destinataire): self
    {
        $this->destinataire = trim($destinataire);
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

    public function getReferenceExterne(): ?string
    {
        return $this->referenceExterne;
    }

    public function setReferenceExterne(?string $referenceExterne): self
    {
        $this->referenceExterne = $referenceExterne;
        return $this;
    }

    public function getErreur(): ?string
    {
        return $this->erreur;
    }

    public function setErreur(?string $erreur): self
    {
        $this->erreur = $erreur;
        return $this;
    }

    public function getDateTentative(): \DateTimeImmutable
    {
        return $this->dateTentative;
    }

    public function getDateEnvoi(): ?\DateTimeImmutable
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(?\DateTimeImmutable $dateEnvoi): self
    {
        $this->dateEnvoi = $dateEnvoi;
        return $this;
    }
}