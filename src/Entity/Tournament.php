<?php

namespace App\Entity;

use App\Repository\TournamentRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentRepository::class)]
#[ORM\Index(name: 'IDX_tournament_season', columns: ['season_id'])]
#[ORM\Index(name: 'IDX_tournament_created_by', columns: ['created_by_id'])]
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
    private ?User $createdBy = null;

    #[ORM\ManyToOne]
    private ?Season $season = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?DateTime $endedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $toursCount = null;

    #[ORM\Column(nullable: true)]
    private ?int $questionsPerTour = null;

    #[ORM\Column(nullable: true)]
    private ?float $difficulty = null;

    #[ORM\Column(nullable: true)]
    private ?float $trueDl = null;

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

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?DateTime $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(?DateTime $endedAt): static
    {
        $this->endedAt = $endedAt;

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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}
