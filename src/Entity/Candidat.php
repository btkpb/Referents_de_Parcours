<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'candidat')]
#[ORM\Index(name: 'IDX_93C3D62716227374', columns: ['campagne_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_93C3D627F55AE19E', columns: ['numero'])]
#[ORM\HasLifecycleCallbacks]
class Candidat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $numero = '';

    #[ORM\Column(length: 100)]
    private string $nom = '';

    #[ORM\Column(length: 160)]
    private string $prenoms = '';

    #[ORM\Column(length: 10)]
    private string $sexe = '';

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $lieuNaissance = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $lieuResidence = null;

    #[ORM\Column(length: 100)]
    private string $nationalite = 'Ivoirienne';

    #[ORM\Column(length: 30)]
    private string $telephone = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephoneAlt = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 40)]
    private string $pieceIdentite = 'Aucun';

    #[ORM\Column(name: 'numero_piece_identite', length: 100, nullable: true)]
    private ?string $numeroPieceIdentite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pieceIdentiteFichier = null;

    #[ORM\Column(length: 40)]
    private string $niveau = '';

    #[ORM\Column(length: 50)]
    private string $diplome = 'Aucun';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplomeFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoIdentite = null;

    #[ORM\Column(length: 80)]
    private string $metier = '';

    #[ORM\Column(length: 80)]
    private string $ancienMetier = 'Aucun';

    #[ORM\Column(length: 30)]
    private string $disponibilite = 'Oui';

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $disponibleLe = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $contactUrgenceNom = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $contactUrgenceTel = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $contactUrgenceLien = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 30)]
    private string $statut = 'INSCRIT';

    #[ORM\ManyToOne]
    private ?Campagne $campagne = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->numero = 'CAND-' . date('YmdHis') . '-' . random_int(100, 999);
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $v): self
    {
        $this->nom = $v;
        return $this;
    }

    public function getPrenoms(): string
    {
        return $this->prenoms;
    }

    public function setPrenoms(string $v): self
    {
        $this->prenoms = $v;
        return $this;
    }

    public function getSexe(): string
    {
        return $this->sexe;
    }

    public function setSexe(string $v): self
    {
        $this->sexe = $v;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $v): self
    {
        $this->dateNaissance = $v;
        return $this;
    }

    public function getLieuNaissance(): ?string
    {
        return $this->lieuNaissance;
    }

    public function setLieuNaissance(?string $v): self
    {
        $this->lieuNaissance = $v;
        return $this;
    }

    public function getLieuResidence(): ?string
    {
        return $this->lieuResidence;
    }

    public function setLieuResidence(?string $v): self
    {
        $this->lieuResidence = $v;
        return $this;
    }

    public function getNationalite(): string
    {
        return $this->nationalite;
    }

    public function setNationalite(string $v): self
    {
        $this->nationalite = $v;
        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $v): self
    {
        $this->telephone = $v;
        return $this;
    }

    public function getTelephoneAlt(): ?string
    {
        return $this->telephoneAlt;
    }

    public function setTelephoneAlt(?string $v): self
    {
        $this->telephoneAlt = $v;
        return $this;
    }

    public function getPieceIdentite(): string
    {
        return $this->pieceIdentite;
    }

    public function setPieceIdentite(string $v): self
    {
        $this->pieceIdentite = $v;
        return $this;
    }

    public function getNumeroPieceIdentite(): ?string
    {
        return $this->numeroPieceIdentite;
    }

    public function setNumeroPieceIdentite(?string $numeroPieceIdentite): self
    {
        $this->numeroPieceIdentite = $numeroPieceIdentite !== null
            && trim($numeroPieceIdentite) !== ''
                ? trim($numeroPieceIdentite)
                : null;

        return $this;
    }

    public function getPieceIdentiteFichier(): ?string
    {
        return $this->pieceIdentiteFichier;
    }

    public function setPieceIdentiteFichier(?string $v): self
    {
        $this->pieceIdentiteFichier = $v;
        return $this;
    }

    public function getNiveau(): string
    {
        return $this->niveau;
    }

    public function setNiveau(string $v): self
    {
        $this->niveau = $v;
        return $this;
    }

    public function getDiplome(): string
    {
        return $this->diplome;
    }

    public function setDiplome(string $v): self
    {
        $this->diplome = $v;
        return $this;
    }

    public function getDiplomeFichier(): ?string
    {
        return $this->diplomeFichier;
    }

    public function setDiplomeFichier(?string $v): self
    {
        $this->diplomeFichier = $v;
        return $this;
    }

    public function getPhotoIdentite(): ?string
    {
        return $this->photoIdentite;
    }

    public function setPhotoIdentite(?string $v): self
    {
        $this->photoIdentite = $v;
        return $this;
    }

    public function getMetier(): string
    {
        return $this->metier;
    }

    public function setMetier(string $v): self
    {
        $this->metier = $v;
        return $this;
    }

    public function getAncienMetier(): string
    {
        return $this->ancienMetier;
    }

    public function setAncienMetier(string $v): self
    {
        $this->ancienMetier = $v;
        return $this;
    }

    public function getDisponibilite(): string
    {
        return $this->disponibilite;
    }

    public function setDisponibilite(string $v): self
    {
        $this->disponibilite = $v;
        return $this;
    }

    public function getDisponibleLe(): ?\DateTimeInterface
    {
        return $this->disponibleLe;
    }

    public function setDisponibleLe(?\DateTimeInterface $v): self
    {
        $this->disponibleLe = $v;
        return $this;
    }

    public function getContactUrgenceNom(): ?string
    {
        return $this->contactUrgenceNom;
    }

    public function setContactUrgenceNom(?string $v): self
    {
        $this->contactUrgenceNom = $v;
        return $this;
    }

    public function getContactUrgenceTel(): ?string
    {
        return $this->contactUrgenceTel;
    }

    public function setContactUrgenceTel(?string $v): self
    {
        $this->contactUrgenceTel = $v;
        return $this;
    }

    public function getContactUrgenceLien(): ?string
    {
        return $this->contactUrgenceLien;
    }

    public function setContactUrgenceLien(?string $v): self
    {
        $this->contactUrgenceLien = $v;
        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $v): self
    {
        $this->experience = $v;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $v): self
    {
        $this->statut = $v;
        return $this;
    }

    public function getCampagne(): ?Campagne
    {
        return $this->campagne;
    }

    public function setCampagne(?Campagne $v): self
    {
        $this->campagne = $v;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $v): self
    {
        $this->email = $v !== null && trim($v) !== ''
            ? trim($v)
            : null;

        return $this;
    }
    public function getAge(): ?int
    {
        return $this->dateNaissance
            ? (new \DateTimeImmutable())->diff($this->dateNaissance)->y
            : null;
    }
}
