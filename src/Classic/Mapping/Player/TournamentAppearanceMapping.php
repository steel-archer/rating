<?php

declare(strict_types=1);

namespace App\Classic\Mapping\Player;

use App\Classic\DTO\Response\Player\TournamentAppearanceDTO;
use App\Classic\Entity\TournamentSessionTeamPlayer;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

#[AsMapper(source: TournamentSessionTeamPlayer::class, destination: TournamentAppearanceDTO::class)]
final class TournamentAppearanceMapping implements MappingInterface
{
    /**
     * @param TournamentSessionTeamPlayer $source
     * @param array{places?: array<int, int>} $context
     * @return TournamentAppearanceDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $sessionTeam = $source->getTournamentSessionTeam();
        $session = $sessionTeam->getTournamentSession();
        $tournament = $session->getTournament();
        $team = $sessionTeam->getTeam();
        $resultsHidden = $tournament->areResultsHidden();
        $maxScore = $tournament->getMaxScore();

        return new $destinationClass(
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            playedAt: $session->getPlayedAt(),
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            score: $resultsHidden ? null : $sessionTeam->getScore(),
            maxScore: $resultsHidden ? null : $maxScore,
            place: $resultsHidden ? null : ($context['places'][$sessionTeam->getId()] ?? null),
            isLegionary: $source->isLegionary(),
            oneTimeName: $sessionTeam->getOneTimeName(),
            isOnline: $session->isOnline(),
        );
    }
}
