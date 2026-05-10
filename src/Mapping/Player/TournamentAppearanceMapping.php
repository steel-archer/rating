<?php

declare(strict_types=1);

namespace App\Mapping\Player;

use App\DTO\Response\Player\TournamentAppearanceDTO;
use App\Entity\TournamentSessionTeamPlayer;
use App\Mapping\AsMapper;
use App\Mapping\MappingInterface;

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

        return new $destinationClass(
            tournamentId: $tournament->getId(),
            tournamentName: $tournament->getName(),
            playedAt: $session->getPlayedAt(),
            teamId: $team->getId(),
            teamName: $team->getName(),
            teamTownName: $team->getTown()->getName(),
            score: $sessionTeam->getScore(),
            place: $context['places'][$sessionTeam->getId()] ?? null,
            isLegionary: $source->isLegionary(),
            oneTimeName: $sessionTeam->getOneTimeName(),
        );
    }
}
