<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'import_beneficiaire_lot')]
#[ORM\Index(name: 'IDX_IMPORT_LOT_DATE', columns: ['date_import'])]
class ImportBeneficiaireLot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $programme = '';

    #[ORM\Column(length: 180)]
    private string $partenaire = '';

    #[ORM\Column(name: 'reference_lot', length: 100, nullable: true)]
    private ?string $referenceLot = null;

    #[ORM\Column(name: 'date_import', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateImport;

    #[ORM\Column(name: 'fichier_original', length: 255)]
    private string $fichierOriginal = '';

    #[ORM\Column(name: 'fichier_stocke', length: 255, nullable: true)]
    private ?string $fichierStocke = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'importe_par_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $importePar = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->dateImport = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramme(): string
    {
        return $this->programme;
    }

    public function setProgramme(string $programme): self
    {
        $this->programme = trim($programme);
        return $this;
    }

    public function getPartenaire(): string
    {
        return $this->partenaire;
    }

    public function setPartenaire(string $partenaire): self
    {
        $this->partenaire = trim($partenaire);
        return $this;
    }

    public function getReferenceLot(): ?string
    {
        return $this->referenceLot;
    }

    public function setReferenceLot(?string $referenceLot): self
    {
        $this->referenceLot = $referenceLot !== null && trim($referenceLot) !== ''
            ? trim($referenceLot)
            : null;

        return $this;
    }

    public function getDateImport(): \DateTimeImmutable
    {
        return $this->dateImport;
    }

    public function setDateImport(\DateTimeImmutable $dateImport): self
    {
        $this->dateImport = $dateImport;
        return $this;
    }

    public function getFichierOriginal(): string
    {
        return $this->fichierOriginal;
    }

    public function setFichierOriginal(string $fichierOriginal): self
    {
        $this->fichierOriginal = trim($fichierOriginal);
        return $this;
    }

    public function getFichierStocke(): ?string
    {
        return $this->fichierStocke;
    }

    public function setFichierStocke(?string $fichierStocke): self
    {
        $this->fichierStocke = $fichierStocke !== null && trim($fichierStocke) !== ''
            ? trim($fichierStocke)
            : null;

        return $this;
    }
    public function getImportePar(): ?User
    {
        return $this->importePar;
    }

    public function setImportePar(?User $importePar): self
    {
        $this->importePar = $importePar;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}