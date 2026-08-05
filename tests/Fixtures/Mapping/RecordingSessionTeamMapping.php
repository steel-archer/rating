<?php

declare(strict_types=1);

namespace App\Tests\Fixtures\Mapping;

use App\Classic\DTO\Response\Tournament\SessionTeamDTO;
use App\Classic\Entity\TournamentSessionTeam;
use App\Common\Mapping\AsMapper;
use App\Common\Mapping\MappingInterface;

/**
 * Test double standing in for the real `SessionTeamMapping`.
 *
 * Records every `map()` call (source object plus the context array it was
 * called with), so a test can assert on exactly what `SessionTeamResultBuilder`
 * passed through, without needing to build full `Team`/`Tournament` object
 * graphs required by the real mapping.
 */
#[AsMapper(source: TournamentSessionTeam::class, destination: SessionTeamDTO::class)]
final class RecordingSessionTeamMapping implements MappingInterface
{
    /** @var list<array{source: object, context: array<string, mixed>}> */
    public array $calls = [];

    /**
     * @param TournamentSessionTeam $source
     * @param array<string, mixed> $context
     */
    public function map(mixed $source, string $destinationClass, array $context = []): object
    {
        $this->calls[] = ['source' => $source, 'context' => $context];

        return new SessionTeamDTO(
            sessionTeamId: $source->getId(),
            teamId: 0,
            teamName: '',
            teamTownName: null,
            score: null,
            maxScore: null,
            place: $context['place'] ?? null,
            players: [],
            sessionPlace: $context['sessionPlace'] ?? null,
        );
    }
}
