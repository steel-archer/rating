<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Classic\Enum\DisputeStatus;
use App\Classic\Repository\TournamentSessionTeamAnswerRepository;
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

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $disputeText = null;

    #[ORM\Column(length: 20, nullable: true, enumType: DisputeStatus::class)]
    private ?DisputeStatus $disputeStatus = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $disputeComment = null;

    #[ORM\Column]
    private bool $isQuestionRemoved = false;

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

    public function getDisputeText(): ?string
    {
        return $this->disputeText;
    }

    public function setDisputeText(?string $disputeText): static
    {
        $this->disputeText = $disputeText;

        return $this;
    }

    public function getDisputeStatus(): ?DisputeStatus
    {
        return $this->disputeStatus;
    }

    public function setDisputeStatus(?DisputeStatus $disputeStatus): static
    {
        $this->disputeStatus = $disputeStatus;

        return $this;
    }

    public function getDisputeComment(): ?string
    {
        return $this->disputeComment;
    }

    public function setDisputeComment(?string $disputeComment): static
    {
        $this->disputeComment = $disputeComment;

        return $this;
    }

    public function isQuestionRemoved(): bool
    {
        return $this->isQuestionRemoved;
    }

    public function setIsQuestionRemoved(bool $isQuestionRemoved): static
    {
        $this->isQuestionRemoved = $isQuestionRemoved;

        return $this;
    }
}
