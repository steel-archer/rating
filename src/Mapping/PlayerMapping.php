<?php

namespace App\Mapping;

use App\DTO\Response\Player\SquadDTO;
use App\DTO\Response\Player\TournamentAppearanceDTO;
use App\DTO\Response\PlayerDTO;
use App\Entity\Player;
use App\Entity\TeamPlayer;
use App\Entity\TournamentSessionTeamPlayer;

#[AsMapper(source: Player::class, destination: PlayerDTO::class)]
final class PlayerMapping implements MappingInterface
{
    /**
     * @param array{
     *     mapper: Mapper,
     *     squads: list<TeamPlayer>,
     *     appearances: list<TournamentSessionTeamPlayer>,
     *     places: array<int, int>,
     * } $context
     * @return PlayerDTO
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        /** @var Player $source */
        $mapper = $context['mapper'];
        $places = $context['places'] ?? [];

        $squads = array_map(
            static fn(TeamPlayer $teamPlayer) => $mapper->map($teamPlayer, SquadDTO::class),
            $context['squads'] ?? [],
        );

        $tournaments = array_map(
            static fn(TournamentSessionTeamPlayer $appearance) => $mapper->map(
                $appearance,
                TournamentAppearanceDTO::class,
                ['places' => $places],
            ),
            $context['appearances'] ?? [],
        );

        return new $destinationClass(
            id: $source->getId(),
            fullName: $source->getFullName(),
            townName: $source->getTown()?->getName(),
            squads: $squads,
            tournaments: $tournaments,
        );
    }
}
