<?php

declare(strict_types=1);

namespace App\Classic\Provider;

use App\Classic\Entity\TeamPlayer;
use App\Common\Contract\PlayerTeamProviderInterface;
use App\Common\Entity\Season;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ClassicPlayerTeamProvider implements PlayerTeamProviderInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function getTeamsByPlayerIds(array $playerIds, Season $season): array
    {
        if ($playerIds === []) {
            return [];
        }

        $rows = $this->em->createQueryBuilder()
            ->select('IDENTITY(tp.player) AS playerId', 't.id AS teamId', 't.name AS teamName')
            ->from(TeamPlayer::class, 'tp')
            ->join('tp.team', 't')
            ->where('tp.player IN (:playerIds)')
            ->andWhere('tp.season = :season')
            ->setParameter('playerIds', $playerIds)
            ->setParameter('season', $season)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['playerId']] = [
                'teamId' => (int) $row['teamId'],
                'teamName' => $row['teamName'],
            ];
        }

        return $result;
    }
}
