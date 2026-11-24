<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContratStageSignature;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratStageSignature>
 */
final class ContratStageSignatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratStageSignature::class);
    }
}