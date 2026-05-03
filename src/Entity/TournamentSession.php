<?php

namespace App\Entity;

use App\Repository\TournamentSessionRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentSessionRepository::class)]
#[ORM\UniqueConstraint(columns: ['tournament_id', 'venue_id'])]
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
    private ?DateTime $playedAt = null;

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

    public function getPlayedAt(): ?DateTime
    {
        return $this->playedAt;
    }

    public function setPlayedAt(?DateTime $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }
}
