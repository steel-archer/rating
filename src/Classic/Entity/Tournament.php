<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Common\Entity\Player;
use App\Common\Entity\Season;
use App\Classic\Enum\TournamentStatus;
use App\Classic\Repository\TournamentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Table(name: 'classic_tournament')]
#[ORM\Index(name: 'IDX_tournament_season', columns: ['season_id'])]
#[ORM\Index(name: 'IDX_tournament_created_by', columns: ['created_by_id'])]
#[ORM\HasLifecycleCallbacks]
class Tournament
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 20, enumType: TournamentStatus::class)]
    private TournamentStatus $status = TournamentStatus::Draft;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Player $createdBy = null;

    #[ORM\ManyToOne]
    private ?Season $season = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $endedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $resultsHiddenUntil = null;

    #[ORM\Column(nullable: true)]
    private ?int $toursCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $questionsPerTour = null;

    #[ORM\Column(nullable: true)]
    private ?float $difficulty = null;

    #[ORM\Column(nullable: true)]
    private ?float $trueDl = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $registrationDeadline = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $detailsHiddenUntil = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $submissionDeadline = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $appealDeadline = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $discussionLink = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column]
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSeason(): ?Season
    {
        return $this->season;
    }

    public function setSeason(?Season $season): static
    {
        $this->season = $season;

        return $this;
    }

    public function getStartedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getResultsHiddenUntil(): ?DateTimeImmutable
    {
        return $this->resultsHiddenUntil;
    }

    public function setResultsHiddenUntil(?DateTimeImmutable $resultsHiddenUntil): static
    {
        $this->resultsHiddenUntil = $resultsHiddenUntil;

        return $this;
    }

    public function areResultsHidden(): bool
    {
        return $this->resultsHiddenUntil !== null
            && $this->resultsHiddenUntil > new DateTimeImmutable();
    }

    public function getRegistrationDeadline(): ?DateTimeImmutable
    {
        return $this->registrationDeadline;
    }

    public function setRegistrationDeadline(?DateTimeImmutable $registrationDeadline): static
    {
        $this->registrationDeadline = $registrationDeadline;

        return $this;
    }

    public function isRegistrationOpen(): bool
    {
        return $this->registrationDeadline !== null
            && $this->registrationDeadline > new DateTimeImmutable();
    }

    public function getDetailsHiddenUntil(): ?DateTimeImmutable
    {
        return $this->detailsHiddenUntil;
    }

    public function setDetailsHiddenUntil(?DateTimeImmutable $detailsHiddenUntil): static
    {
        $this->detailsHiddenUntil = $detailsHiddenUntil;

        return $this;
    }

    public function areDetailsHidden(): bool
    {
        return $this->detailsHiddenUntil !== null
            && $this->detailsHiddenUntil > new DateTimeImmutable();
    }

    public function getSubmissionDeadline(): ?DateTimeImmutable
    {
        return $this->submissionDeadline;
    }

    public function setSubmissionDeadline(?DateTimeImmutable $submissionDeadline): static
    {
        $this->submissionDeadline = $submissionDeadline;

        return $this;
    }

    public function isSubmissionOpen(): bool
    {
        return $this->submissionDeadline !== null
            && $this->submissionDeadline > new DateTimeImmutable();
    }

    public function getAppealDeadline(): ?DateTimeImmutable
    {
        return $this->appealDeadline;
    }

    public function setAppealDeadline(?DateTimeImmutable $appealDeadline): static
    {
        $this->appealDeadline = $appealDeadline;

        return $this;
    }

    public function isAppealOpen(): bool
    {
        return $this->appealDeadline !== null
            && $this->appealDeadline > new DateTimeImmutable();
    }

    public function getDiscussionLink(): ?string
    {
        return $this->discussionLink;
    }

    public function setDiscussionLink(?string $discussionLink): static
    {
        $this->discussionLink = $discussionLink;

        return $this;
    }

    public function getToursCount(): ?int
    {
        return $this->toursCount;
    }

    public function setToursCount(?int $toursCount): static
    {
        $this->toursCount = $toursCount;

        return $this;
    }

    public function getQuestionsPerTour(): ?int
    {
        return $this->questionsPerTour;
    }

    public function setQuestionsPerTour(?int $questionsPerTour): static
    {
        $this->questionsPerTour = $questionsPerTour;

        return $this;
    }

    public function getMaxScore(): ?int
    {
        if ($this->toursCount === null || $this->questionsPerTour === null) {
            return null;
        }

        return $this->toursCount * $this->questionsPerTour;
    }

    public function getDifficulty(): ?float
    {
        return $this->difficulty;
    }

    public function setDifficulty(?float $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getTrueDl(): ?float
    {
        return $this->trueDl;
    }

    public function setTrueDl(?float $trueDl): static
    {
        $this->trueDl = $trueDl;

        return $this;
    }

    public function getStatus(): TournamentStatus
    {
        return $this->status;
    }

    public function setStatus(TournamentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedBy(): ?Player
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?Player $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isStarted(): bool
    {
        return $this->status === TournamentStatus::Published
            && $this->startedAt !== null
            && $this->startedAt <= new DateTimeImmutable();
    }
}
