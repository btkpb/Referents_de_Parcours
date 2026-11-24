<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table]
#[ORM\Index(name: 'IDX_ADMISSION_ARBITRE', columns: ['arbitre_id'])]
class Admission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    private ?Candidat $candidat = null;

    #[ORM\Column(name: 'type_admission', length: 40, options: ['default' => 'RECRUTEMENT_INTERNE'])]
    private string $typeAdmission = 'RECRUTEMENT_INTERNE';

    #[ORM\Column(name: 'origine_dossier', length: 20, options: ['default' => 'INTERNE'])]
    private string $origineDossier = 'INTERNE';

    #[ORM\ManyToOne(targetEntity: ImportBeneficiaireLot::class)]
    #[ORM\JoinColumn(name: 'lot_integration_externe_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?ImportBeneficiaireLot $lotIntegrationExterne = null;

    #[ORM\Column(length: 30)]
    private string $decision = 'EN_ATTENTE';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $metierAffecte = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateAdmission = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $codeAdmission = null;

    #[ORM\Column(name: 'score_moyen', type: 'float', nullable: true)]
    private ?float $scoreMoyen = null;

    #[ORM\Column(name: 'avis_jury', length: 30, options: ['default' => 'EN_ATTENTE'])]
    private string $avisJury = 'EN_ATTENTE';

    #[ORM\Column(name: 'arbitrage_gestionnaire', length: 30, options: ['default' => 'EN_ATTENTE'])]
    private string $arbitrageGestionnaire = 'EN_ATTENTE';

    #[ORM\Column(name: 'observations_arbitrage', type: 'text', nullable: true)]
    private ?string $observationsArbitrage = null;

    #[ORM\Column(name: 'date_arbitrage', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateArbitrage = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'arbitre_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $arbitre = null;

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

    public function getTypeAdmission(): string
    {
        return $this->typeAdmission;
    }

    public function setTypeAdmission(string $typeAdmission): self
    {
        $this->typeAdmission = trim($typeAdmission);
        return $this;
    }

    public function getOrigineDossier(): string
    {
        return $this->origineDossier;
    }

    public function setOrigineDossier(string $origineDossier): self
    {
        $this->origineDossier = trim($origineDossier);
        return $this;
    }

    public function getLotIntegrationExterne(): ?ImportBeneficiaireLot
    {
        return $this->lotIntegrationExterne;
    }

    public function setLotIntegrationExterne(?ImportBeneficiaireLot $lot): self
    {
        $this->lotIntegrationExterne = $lot;
        return $this;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): self
    {
        $this->decision = $decision;
        return $this;
    }

    public function getMetierAffecte(): ?string
    {
        return $this->metierAffecte;
    }

    public function setMetierAffecte(?string $metierAffecte): self
    {
        $this->metierAffecte = $metierAffecte;
        return $this;
    }

    public function getDateAdmission(): ?\DateTimeInterface
    {
        return $this->dateAdmission;
    }

    public function setDateAdmission(?\DateTimeInterface $dateAdmission): self
    {
        $this->dateAdmission = $dateAdmission;
        return $this;
    }

    public function getCodeAdmission(): ?string
    {
        return $this->codeAdmission;
    }

    public function setCodeAdmission(?string $codeAdmission): self
    {
        $this->codeAdmission = $codeAdmission;
        return $this;
    }

    public function getScoreMoyen(): ?float
    {
        return $this->scoreMoyen;
    }

    public function setScoreMoyen(?float $scoreMoyen): self
    {
        $this->scoreMoyen = $scoreMoyen;
        return $this;
    }

    public function getAvisJury(): string
    {
        return $this->avisJury;
    }

    public function setAvisJury(string $avisJury): self
    {
        $this->avisJury = $avisJury;
        return $this;
    }

    public function getArbitrageGestionnaire(): string
    {
        return $this->arbitrageGestionnaire;
    }

    public function setArbitrageGestionnaire(string $arbitrageGestionnaire): self
    {
        $this->arbitrageGestionnaire = $arbitrageGestionnaire;
        return $this;
    }

    public function getObservationsArbitrage(): ?string
    {
        return $this->observationsArbitrage;
    }

    public function setObservationsArbitrage(?string $observationsArbitrage): self
    {
        $this->observationsArbitrage = $observationsArbitrage;
        return $this;
    }

    public function getDateArbitrage(): ?\DateTimeInterface
    {
        return $this->dateArbitrage;
    }

    public function setDateArbitrage(?\DateTimeInterface $dateArbitrage): self
    {
        $this->dateArbitrage = $dateArbitrage;
        return $this;
    }

    public function getArbitre(): ?User
    {
        return $this->arbitre;
    }

    public function setArbitre(?User $arbitre): self
    {
        $this->arbitre = $arbitre;
        return $this;
    }
}