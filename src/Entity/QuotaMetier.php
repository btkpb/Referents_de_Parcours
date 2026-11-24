<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'quota_metier')]
#[ORM\UniqueConstraint(
    name: 'uniq_quota_campagne_metier',
    columns: ['campagne_id', 'metier_id']
)]
class QuotaMetier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Campagne::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Campagne $campagne = null;

    #[ORM\ManyToOne(targetEntity: MetierCatalogue::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?MetierCatalogue $metier = null;

    #[ORM\Column]
    private int $quota = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampagne(): ?Campagne
    {
        return $this->campagne;
    }

    public function setCampagne(Campagne $campagne): self
    {
        $this->campagne = $campagne;
        return $this;
    }

    public function getMetier(): ?MetierCatalogue
    {
        return $this->metier;
    }

    public function setMetier(MetierCatalogue $metier): self
    {
        $this->metier = $metier;
        return $this;
    }

    public function getQuota(): int
    {
        return $this->quota;
    }

    public function setQuota(int $quota): self
    {
        $this->quota = max(0, $quota);
        return $this;
    }
}