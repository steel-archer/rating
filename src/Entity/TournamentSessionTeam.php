<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentSessionTeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentSessionTeamRepository::class)]
#[ORM\UniqueConstraint(name: 'UQ_session_team', columns: ['tournament_session_id', 'team_id'])]
#[ORM\Index(name: 'IDX_tst_session', columns: ['tournament_session_id'])]
#[ORM\Index(name: 'IDX_tst_team', columns: ['team_id'])]
class TournamentSessionTeam
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentSession $tournamentSession;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(nullable: true)]
    private ?int $score = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournamentSession(): TournamentSession
    {
        return $this->tournamentSession;
    }

    public function setTournamentSession(TournamentSession $tournamentSession): static
    {
        $this->tournamentSession = $tournamentSession;

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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }
}
