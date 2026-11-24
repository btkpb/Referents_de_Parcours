<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'document_apprenti',
    indexes: [
        new ORM\Index(
            name: 'idx_document_apprenti_candidat',
            columns: ['candidat_id']
        )
    ]
)]
class DocumentApprenti
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Candidat::class)]
    #[ORM\JoinColumn(name: 'candidat_id', nullable: false, onDelete: 'CASCADE')]
    private ?Candidat $candidat = null;

    #[ORM\Column(length: 40)]
    private string $categorie = 'autre';

    #[ORM\Column(length: 180)]
    private string $intitule = '';

    #[ORM\Column(name: 'nom_original', length: 255)]
    private string $nomOriginal = '';

    #[ORM\Column(name: 'nom_stockage', length: 255)]
    private string $nomStockage = '';

    #[ORM\Column(name: 'mime_type', length: 120)]
    private string $mimeType = '';

    #[ORM\Column(name: 'taille_octets', type: 'integer')]
    private int $tailleOctets = 0;

    #[ORM\Column(name: 'date_ajout', type: 'datetime_immutable')]
    private ?\DateTimeImmutable $dateAjout = null;

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

    public function getCategorie(): string
    {
        return $this->categorie;
    }

    public function setCategorie(string $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getIntitule(): string
    {
        return $this->intitule;
    }

    public function setIntitule(string $intitule): self
    {
        $this->intitule = $intitule;
        return $this;
    }

    public function getNomOriginal(): string
    {
        return $this->nomOriginal;
    }

    public function setNomOriginal(string $nomOriginal): self
    {
        $this->nomOriginal = $nomOriginal;
        return $this;
    }

    public function getNomStockage(): string
    {
        return $this->nomStockage;
    }

    public function setNomStockage(string $nomStockage): self
    {
        $this->nomStockage = $nomStockage;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getTailleOctets(): int
    {
        return $this->tailleOctets;
    }

    public function setTailleOctets(int $tailleOctets): self
    {
        $this->tailleOctets = $tailleOctets;
        return $this;
    }

    public function getDateAjout(): ?\DateTimeImmutable
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeImmutable $dateAjout): self
    {
        $this->dateAjout = $dateAjout;
        return $this;
    }
}