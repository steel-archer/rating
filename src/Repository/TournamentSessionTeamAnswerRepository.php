<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TournamentSessionTeamAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<TournamentSessionTeamAnswer> */
class TournamentSessionTeamAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TournamentSessionTeamAnswer::class);
    }
}
