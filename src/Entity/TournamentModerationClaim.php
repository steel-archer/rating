<?php

namespace App\Entity;

use App\Repository\TournamentModerationClaimRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentModerationClaimRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_tmc_tournament', columns: ['tournament_id'])]
class TournamentModerationClaim
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Tournament $tournament;

    #[ORM\Column(length: 20, enumType: TournamentModerationStatus::class)]
    private TournamentModerationStatus $status = TournamentModerationStatus::Pending;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private DateTime $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTime $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

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

    public function getStatus(): TournamentModerationStatus
    {
        return $this->status;
    }

    public function setStatus(TournamentModerationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getResolvedAt(): ?DateTime
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?DateTime $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }
}
