<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Classic\Enum\TeamPlayerTransferType;
use App\Classic\Repository\TeamPlayerTransferRepository;
use App\Common\Entity\Player;
use App\Common\Entity\Season;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamPlayerTransferRepository::class)]
#[ORM\Table(name: 'classic_team_player_transfer')]
#[ORM\Index(name: 'IDX_tpt_player_season_date', columns: ['player_id', 'season_id', 'date'])]
class TeamPlayerTransfer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Season $season;

    #[ORM\Column(length: 10, enumType: TeamPlayerTransferType::class)]
    private TeamPlayerTransferType $type;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $date;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function setTeam(Team $team): static
    {
        $this->team = $team;

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

    public function getType(): TeamPlayerTransferType
    {
        return $this->type;
    }

    public function setType(TeamPlayerTransferType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }
}
