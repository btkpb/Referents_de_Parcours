<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'affectation_responsable_apprenti')]
#[ORM\Index(
    name: 'idx_affect_resp_candidat_type_statut',
    columns: ['candidat_id', 'type_responsabilite', 'statut']
)]
#[ORM\Index(
    name: 'idx_affect_resp_responsable',
    columns: ['responsable_id']
)]
#[ORM\HasLifecycleCallbacks]
class AffectationResponsableApprenti
{
    public const TYPE_REFERENT_PARCOURS = 'REFERENT_PARCOURS';
    public const TYPE_FORMATEUR = 'FORMATEUR';
    public const TYPE_MAITRE_STAGE = 'MAITRE_STAGE';

    public const STATUT_ACTIVE = 'ACTIVE';
    public const STATUT_TERMINEE = 'TERMINEE';
    public const STATUT_ANNULEE = 'ANNULEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Candidat::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Candidat $candidat = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $responsable = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private User $affectePar;

    #[ORM\Column(name: 'type_responsabilite', length: 30)]
    private string $typeResponsabilite = self::TYPE_REFERENT_PARCOURS;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $dateDebut;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_ACTIVE;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifFin = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $maintenant = new \DateTimeImmutable();

        $this->dateDebut = new \DateTime('today');
        $this->createdAt = $maintenant;
        $this->updatedAt = $maintenant;
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

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): self
    {
        $this->responsable = $responsable;
        return $this;
    }

    public function getAffectePar(): User
    {
        return $this->affectePar;
    }

    public function setAffectePar(User $affectePar): self
    {
        $this->affectePar = $affectePar;
        return $this;
    }

    public function getTypeResponsabilite(): string
    {
        return $this->typeResponsabilite;
    }

    public function setTypeResponsabilite(string $type): self
    {
        $typesAutorises = [
            self::TYPE_REFERENT_PARCOURS,
            self::TYPE_FORMATEUR,
            self::TYPE_MAITRE_STAGE,
        ];

        if (!in_array($type, $typesAutorises, true)) {
            throw new \InvalidArgumentException(
                'Type de responsabilite invalide.'
            );
        }

        $this->typeResponsabilite = $type;
        return $this;
    }

    public function getDateDebut(): \DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = \DateTime::createFromInterface($dateDebut);
        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin === null
            ? null
            : \DateTime::createFromInterface($dateFin);
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $statutsAutorises = [
            self::STATUT_ACTIVE,
            self::STATUT_TERMINEE,
            self::STATUT_ANNULEE,
        ];

        if (!in_array($statut, $statutsAutorises, true)) {
            throw new \InvalidArgumentException(
                'Statut d affectation invalide.'
            );
        }

        $this->statut = $statut;
        return $this;
    }

    public function getMotifFin(): ?string
    {
        return $this->motifFin;
    }

    public function setMotifFin(?string $motifFin): self
    {
        $this->motifFin = $motifFin;
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

    #[ORM\PreUpdate]
    public function mettreAJourDateModification(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function estActive(): bool
    {
        return $this->statut === self::STATUT_ACTIVE
            && $this->dateFin === null;
    }

    public function responsablePossedeRoleAttendu(): bool
    {
        if ($this->responsable === null) {
            return false;
        }

        $roles = $this->responsable->getRoles();

        return match ($this->typeResponsabilite) {
            self::TYPE_REFERENT_PARCOURS =>
                in_array('ROLE_REFERENT_PARCOURS', $roles, true)
                || in_array('ROLE_REFERENT', $roles, true),

            self::TYPE_FORMATEUR =>
                in_array('ROLE_FORMATEUR', $roles, true),

            self::TYPE_MAITRE_STAGE =>
                in_array('ROLE_MAITRE_STAGE', $roles, true),

            default => false,
        };
    }
}
