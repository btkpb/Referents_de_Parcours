<?php

namespace App\Entity;

use App\Repository\ContratStageSignatureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratStageSignatureRepository::class)]
#[ORM\Table(
    name: 'contrat_stage_signature',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'UNIQ_CONTRAT_SIGNATURE_VERSION_TYPE',
            columns: ['version_id', 'type_signataire']
        ),
    ],
    indexes: [
        new ORM\Index(name: 'IDX_CONTRAT_SIGNATURE_USER', columns: ['user_id']),
        new ORM\Index(name: 'IDX_CONTRAT_SIGNATURE_VERIF', columns: ['verifie_par_id']),
    ]
)]
class ContratStageSignature
{
    public const ENTREPRISE = 'ENTREPRISE';
    public const STAGIAIRE = 'STAGIAIRE';
    public const ETABLISSEMENT = 'ETABLISSEMENT';

    public const MODE_ELECTRONIQUE = 'ELECTRONIQUE';
    public const MODE_MANUSCRITE = 'MANUSCRITE';

    public const EN_ATTENTE = 'EN_ATTENTE';
    public const A_SIGNER = 'A_SIGNER';
    public const DEPOSEE_A_VERIFIER = 'DEPOSEE_A_VERIFIER';
    public const SIGNEE = 'SIGNEE';
    public const REFUSEE = 'REFUSEE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContratStageVersion::class)]
    #[ORM\JoinColumn(
        name: 'version_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ?ContratStageVersion $version = null;

    #[ORM\Column(name: 'type_signataire', length: 20)]
    private ?string $typeSignataire = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'user_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $user = null;

    #[ORM\Column(name: 'signataire_nom', length: 160)]
    private ?string $signataireNom = null;

    #[ORM\Column(name: 'signataire_fonction', length: 120, nullable: true)]
    private ?string $signataireFonction = null;

    #[ORM\Column(name: 'signataire_email', length: 180, nullable: true)]
    private ?string $signataireEmail = null;

    #[ORM\Column(name: 'signataire_telephone', length: 30, nullable: true)]
    private ?string $signataireTelephone = null;

    #[ORM\Column(name: 'mode_signature', length: 20, nullable: true)]
    private ?string $modeSignature = null;

    #[ORM\Column(length: 30, options: ['default' => 'EN_ATTENTE'])]
    private string $statut = self::EN_ATTENTE;

    #[ORM\Column(name: 'signe_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $signeLe = null;

    #[ORM\Column(name: 'preuve_path', length: 500, nullable: true)]
    private ?string $preuvePath = null;

    #[ORM\Column(name: 'preuve_hash', length: 64, nullable: true)]
    private ?string $preuveHash = null;

    #[ORM\Column(name: 'reference_authentification', length: 255, nullable: true)]
    private ?string $referenceAuthentification = null;

    #[ORM\Column(name: 'motif_refus', type: 'text', nullable: true)]
    private ?string $motifRefus = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(
        name: 'verifie_par_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?User $verifiePar = null;

    #[ORM\Column(name: 'verifie_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $verifieLe = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): ?ContratStageVersion
    {
        return $this->version;
    }

    public function setVersion(?ContratStageVersion $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function getTypeSignataire(): ?string
    {
        return $this->typeSignataire;
    }

    public function setTypeSignataire(string $typeSignataire): self
    {
        if (!in_array($typeSignataire, [
            self::ENTREPRISE,
            self::STAGIAIRE,
            self::ETABLISSEMENT,
        ], true)) {
            throw new \InvalidArgumentException('Type de signataire invalide.');
        }

        $this->typeSignataire = $typeSignataire;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getSignataireNom(): ?string
    {
        return $this->signataireNom;
    }

    public function setSignataireNom(string $signataireNom): self
    {
        $this->signataireNom = trim($signataireNom);
        return $this;
    }

    public function getSignataireFonction(): ?string
    {
        return $this->signataireFonction;
    }

    public function setSignataireFonction(?string $signataireFonction): self
    {
        $this->signataireFonction = $signataireFonction;
        return $this;
    }

    public function getSignataireEmail(): ?string
    {
        return $this->signataireEmail;
    }

    public function setSignataireEmail(?string $signataireEmail): self
    {
        $this->signataireEmail = $signataireEmail;
        return $this;
    }

    public function getSignataireTelephone(): ?string
    {
        return $this->signataireTelephone;
    }

    public function setSignataireTelephone(?string $signataireTelephone): self
    {
        $this->signataireTelephone = $signataireTelephone;
        return $this;
    }

    public function getModeSignature(): ?string
    {
        return $this->modeSignature;
    }

    public function setModeSignature(?string $modeSignature): self
    {
        if ($modeSignature !== null && !in_array($modeSignature, [
            self::MODE_ELECTRONIQUE,
            self::MODE_MANUSCRITE,
        ], true)) {
            throw new \InvalidArgumentException('Mode de signature invalide.');
        }

        $this->modeSignature = $modeSignature;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, [
            self::EN_ATTENTE,
            self::A_SIGNER,
            self::DEPOSEE_A_VERIFIER,
            self::SIGNEE,
            self::REFUSEE,
        ], true)) {
            throw new \InvalidArgumentException('Statut de signature invalide.');
        }

        $this->statut = $statut;
        return $this;
    }

    public function getSigneLe(): ?\DateTimeImmutable
    {
        return $this->signeLe;
    }

    public function setSigneLe(?\DateTimeImmutable $signeLe): self
    {
        $this->signeLe = $signeLe;
        return $this;
    }

    public function getPreuvePath(): ?string
    {
        return $this->preuvePath;
    }

    public function setPreuvePath(?string $preuvePath): self
    {
        $this->preuvePath = $preuvePath;
        return $this;
    }

    public function getPreuveHash(): ?string
    {
        return $this->preuveHash;
    }

    public function setPreuveHash(?string $preuveHash): self
    {
        $this->preuveHash = $preuveHash;
        return $this;
    }

    public function getReferenceAuthentification(): ?string
    {
        return $this->referenceAuthentification;
    }

    public function setReferenceAuthentification(?string $referenceAuthentification): self
    {
        $this->referenceAuthentification = $referenceAuthentification;
        return $this;
    }

    public function getMotifRefus(): ?string
    {
        return $this->motifRefus;
    }

    public function setMotifRefus(?string $motifRefus): self
    {
        $this->motifRefus = $motifRefus;
        return $this;
    }

    public function getVerifiePar(): ?User
    {
        return $this->verifiePar;
    }

    public function setVerifiePar(?User $verifiePar): self
    {
        $this->verifiePar = $verifiePar;
        return $this;
    }

    public function getVerifieLe(): ?\DateTimeImmutable
    {
        return $this->verifieLe;
    }

    public function setVerifieLe(?\DateTimeImmutable $verifieLe): self
    {
        $this->verifieLe = $verifieLe;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
