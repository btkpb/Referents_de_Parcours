<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'contrat_stage_version',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'UNIQ_CONTRAT_STAGE_VERSION_NUMERO',
            columns: ['contrat_id', 'numero_version']
        ),
        new ORM\UniqueConstraint(
            name: 'UNIQ_CONTRAT_STAGE_VERSION_ID_CONTRAT',
            columns: ['id', 'contrat_id']
        ),
    ]
)]
class ContratStageVersion
{
    public const TYPE_INITIALE = 'INITIALE';
    public const TYPE_AVENANT = 'AVENANT';

    public const TYPES_AUTORISES = [
        self::TYPE_INITIALE,
        self::TYPE_AVENANT,
    ];

    public const STATUT_BROUILLON = 'BROUILLON';
    public const STATUT_A_VALIDER = 'A_VALIDER';
    public const STATUT_EN_SIGNATURE = 'EN_SIGNATURE';
    public const STATUT_BLOQUE = 'BLOQUE';
    public const STATUT_SIGNE = 'SIGNE';
    public const STATUT_REMPLACEE = 'REMPLACEE';
    public const STATUT_ANNULEE = 'ANNULEE';

    public const STATUTS_AUTORISES = [
        self::STATUT_BROUILLON,
        self::STATUT_A_VALIDER,
        self::STATUT_EN_SIGNATURE,
        self::STATUT_BLOQUE,
        self::STATUT_SIGNE,
        self::STATUT_REMPLACEE,
        self::STATUT_ANNULEE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ContratStage::class)]
    #[ORM\JoinColumn(name: 'contrat_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?ContratStage $contrat = null;

    #[ORM\Column(name: 'numero_version')]
    private int $numeroVersion = 1;

    #[ORM\Column(name: 'type_version', length: 20)]
    private string $typeVersion = self::TYPE_INITIALE;

    #[ORM\Column(name: 'motif_avenant', type: 'text', nullable: true)]
    private ?string $motifAvenant = null;

    #[ORM\Column(length: 30)]
    private string $statut = self::STATUT_BROUILLON;

    #[ORM\ManyToOne(targetEntity: Candidat::class)]
    #[ORM\JoinColumn(name: 'candidat_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?Candidat $candidat = null;

    #[ORM\ManyToOne(targetEntity: EntrepriseStage::class)]
    #[ORM\JoinColumn(name: 'entreprise_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?EntrepriseStage $entreprise = null;

    // Instantané de l'entreprise
    #[ORM\Column(name: 'entreprise_raison_sociale', length: 180)]
    private string $entrepriseRaisonSociale = '';

    #[ORM\Column(name: 'entreprise_sigle', length: 80, nullable: true)]
    private ?string $entrepriseSigle = null;

    #[ORM\Column(name: 'entreprise_rccm', length: 100, nullable: true)]
    private ?string $entrepriseRccm = null;

    #[ORM\Column(name: 'entreprise_secteur', length: 160, nullable: true)]
    private ?string $entrepriseSecteur = null;

    #[ORM\Column(name: 'entreprise_adresse', length: 255, nullable: true)]
    private ?string $entrepriseAdresse = null;

    #[ORM\Column(name: 'entreprise_ville', length: 120, nullable: true)]
    private ?string $entrepriseVille = null;

    #[ORM\Column(name: 'entreprise_telephone', length: 30, nullable: true)]
    private ?string $entrepriseTelephone = null;

    #[ORM\Column(name: 'entreprise_email', length: 180, nullable: true)]
    private ?string $entrepriseEmail = null;

    #[ORM\Column(name: 'entreprise_representant_nom', length: 160, nullable: true)]
    private ?string $entrepriseRepresentantNom = null;

    #[ORM\Column(name: 'entreprise_representant_fonction', length: 120, nullable: true)]
    private ?string $entrepriseRepresentantFonction = null;

    #[ORM\Column(name: 'entreprise_representant_telephone', length: 30, nullable: true)]
    private ?string $entrepriseRepresentantTelephone = null;

    #[ORM\Column(name: 'entreprise_representant_email', length: 180, nullable: true)]
    private ?string $entrepriseRepresentantEmail = null;

    // Instantané du stagiaire
    #[ORM\Column(name: 'stagiaire_nom', length: 100)]
    private string $stagiaireNom = '';

    #[ORM\Column(name: 'stagiaire_prenoms', length: 160)]
    private string $stagiairePrenoms = '';

    #[ORM\Column(name: 'stagiaire_sexe', length: 10, nullable: true)]
    private ?string $stagiaireSexe = null;

    #[ORM\Column(name: 'stagiaire_telephone', length: 30)]
    private string $stagiaireTelephone = '';

    #[ORM\Column(name: 'stagiaire_email', length: 180, nullable: true)]
    private ?string $stagiaireEmail = null;

    #[ORM\Column(name: 'stagiaire_metier', length: 80)]
    private string $stagiaireMetier = '';

    #[ORM\Column(name: 'stagiaire_diplome', length: 50, nullable: true)]
    private ?string $stagiaireDiplome = null;

    #[ORM\Column(name: 'stagiaire_adresse', length: 255, nullable: true)]
    private ?string $stagiaireAdresse = null;

    // Instantané de l'établissement
    #[ORM\Column(name: 'etablissement_id')]
    private int $etablissementId;

    #[ORM\Column(name: 'etablissement_nom', length: 180)]
    private string $etablissementNom = '';

    #[ORM\Column(name: 'etablissement_ville', length: 120)]
    private string $etablissementVille = '';

    #[ORM\Column(name: 'etablissement_adresse', length: 255, nullable: true)]
    private ?string $etablissementAdresse = null;

    #[ORM\Column(name: 'etablissement_telephone', length: 30, nullable: true)]
    private ?string $etablissementTelephone = null;

    #[ORM\Column(name: 'etablissement_email', length: 180, nullable: true)]
    private ?string $etablissementEmail = null;

    // Signataire représentant l'établissement
    #[ORM\Column(name: 'etablissement_signataire_type', length: 30)]
    private string $etablissementSignataireType = 'REFERENT_PARCOURS';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'etablissement_signataire_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $etablissementSignataireUser = null;

    #[ORM\Column(name: 'etablissement_signataire_nom', length: 160)]
    private string $etablissementSignataireNom = '';

    #[ORM\Column(name: 'etablissement_signataire_fonction', length: 120)]
    private string $etablissementSignataireFonction = '';

    #[ORM\Column(name: 'etablissement_signataire_email', length: 180, nullable: true)]
    private ?string $etablissementSignataireEmail = null;

    #[ORM\Column(name: 'etablissement_signataire_telephone', length: 30, nullable: true)]
    private ?string $etablissementSignataireTelephone = null;

    // Informations sur le stage
    #[ORM\Column(name: 'metier_stage', length: 160)]
    private string $metierStage = '';

    #[ORM\Column(name: 'secteur_stage', length: 160, nullable: true)]
    private ?string $secteurStage = null;

    #[ORM\Column(name: 'poste_stage', length: 180)]
    private string $posteStage = '';

    #[ORM\Column(name: 'missions', type: 'text')]
    private string $missions = '';

    #[ORM\Column(name: 'date_debut', type: 'date_immutable')]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(name: 'date_fin', type: 'date_immutable')]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\Column(name: 'jours_travail', length: 120, nullable: true)]
    private ?string $joursTravail = null;

    #[ORM\Column(name: 'heure_debut', type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $heureDebut = null;

    #[ORM\Column(name: 'heure_fin', type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $heureFin = null;

    #[ORM\Column(name: 'pause_minutes', nullable: true)]
    private ?int $pauseMinutes = null;

    #[ORM\Column(name: 'heures_hebdomadaires', type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $heuresHebdomadaires = null;

    // Maître de stage
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'maitre_stage_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $maitreStageUser = null;

    #[ORM\Column(name: 'maitre_stage_nom', length: 160, nullable: true)]
    private ?string $maitreStageNom = null;

    #[ORM\Column(name: 'maitre_stage_fonction', length: 120, nullable: true)]
    private ?string $maitreStageFonction = null;

    #[ORM\Column(name: 'maitre_stage_telephone', length: 30, nullable: true)]
    private ?string $maitreStageTelephone = null;

    // Référent de parcours
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'referent_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $referentUser = null;

    #[ORM\Column(name: 'referent_nom', length: 160, nullable: true)]
    private ?string $referentNom = null;

    // Indemnité
    #[ORM\Column(name: 'salaire_reference', type: 'decimal', precision: 12, scale: 2)]
    private string $salaireReference = '0.00';

    #[ORM\Column(name: 'reference_salaire', length: 255)]
    private string $referenceSalaire = '';

    #[ORM\Column(name: 'taux_minimum', type: 'decimal', precision: 5, scale: 2)]
    private string $tauxMinimum = '50.00';

    #[ORM\Column(name: 'seuil_indemnite', type: 'decimal', precision: 12, scale: 2)]
    private string $seuilIndemnite = '0.00';

    #[ORM\Column(name: 'indemnite_mensuelle', type: 'decimal', precision: 12, scale: 2)]
    private string $indemniteMensuelle = '0.00';

    #[ORM\Column(name: 'indemnite_conforme', type: 'boolean')]
    private bool $indemniteConforme = false;

    // Documents privés et preuves
    #[ORM\Column(name: 'contrat_pdf_path', length: 500, nullable: true)]
    private ?string $contratPdfPath = null;

    #[ORM\Column(name: 'contrat_pdf_hash', length: 64, nullable: true)]
    private ?string $contratPdfHash = null;

    #[ORM\Column(name: 'scan_signe_path', length: 500, nullable: true)]
    private ?string $scanSignePath = null;

    #[ORM\Column(name: 'scan_signe_hash', length: 64, nullable: true)]
    private ?string $scanSigneHash = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'scan_depose_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $scanDeposePar = null;

    #[ORM\Column(name: 'scan_depose_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $scanDeposeLe = null;

    #[ORM\Column(name: 'scan_verifie', type: 'boolean')]
    private bool $scanVerifie = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'scan_verifie_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $scanVerifiePar = null;

    #[ORM\Column(name: 'scan_verifie_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $scanVerifieLe = null;

    #[ORM\Column(name: 'pdf_final_path', length: 500, nullable: true)]
    private ?string $pdfFinalPath = null;

    #[ORM\Column(name: 'pdf_final_hash', length: 64, nullable: true)]
    private ?string $pdfFinalHash = null;

    #[ORM\Column(name: 'genere_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $genereLe = null;

    #[ORM\Column(name: 'signatures_terminees_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $signaturesTermineesLe = null;

    #[ORM\Column(name: 'finalise_le', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finaliseLe = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'cree_par_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $creePar = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
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

    public function getContrat(): ?ContratStage
    {
        return $this->contrat;
    }

    public function setContrat(ContratStage $contrat): self
    {
        $this->contrat = $contrat;
        return $this;
    }

    public function getNumeroVersion(): int
    {
        return $this->numeroVersion;
    }

    public function setNumeroVersion(int $numeroVersion): self
    {
        if ($numeroVersion < 1) {
            throw new \InvalidArgumentException('Le numéro de version doit être supérieur ou égal à 1.');
        }

        $this->numeroVersion = $numeroVersion;
        return $this;
    }

    public function getTypeVersion(): string
    {
        return $this->typeVersion;
    }

    public function setTypeVersion(string $typeVersion): self
    {
        if (!in_array($typeVersion, self::TYPES_AUTORISES, true)) {
            throw new \InvalidArgumentException('Type de version invalide.');
        }

        $this->typeVersion = $typeVersion;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        if (!in_array($statut, self::STATUTS_AUTORISES, true)) {
            throw new \InvalidArgumentException('Statut de version invalide.');
        }

        $this->statut = $statut;
        return $this;
    }

    public function getCandidat(): ?Candidat
    {
        return $this->candidat;
    }

    public function setCandidat(Candidat $candidat): self
    {
        $this->candidat = $candidat;
        return $this;
    }

    public function getEntreprise(): ?EntrepriseStage
    {
        return $this->entreprise;
    }

    public function setEntreprise(EntrepriseStage $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): self
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): self
    {
        if ($this->dateDebut !== null && $dateFin < $this->dateDebut) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure ou égale à la date de début.');
        }

        $this->dateFin = $dateFin;
        return $this;
    }

    public function getIndemniteMensuelle(): string
    {
        return $this->indemniteMensuelle;
    }

    public function setIndemniteMensuelle(string $montant): self
    {
        if (!is_numeric($montant) || (float) $montant < 0) {
            throw new \InvalidArgumentException('Le montant de l’indemnité est invalide.');
        }

        $this->indemniteMensuelle = number_format((float) $montant, 2, '.', '');
        return $this;
    }

    public function isIndemniteConforme(): bool
    {
        return $this->indemniteConforme;
    }

    public function setIndemniteConforme(bool $conforme): self
    {
        $this->indemniteConforme = $conforme;
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