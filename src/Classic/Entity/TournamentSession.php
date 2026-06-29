<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Common\Entity\Player;
use App\Common\Entity\Venue;
use App\Classic\Repository\TournamentSessionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentSessionRepository::class)]
#[ORM\Table(name: 'classic_tournament_session')]
#[ORM\Index(name: 'IDX_ts_tournament', columns: ['tournament_id'])]
#[ORM\Index(name: 'IDX_ts_venue', columns: ['venue_id'])]
#[ORM\Index(name: 'IDX_ts_representative', columns: ['representative_id'])]
#[ORM\Index(name: 'IDX_ts_host', columns: ['host_id'])]
#[ORM\Index(name: 'IDX_ts_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'IDX_ts_updated_at', columns: ['updated_at'])]
#[ORM\HasLifecycleCallbacks]
class TournamentSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Tournament $tournament;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Venue $venue;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $representative;

    #[ORM\ManyToOne]
    private ?Player $host = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $playedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $estimatedTeams = null;

    #[ORM\Column]
    private bool $isOnline = false;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournament(): Tournament
    {
        return $this->tournament;
    }

    public function setTournament(Tournament $tournament): static
    {
        $this->tournament = $tournament;

        return $this;
    }

    public function getVenue(): Venue
    {
        return $this->venue;
    }

    public function setVenue(Venue $venue): static
    {
        $this->venue = $venue;

        return $this;
    }

    public function getRepresentative(): Player
    {
        return $this->representative;
    }

    public function setRepresentative(Player $representative): static
    {
        $this->representative = $representative;

        return $this;
    }

    public function getHost(): ?Player
    {
        return $this->host;
    }

    public function setHost(?Player $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getPlayedAt(): ?DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(?DateTimeImmutable $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getEstimatedTeams(): ?int
    {
        return $this->estimatedTeams;
    }

    public function setEstimatedTeams(?int $estimatedTeams): static
    {
        $this->estimatedTeams = $estimatedTeams;

        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
