<?php

namespace App\Entity;

use App\Repository\TournamentOfficialRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentOfficialRepository::class)]
#[ORM\UniqueConstraint(columns: ['tournament_id', 'player_id', 'role'])]
class TournamentOfficial
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
    private Player $player;

    #[ORM\Column(length: 20, enumType: TournamentOfficialRole::class)]
    private TournamentOfficialRole $role;

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

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getRole(): TournamentOfficialRole
    {
        return $this->role;
    }

    public function setRole(TournamentOfficialRole $role): static
    {
        $this->role = $role;

        return $this;
    }
}
