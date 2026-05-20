<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AppealStatus;
use App\Enum\AppealType;
use App\Repository\AppealRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppealRepository::class)]
#[ORM\UniqueConstraint(name: 'UQ_appeal_answer', columns: ['tournament_session_team_answer_id'])]
#[ORM\Index(name: 'IDX_appeal_answer', columns: ['tournament_session_team_answer_id'])]
class Appeal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentSessionTeamAnswer $tournamentSessionTeamAnswer;

    #[ORM\Column(length: 20, enumType: AppealType::class)]
    private AppealType $type;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[ORM\Column(length: 20, enumType: AppealStatus::class)]
    private AppealStatus $status = AppealStatus::Pending;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $verdict = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTournamentSessionTeamAnswer(): TournamentSessionTeamAnswer
    {
        return $this->tournamentSessionTeamAnswer;
    }

    public function setTournamentSessionTeamAnswer(TournamentSessionTeamAnswer $answer): static
    {
        $this->tournamentSessionTeamAnswer = $answer;

        return $this;
    }

    public function getType(): AppealType
    {
        return $this->type;
    }

    public function setType(AppealType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getStatus(): AppealStatus
    {
        return $this->status;
    }

    public function setStatus(AppealStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getVerdict(): ?string
    {
        return $this->verdict;
    }

    public function setVerdict(?string $verdict): static
    {
        $this->verdict = $verdict;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
