<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContratStageNotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratStageNotificationRepository::class)]
#[ORM\Table(
    name: 'contrat_stage_notification',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'UNIQ_CONTRAT_NOTIFICATION_CLE',
            columns: ['cle_idempotence']
        )
    ],
    indexes: [
        new ORM\Index(name: 'IDX_CONTRAT_NOTIFICATION_DOSSIER', columns: ['contrat_id']),
        new ORM\Index(name: 'IDX_CONTRAT_NOTIFICATION_VERSION', columns: ['version_id']),
        new ORM\Index(name: 'IDX_CONTRAT_NOTIFICATION_DEST', columns: ['destinataire_user_id']),
        new ORM\Index(name: 'IDX_CONTRAT_NOTIFICATION_AUTORISEUR', columns: ['autorise_par_id'])
    ]
)]
class ContratStageNotification
{
    public const TYPE_CONTRAT_A_SIGNER = 'CONTRAT_A_SIGNER';
    public const TYPE_RAPPEL_SIGNATURE = 'RAPPEL_SIGNATURE';
    public const TYPE_SIGNATURE_VALIDEE = 'SIGNATURE_VALIDEE';
    public const TYPE_SIGNATURE_REFUSEE = 'SIGNATURE_REFUSEE';
    public const TYPE_CONTRAT_SIGNE = 'CONTRAT_SIGNE';
    public const TYPE_CONTRAT_ANNULE = 'CONTRAT_ANNULE';
    public const TYPE_AVENANT_DISPONIBLE = 'AVENANT_DISPONIBLE';

    public const CANAL_EMAIL = 'EMAIL';
    public const CANAL_SMS = 'SMS';
    public const CANAL_INTERNE = 'INTERNE';

    public const STATUT_EN_ATTENTE_VALIDATION = 'EN_ATTENTE_VALIDATION';
    public const STATUT_AUTORISE = 'AUTORISE';
    public const STATUT_ENVOYE = 'ENVOYE';
    public const STATUT_ECHEC = 'ECHEC';
    public const STATUT_ANNULE = 'ANNULE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContratStage::class)]
    #[ORM\JoinColumn(name: 'contrat_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?ContratStage $contrat = null;

    // La contrainte composite SQL (version_id, contrat_id) reste gérée par la base.
    #[ORM\Column(name: 'version_id', type: 'integer', nullable: true)]
    private ?int $versionId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'destinataire_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $destinataireUser = null;

    #[ORM\Column(name: 'destinataire_nom', length: 160)]
    private string $destinataireNom;

    #[ORM\Column(name: 'destinataire_email', length: 180, nullable: true)]
    private ?string $destinataireEmail = null;

    #[ORM\Column(name: 'destinataire_telephone', length: 30, nullable: true)]
    private ?string $destinataireTelephone = null;

    #[ORM\Column(name: 'type_notification', length: 40)]
    private string $typeNotification;

    #[ORM\Column(length: 20)]
    private string $canal;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sujet = null;

    #[ORM\Column(type: 'text')]
    private string $contenu;

    #[ORM\Column(length: 30, options: ['default' => 'EN_ATTENTE_VALIDATION'])]
    private string $statut = self::STATUT_EN_ATTENTE_VALIDATION;

    #[ORM\Column(name: 'cle_idempotence', length: 120)]
    private string $cleIdempotence;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'autorise_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $autorisePar = null;

    #[ORM\Column(name: 'autorise_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $autoriseLe = null;

    #[ORM\Column(name: 'envoye_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $envoyeLe = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $erreur = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }

    public function getContrat(): ?ContratStage { return $this->contrat; }
    public function setContrat(ContratStage $contrat): self { $this->contrat = $contrat; return $this; }

    public function getVersionId(): ?int { return $this->versionId; }
    public function setVersionId(?int $versionId): self { $this->versionId = $versionId; return $this; }

    public function getDestinataireUser(): ?User { return $this->destinataireUser; }
    public function setDestinataireUser(?User $user): self { $this->destinataireUser = $user; return $this; }

    public function getDestinataireNom(): string { return $this->destinataireNom; }
    public function setDestinataireNom(string $nom): self { $this->destinataireNom = $nom; return $this; }

    public function getDestinataireEmail(): ?string { return $this->destinataireEmail; }
    public function setDestinataireEmail(?string $email): self { $this->destinataireEmail = $email; return $this; }

    public function getDestinataireTelephone(): ?string { return $this->destinataireTelephone; }
    public function setDestinataireTelephone(?string $telephone): self { $this->destinataireTelephone = $telephone; return $this; }

    public function getTypeNotification(): string { return $this->typeNotification; }
    public function setTypeNotification(string $type): self { $this->typeNotification = $type; return $this; }

    public function getCanal(): string { return $this->canal; }
    public function setCanal(string $canal): self { $this->canal = $canal; return $this; }

    public function getSujet(): ?string { return $this->sujet; }
    public function setSujet(?string $sujet): self { $this->sujet = $sujet; return $this; }

    public function getContenu(): string { return $this->contenu; }
    public function setContenu(string $contenu): self { $this->contenu = $contenu; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getCleIdempotence(): string { return $this->cleIdempotence; }
    public function setCleIdempotence(string $cle): self { $this->cleIdempotence = $cle; return $this; }

    public function getAutorisePar(): ?User { return $this->autorisePar; }
    public function setAutorisePar(?User $user): self { $this->autorisePar = $user; return $this; }

    public function getAutoriseLe(): ?\DateTimeImmutable { return $this->autoriseLe; }
    public function setAutoriseLe(?\DateTimeImmutable $date): self { $this->autoriseLe = $date; return $this; }

    public function getEnvoyeLe(): ?\DateTimeImmutable { return $this->envoyeLe; }
    public function setEnvoyeLe(?\DateTimeImmutable $date): self { $this->envoyeLe = $date; return $this; }

    public function getErreur(): ?string { return $this->erreur; }
    public function setErreur(?string $erreur): self { $this->erreur = $erreur; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $date): self { $this->createdAt = $date; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $date): self { $this->updatedAt = $date; return $this; }

    public function estEnAttenteValidation(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE_VALIDATION;
    }

    public function peutEtreEnvoyee(): bool
    {
        if ($this->statut !== self::STATUT_AUTORISE) {
            return false;
        }

        if ($this->canal === self::CANAL_EMAIL) {
            return $this->destinataireEmail !== null && trim($this->destinataireEmail) !== '';
        }

        if ($this->canal === self::CANAL_SMS) {
            return $this->destinataireTelephone !== null && trim($this->destinataireTelephone) !== '';
        }

        return $this->canal === self::CANAL_INTERNE;
    }
}