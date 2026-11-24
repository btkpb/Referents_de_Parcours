<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'evaluation')]
#[ORM\UniqueConstraint(
    name: 'uniq_evaluation_entretien_jury',
    columns: ['entretien_id', 'jury_id']
)]
class Evaluation
{
    private const BAREME = [
        'identification' => 3,
        'motivation' => 8,
        'freins' => 15,
        'organisation' => 2,
        'anticipation' => 2,
        'relations' => 2,
        'projet' => 4,
        'premiereImpression' => 2,
        'expressionOrale' => 2,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Entretien::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Entretien $entretien = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'jury_id', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
    private ?User $jury = null;

    #[ORM\Column(type: 'float')]
    private float $scoreIdentification = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreMotivation = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreFreins = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreOrganisation = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreAnticipation = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreRelations = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreProjet = 0;

    #[ORM\Column(type: 'float')]
    private float $scorePremiereImpression = 0;

    #[ORM\Column(type: 'float')]
    private float $scoreExpressionOrale = 0;

    #[ORM\Column(length: 20)]
    private string $decision = 'EN_ATTENTE';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $observations = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntretien(): ?Entretien
    {
        return $this->entretien;
    }

    public function setEntretien(?Entretien $entretien): self
    {
        $this->entretien = $entretien;
        return $this;
    }

    public function getJury(): ?User
    {
        return $this->jury;
    }

    public function setJury(?User $jury): self
    {
        $this->jury = $jury;
        return $this;
    }

    public function getScoreIdentification(): float
    {
        return $this->scoreIdentification;
    }

    public function getScoreMotivation(): float
    {
        return $this->scoreMotivation;
    }

    public function getScoreFreins(): float
    {
        return $this->scoreFreins;
    }

    public function getScoreOrganisation(): float
    {
        return $this->scoreOrganisation;
    }

    public function getScoreAnticipation(): float
    {
        return $this->scoreAnticipation;
    }

    public function getScoreRelations(): float
    {
        return $this->scoreRelations;
    }

    public function getScoreProjet(): float
    {
        return $this->scoreProjet;
    }

    public function getScorePremiereImpression(): float
    {
        return $this->scorePremiereImpression;
    }

    public function getScoreExpressionOrale(): float
    {
        return $this->scoreExpressionOrale;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): self
    {
        if (!in_array($decision, ['EN_ATTENTE', 'FAVORABLE', 'DEFAVORABLE'], true)) {
            throw new \InvalidArgumentException('Avis du jury invalide.');
        }

        $this->decision = $decision;
        return $this;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function setObservations(?string $observations): self
    {
        $this->observations = $observations;
        return $this;
    }

    public function setScores(array $scores): self
    {
        foreach (self::BAREME as $critere => $maximum) {
            if (!array_key_exists($critere, $scores)) {
                continue;
            }

            $valeur = $scores[$critere];

            if (!is_numeric($valeur) || !is_finite((float) $valeur)) {
                throw new \InvalidArgumentException(
                    sprintf('La note du critère "%s" doit être numérique.', $critere)
                );
            }

            $note = (float) $valeur;

            if ($note < 0 || $note > $maximum) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'La note de "%s" doit être comprise entre 0 et %s.',
                        $critere,
                        $maximum
                    )
                );
            }

            if (abs($note * 2 - round($note * 2)) > 0.000001) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'La note de "%s" doit être attribuée par demi-point.',
                        $critere
                    )
                );
            }

            $propriete = 'score' . ucfirst($critere);
            $this->$propriete = $note;
        }

        return $this;
    }

    public function total(): float
    {
        return
            $this->scoreIdentification
            + $this->scoreMotivation
            + $this->scoreFreins
            + $this->scoreOrganisation
            + $this->scoreAnticipation
            + $this->scoreRelations
            + $this->scoreProjet
            + $this->scorePremiereImpression
            + $this->scoreExpressionOrale;
    }

    public static function bareme(): array
    {
        return self::BAREME;
    }
}