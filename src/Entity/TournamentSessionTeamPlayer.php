<?php

namespace App\Entity;

use App\Repository\TournamentSessionTeamPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentSessionTeamPlayerRepository::class)]
#[ORM\UniqueConstraint(columns: ['tournament_session_team_id', 'player_id'])]
class TournamentSessionTeamPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentSessionTeam $tournamentSessionTeam;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\Column(options: ['default' => false])]
    private bool $isLegionary = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournamentSessionTeam(): TournamentSessionTeam
    {
        return $this->tournamentSessionTeam;
    }

    public function setTournamentSessionTeam(TournamentSessionTeam $tournamentSessionTeam): static
    {
        $this->tournamentSessionTeam = $tournamentSessionTeam;

        return $this;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function isLegionary(): bool
    {
        return $this->isLegionary;
    }

    public function setIsLegionary(bool $isLegionary): static
    {
        $this->isLegionary = $isLegionary;

        return $this;
    }
}
