<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'contrat_stage')]
#[ORM\UniqueConstraint(name: 'UNIQ_CONTRAT_STAGE_NUMERO', columns: ['numero_contrat'])]
#[ORM\UniqueConstraint(name: 'UNIQ_CONTRAT_STAGE_AFFECTATION', columns: ['affectation_id'])]
#[ORM\Index(name: 'IDX_CONTRAT_STAGE_CREATEUR', columns: ['cree_par_id'])]
#[ORM\Index(name: 'IDX_CONTRAT_STAGE_STATUT', columns: ['statut'])]
class ContratStage
{
    public const STATUT_BROUILLON = 'BROUILLON';
    public const STATUT_A_VALIDER = 'A_VALIDER';
    public const STATUT_EN_SIGNATURE = 'EN_SIGNATURE';
    public const STATUT_BLOQUE = 'BLOQUE';
    public const STATUT_SIGNE = 'SIGNE';
    public const STATUT_EN_COURS = 'EN_COURS';
    public const STATUT_TERMINE = 'TERMINE';
    public const STATUT_ROMPU = 'ROMPU';
    public const STATUT_ARCHIVE = 'ARCHIVE';
    public const STATUT_ANNULE = 'ANNULE';

    public const STATUTS_AUTORISES = [
        self::STATUT_BROUILLON,
        self::STATUT_A_VALIDER,
        self::STATUT_EN_SIGNATURE,
        self::STATUT_BLOQUE,
        self::STATUT_SIGNE,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINE,
        self::STATUT_ROMPU,
        self::STATUT_ARCHIVE,
        self::STATUT_ANNULE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'numero_contrat', length: 50)]
    private string $numeroContrat = '';

    #[ORM\OneToOne(targetEntity: AffectationEntrepriseApprenti::class)]
    #[ORM\JoinColumn(
        name: 'affectation_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ?AffectationEntrepriseApprenti $affectation = null;

    /**
     * Identifiant de la version courante.
     *
     * La base impose une clé étrangère composite
     * (version_courante_id, id) vers (id, contrat_id).
     * Le contrôle d'appartenance sera assuré par le service métier.
     */
    #[ORM\Column(name: 'version_courante_id', nullable: true)]
    private ?int $versionCouranteId = null;

    #[ORM\Column(name: 'statut', length: 30, options: ['default' => 'BROUILLON'])]
    private string $statut = self::STATUT_BROUILLON;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'cree_par_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $creePar = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $maintenant = new \DateTimeImmutable();

        $this->createdAt = $maintenant;
        $this->updatedAt = $maintenant;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroContrat(): string
    {
        return $this->numeroContrat;
    }

    public function setNumeroContrat(string $numeroContrat): self
    {
        $numeroContrat = trim($numeroContrat);

        if ($numeroContrat === '' || mb_strlen($numeroContrat) > 50) {
            throw new \InvalidArgumentException(
                'Le numéro de contrat est obligatoire et limité à 50 caractères.'
            );
        }

        $this->numeroContrat = $numeroContrat;

        return $this;
    }

    public function getAffectation(): ?AffectationEntrepriseApprenti
    {
        return $this->affectation;
    }

    public function setAffectation(
        ?AffectationEntrepriseApprenti $affectation
    ): self {
        $this->affectation = $affectation;

        return $this;
    }

    public function getVersionCouranteId(): ?int
    {
        return $this->versionCouranteId;
    }

    public function setVersionCouranteId(?int $versionCouranteId): self
    {
        if ($versionCouranteId !== null && $versionCouranteId <= 0) {
            throw new \InvalidArgumentException(
                'Identifiant de version courante invalide.'
            );
        }

        $this->versionCouranteId = $versionCouranteId;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, self::STATUTS_AUTORISES, true)) {
            throw new \InvalidArgumentException(
                'Statut de contrat invalide.'
            );
        }

        $this->statut = $statut;

        return $this;
    }

    public function getCreePar(): ?User
    {
        return $this->creePar;
    }

    public function setCreePar(?User $creePar): self
    {
        $this->creePar = $creePar;

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

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}