<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContratStageHistoriqueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratStageHistoriqueRepository::class)]
#[ORM\Table(
    name: 'contrat_stage_historique',
    indexes: [
        new ORM\Index(name: 'IDX_CONTRAT_HISTORIQUE_DOSSIER', columns: ['contrat_id']),
        new ORM\Index(name: 'IDX_CONTRAT_HISTORIQUE_VERSION', columns: ['version_id']),
        new ORM\Index(name: 'IDX_CONTRAT_HISTORIQUE_USER', columns: ['user_id']),
        new ORM\Index(name: 'IDX_CONTRAT_HISTORIQUE_DATE', columns: ['created_at']),
        new ORM\Index(name: 'FK_CONTRAT_HISTORIQUE_VERSION', columns: ['version_id', 'contrat_id']),
    ]
)]
class ContratStageHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: ContratStage::class)]
    #[ORM\JoinColumn(name: 'contrat_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?ContratStage $contrat = null;

    /**
     * Identifiant de version conservé en scalaire car la table utilise
     * une clé étrangère composite (version_id, contrat_id).
     */
    #[ORM\Column(name: 'version_id', type: 'integer', nullable: true)]
    private ?int $versionId = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(name: 'acteur_nom', length: 160, nullable: true)]
    private ?string $acteurNom = null;

    #[ORM\Column(name: 'acteur_fonction', length: 120, nullable: true)]
    private ?string $acteurFonction = null;

    #[ORM\Column(length: 80)]
    private string $action;

    #[ORM\Column(name: 'ancien_statut', length: 30, nullable: true)]
    private ?string $ancienStatut = null;

    #[ORM\Column(name: 'nouveau_statut', length: 30, nullable: true)]
    private ?string $nouveauStatut = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'donnees_json', type: 'text', nullable: true)]
    private ?string $donneesJson = null;

    #[ORM\Column(name: 'adresse_ip', length: 45, nullable: true)]
    private ?string $adresseIp = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(ContratStage $contrat, string $action)
    {
        $this->contrat = $contrat;
        $this->action = $action;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getContrat(): ?ContratStage
    {
        return $this->contrat;
    }

    public function setContrat(ContratStage $contrat): self
    {
        $this->contrat = $contrat;
        return $this;
    }

    public function getVersionId(): ?int
    {
        return $this->versionId;
    }

    public function setVersionId(?int $versionId): self
    {
        $this->versionId = $versionId;
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

    public function getActeurNom(): ?string
    {
        return $this->acteurNom;
    }

    public function setActeurNom(?string $acteurNom): self
    {
        $this->acteurNom = $acteurNom;
        return $this;
    }

    public function getActeurFonction(): ?string
    {
        return $this->acteurFonction;
    }

    public function setActeurFonction(?string $acteurFonction): self
    {
        $this->acteurFonction = $acteurFonction;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getAncienStatut(): ?string
    {
        return $this->ancienStatut;
    }

    public function setAncienStatut(?string $ancienStatut): self
    {
        $this->ancienStatut = $ancienStatut;
        return $this;
    }

    public function getNouveauStatut(): ?string
    {
        return $this->nouveauStatut;
    }

    public function setNouveauStatut(?string $nouveauStatut): self
    {
        $this->nouveauStatut = $nouveauStatut;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDonneesJson(): ?string
    {
        return $this->donneesJson;
    }

    public function setDonneesJson(?string $donneesJson): self
    {
        if ($donneesJson !== null) {
            json_decode($donneesJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Le contenu fourni dans donneesJson doit être un JSON valide.');
            }
        }

        $this->donneesJson = $donneesJson;
        return $this;
    }

    public function getAdresseIp(): ?string
    {
        return $this->adresseIp;
    }

    public function setAdresseIp(?string $adresseIp): self
    {
        $this->adresseIp = $adresseIp;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}