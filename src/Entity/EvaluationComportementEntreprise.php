<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'evaluation_comportement_entreprise')]
#[ORM\UniqueConstraint(
    name: 'UNIQ_EVAL_COMPORTEMENT_CRITERE',
    columns: ['evaluation_id', 'critere']
)]
class EvaluationComportementEntreprise
{
    public const CRITERES = [
        'PONCTUALITE' => 'Ponctualité',
        'ASSIDUITE' => 'Assiduité',
        'TENUE_PRESENTATION' => 'Tenue vestimentaire et présentation',
        'RESPECT_CONSIGNES' => 'Respect des consignes et du règlement intérieur',
        'CURIOSITE_INITIATIVE' => 'Curiosité / prise d’initiative',
        'INTEGRATION_COMMUNICATION' => 'Intégration / communication',
        'RESPECT_DELAIS' => 'Respect des délais d’exécution',
        'ATTITUDE_TRAVAIL' => 'Attitude au travail',
        'QUALITE_TRAVAIL' => 'Qualité du travail',
    ];

    public const APPRECIATIONS = [
        'INSATISFAISANT',
        'SATISFAISANT',
        'TRES_SATISFAISANT',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: EvaluationEntreprise::class, inversedBy: 'evaluationsComportementales')]
    #[ORM\JoinColumn(name: 'evaluation_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?EvaluationEntreprise $evaluation = null;

    #[ORM\Column(length: 50)]
    private string $critere = '';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $appreciation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observation = null;

    public function getId(): ?int { return $this->id; }

    public function getEvaluation(): ?EvaluationEntreprise { return $this->evaluation; }
    public function setEvaluation(EvaluationEntreprise $v): self { $this->evaluation = $v; return $this; }

    public function getCritere(): string { return $this->critere; }

    public function setCritere(string $v): self
    {
        if (!array_key_exists($v, self::CRITERES)) {
            throw new \InvalidArgumentException('Critère comportemental non autorisé.');
        }
        $this->critere = $v;
        return $this;
    }

    public function getLibelleCritere(): string
    {
        return self::CRITERES[$this->critere] ?? $this->critere;
    }

    public function getAppreciation(): ?string { return $this->appreciation; }

    public function setAppreciation(?string $v): self
    {
        if ($v !== null && !in_array($v, self::APPRECIATIONS, true)) {
            throw new \InvalidArgumentException('Appréciation comportementale non autorisée.');
        }
        $this->appreciation = $v;
        return $this;
    }

    public function getObservation(): ?string { return $this->observation; }
    public function setObservation(?string $v): self { $this->observation = $v; return $this; }
}