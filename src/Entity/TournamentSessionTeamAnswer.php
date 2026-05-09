<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TournamentSessionTeamAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentSessionTeamAnswerRepository::class)]
#[ORM\UniqueConstraint(name: 'UQ_session_team_question', columns: ['tournament_session_team_id', 'question_number'])]
#[ORM\Index(name: 'IDX_tsta_session_team', columns: ['tournament_session_team_id'])]
class TournamentSessionTeamAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentSessionTeam $tournamentSessionTeam;

    #[ORM\Column]
    private int $questionNumber;

    #[ORM\Column]
    private bool $isCorrect;

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

    public function getQuestionNumber(): int
    {
        return $this->questionNumber;
    }

    public function setQuestionNumber(int $questionNumber): static
    {
        $this->questionNumber = $questionNumber;

        return $this;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function setIsCorrect(bool $isCorrect): static
    {
        $this->isCorrect = $isCorrect;

        return $this;
    }
}
