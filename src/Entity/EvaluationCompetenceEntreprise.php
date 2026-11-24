<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'evaluation_competence_entreprise')]
class EvaluationCompetenceEntreprise
{
    public const NIVEAU_MAITRISE = 'MAITRISE';
    public const NIVEAU_AUTONOMIE_PARTIELLE = 'AUTONOMIE_PARTIELLE';
    public const NIVEAU_DEPENDANCE_TOTALE = 'DEPENDANCE_TOTALE';

    public const NIVEAUX = [
        self::NIVEAU_MAITRISE,
        self::NIVEAU_AUTONOMIE_PARTIELLE,
        self::NIVEAU_DEPENDANCE_TOTALE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EvaluationEntreprise::class, inversedBy: 'competencesTechniques')]
    #[ORM\JoinColumn(name: 'evaluation_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?EvaluationEntreprise $evaluation = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $referenceRf = null;

    #[ORM\Column(type: 'text')]
    private string $intituleCompetence = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    #[ORM\Column]
    private int $ordre = 0;

    public function getId(): ?int { return $this->id; }

    public function getEvaluation(): ?EvaluationEntreprise { return $this->evaluation; }
    public function setEvaluation(EvaluationEntreprise $v): self { $this->evaluation = $v; return $this; }

    public function getReferenceRf(): ?string { return $this->referenceRf; }
    public function setReferenceRf(?string $v): self { $this->referenceRf = $v; return $this; }

    public function getIntituleCompetence(): string { return $this->intituleCompetence; }
    public function setIntituleCompetence(string $v): self { $this->intituleCompetence = trim($v); return $this; }

    public function getNiveau(): ?string { return $this->niveau; }

    public function setNiveau(?string $v): self
    {
        if ($v !== null && !in_array($v, self::NIVEAUX, true)) {
            throw new \InvalidArgumentException('Niveau technique non autorisé.');
        }
        $this->niveau = $v;
        return $this;
    }

    public function getObservations(): ?string { return $this->observations; }
    public function setObservations(?string $v): self { $this->observations = $v; return $this; }

    public function getOrdre(): int { return $this->ordre; }
    public function setOrdre(int $v): self { $this->ordre = $v; return $this; }
}