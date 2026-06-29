<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Common\Entity\Player;
use App\Classic\Repository\TournamentSessionTeamPlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentSessionTeamPlayerRepository::class)]
#[ORM\Table(name: 'classic_tournament_session_team_player')]
#[ORM\UniqueConstraint(name: 'UQ_session_team_player', columns: ['tournament_session_team_id', 'player_id'])]
#[ORM\Index(name: 'IDX_tstp_session_team', columns: ['tournament_session_team_id'])]
#[ORM\Index(name: 'IDX_tstp_player', columns: ['player_id'])]
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

    #[ORM\Column(options: ['default' => false])]
    private bool $isCaptain = false;

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
