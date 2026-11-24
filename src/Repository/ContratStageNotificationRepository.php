<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ContratStageNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratStageNotification>
 */
final class ContratStageNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratStageNotification::class);
    }
}