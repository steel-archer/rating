<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TeamPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamPlayerRepository::class)]
#[ORM\UniqueConstraint(name: 'UQ_team_player_season', columns: ['team_id', 'player_id', 'season_id'])]
#[ORM\Index(name: 'IDX_tp_team', columns: ['team_id'])]
#[ORM\Index(name: 'IDX_tp_player', columns: ['player_id'])]
#[ORM\Index(name: 'IDX_tp_season', columns: ['season_id'])]
class TeamPlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Season $season;

    #[ORM\Column(options: ['default' => false])]
    private bool $isCaptain = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): static
    {
        $this->team = $team;

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

    public function getSeason(): Season
    {
        return $this->season;
    }

    public function setSeason(Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function isCaptain(): bool
    {
        return $this->isCaptain;
    }

    public function setIsCaptain(bool $isCaptain): static
    {
        $this->isCaptain = $isCaptain;

        return $this;
    }
}
