<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EvaluationEntrepriseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: EvaluationEntrepriseRepository::class)]
#[ORM\Table(name: 'evaluation_entreprise')]
#[ORM\HasLifecycleCallbacks]
class EvaluationEntreprise
{
    public const STATUT_BROUILLON = 'BROUILLON';
    public const STATUT_TRANSMISE = 'TRANSMISE';
    public const STATUT_SIGNEE_REFERENT = 'SIGNEE_REFERENT';
    public const STATUT_ARCHIVEE = 'ARCHIVEE';

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_TRANSMISE,
        self::STATUT_SIGNEE_REFERENT,
        self::STATUT_ARCHIVEE,
    ];

    public const BILANS = [
        'TRES_SATISFAISANT',
        'SATISFAISANT',
        'MOYENNEMENT_SATISFAISANT',
        'PAS_SATISFAISANT',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Candidat::class)]
    #[ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?Candidat $candidat = null;

    #[ORM\ManyToOne(targetEntity: AffectationEntrepriseApprenti::class)]
    #[ORM\JoinColumn(name: 'affectation_entreprise_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?AffectationEntrepriseApprenti $affectationEntreprise = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'maitre_stage_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $maitreStage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'referent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $referent = null;

    // Informations historiques : elles restent conservées même si
    // les coordonnées du candidat ou de l'entreprise changent.
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $nomApprenti = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $prenomsApprenti = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $telephoneApprenti = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $metier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $intituleProjet = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $nomReferent = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $nomCentre = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $nomFormateur = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $telephoneFormateur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomEntreprise = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $nomResponsable = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $nomTuteur = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $fonctionTuteur = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $telephoneTuteur = null;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $dateSuivi;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $periodeSuiviDebut = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $periodeSuiviFin = null;

    #[ORM\OneToMany(
        mappedBy: 'evaluation',
        targetEntity: EvaluationCompetenceEntreprise::class,
        cascade: ['persist'],
        orphanRemoval: false
    )]
    private Collection $competencesTechniques;

    #[ORM\OneToMany(
        mappedBy: 'evaluation',
        targetEntity: EvaluationComportementEntreprise::class,
        cascade: ['persist'],
        orphanRemoval: false
    )]
    private Collection $evaluationsComportementales;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $pointsForts = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $pointsAAmeliorer = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $bilanGeneral = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $avisMaitreStage = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $lieuSignature = null;

    #[ORM\Column(length: 30)]
    private string $statut = self::STATUT_BROUILLON;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $signatureMaitreNom = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $signatureMaitreDate = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $signatureReferentNom = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $signatureReferentDate = null;

    // Chemins de stockage privé uniquement : aucun document sensible
    // ne doit être placé dans le répertoire public.
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFichier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $scanSigneFichier = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->dateSuivi = new DateTimeImmutable();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->competencesTechniques = new ArrayCollection();
        $this->evaluationsComportementales = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function actualiserDateModification(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCandidat(): ?Candidat { return $this->candidat; }
    public function setCandidat(Candidat $candidat): self { $this->candidat = $candidat; return $this; }

    public function getAffectationEntreprise(): ?AffectationEntrepriseApprenti { return $this->affectationEntreprise; }
    public function setAffectationEntreprise(AffectationEntrepriseApprenti $affectation): self { $this->affectationEntreprise = $affectation; return $this; }

    public function getMaitreStage(): ?User { return $this->maitreStage; }
    public function setMaitreStage(User $maitreStage): self { $this->maitreStage = $maitreStage; return $this; }

    public function getReferent(): ?User { return $this->referent; }
    public function setReferent(?User $referent): self { $this->referent = $referent; return $this; }

    public function getNomApprenti(): ?string { return $this->nomApprenti; }
    public function setNomApprenti(?string $v): self { $this->nomApprenti = $v; return $this; }

    public function getPrenomsApprenti(): ?string { return $this->prenomsApprenti; }
    public function setPrenomsApprenti(?string $v): self { $this->prenomsApprenti = $v; return $this; }

    public function getTelephoneApprenti(): ?string { return $this->telephoneApprenti; }
    public function setTelephoneApprenti(?string $v): self { $this->telephoneApprenti = $v; return $this; }

    public function getMetier(): ?string { return $this->metier; }
    public function setMetier(?string $v): self { $this->metier = $v; return $this; }

    public function getIntituleProjet(): ?string { return $this->intituleProjet; }
    public function setIntituleProjet(?string $v): self { $this->intituleProjet = $v; return $this; }

    public function getNomReferent(): ?string { return $this->nomReferent; }
    public function setNomReferent(?string $v): self { $this->nomReferent = $v; return $this; }

    public function getNomCentre(): ?string { return $this->nomCentre; }
    public function setNomCentre(?string $v): self { $this->nomCentre = $v; return $this; }

    public function getNomFormateur(): ?string { return $this->nomFormateur; }
    public function setNomFormateur(?string $v): self { $this->nomFormateur = $v; return $this; }

    public function getTelephoneFormateur(): ?string { return $this->telephoneFormateur; }
    public function setTelephoneFormateur(?string $v): self { $this->telephoneFormateur = $v; return $this; }

    public function getNomEntreprise(): ?string { return $this->nomEntreprise; }
    public function setNomEntreprise(?string $v): self { $this->nomEntreprise = $v; return $this; }

    public function getNomResponsable(): ?string { return $this->nomResponsable; }
    public function setNomResponsable(?string $v): self { $this->nomResponsable = $v; return $this; }

    public function getNomTuteur(): ?string { return $this->nomTuteur; }
    public function setNomTuteur(?string $v): self { $this->nomTuteur = $v; return $this; }

    public function getFonctionTuteur(): ?string { return $this->fonctionTuteur; }
    public function setFonctionTuteur(?string $v): self { $this->fonctionTuteur = $v; return $this; }

    public function getTelephoneTuteur(): ?string { return $this->telephoneTuteur; }
    public function setTelephoneTuteur(?string $v): self { $this->telephoneTuteur = $v; return $this; }

    public function getDateSuivi(): DateTimeImmutable { return $this->dateSuivi; }
    public function setDateSuivi(DateTimeImmutable $v): self { $this->dateSuivi = $v; return $this; }

    public function getPeriodeSuiviDebut(): ?DateTimeImmutable { return $this->periodeSuiviDebut; }
    public function setPeriodeSuiviDebut(?DateTimeImmutable $v): self { $this->periodeSuiviDebut = $v; return $this; }

    public function getPeriodeSuiviFin(): ?DateTimeImmutable { return $this->periodeSuiviFin; }
    public function setPeriodeSuiviFin(?DateTimeImmutable $v): self { $this->periodeSuiviFin = $v; return $this; }

    public function getCompetencesTechniques(): Collection { return $this->competencesTechniques; }

    public function addCompetenceTechnique(EvaluationCompetenceEntreprise $competence): self
    {
        if (!$this->competencesTechniques->contains($competence)) {
            $this->competencesTechniques->add($competence);
            $competence->setEvaluation($this);
        }
        return $this;
    }

    public function getEvaluationsComportementales(): Collection { return $this->evaluationsComportementales; }

    public function addEvaluationComportementale(EvaluationComportementEntreprise $evaluation): self
    {
        if (!$this->evaluationsComportementales->contains($evaluation)) {
            $this->evaluationsComportementales->add($evaluation);
            $evaluation->setEvaluation($this);
        }
        return $this;
    }

    public function getPointsForts(): ?string { return $this->pointsForts; }
    public function setPointsForts(?string $v): self { $this->pointsForts = $v; return $this; }

    public function getPointsAAmeliorer(): ?string { return $this->pointsAAmeliorer; }
    public function setPointsAAmeliorer(?string $v): self { $this->pointsAAmeliorer = $v; return $this; }

    public function getBilanGeneral(): ?string { return $this->bilanGeneral; }

    public function setBilanGeneral(?string $v): self
    {
        if ($v !== null && !in_array($v, self::BILANS, true)) {
            throw new \InvalidArgumentException('Bilan général non autorisé.');
        }
        $this->bilanGeneral = $v;
        return $this;
    }

    public function getAvisMaitreStage(): ?string { return $this->avisMaitreStage; }
    public function setAvisMaitreStage(?string $v): self { $this->avisMaitreStage = $v; return $this; }

    public function getLieuSignature(): ?string { return $this->lieuSignature; }
    public function setLieuSignature(?string $v): self { $this->lieuSignature = $v; return $this; }

    public function getStatut(): string { return $this->statut; }

    public function setStatut(string $v): self
    {
        if (!in_array($v, self::STATUTS, true)) {
            throw new \InvalidArgumentException('Statut d’évaluation non autorisé.');
        }
        $this->statut = $v;
        return $this;
    }

    public function getSignatureMaitreNom(): ?string { return $this->signatureMaitreNom; }
    public function setSignatureMaitreNom(?string $v): self { $this->signatureMaitreNom = $v; return $this; }

    public function getSignatureMaitreDate(): ?DateTimeImmutable { return $this->signatureMaitreDate; }
    public function setSignatureMaitreDate(?DateTimeImmutable $v): self { $this->signatureMaitreDate = $v; return $this; }

    public function getSignatureReferentNom(): ?string { return $this->signatureReferentNom; }
    public function setSignatureReferentNom(?string $v): self { $this->signatureReferentNom = $v; return $this; }

    public function getSignatureReferentDate(): ?DateTimeImmutable { return $this->signatureReferentDate; }
    public function setSignatureReferentDate(?DateTimeImmutable $v): self { $this->signatureReferentDate = $v; return $this; }

    public function getPdfFichier(): ?string { return $this->pdfFichier; }
    public function setPdfFichier(?string $v): self { $this->pdfFichier = $v; return $this; }

    public function getScanSigneFichier(): ?string { return $this->scanSigneFichier; }
    public function setScanSigneFichier(?string $v): self { $this->scanSigneFichier = $v; return $this; }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
}