<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\SessionClaimStatus;
use App\Repository\SessionClaimRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionClaimRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_sc_session', columns: ['session_id'])]
#[ORM\Index(name: 'IDX_sc_player', columns: ['player_id'])]
#[ORM\Index(name: 'IDX_sc_created_at', columns: ['created_at'])]
class SessionClaim
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentSession $session;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\Column(length: 20, enumType: SessionClaimStatus::class)]
    private SessionClaimStatus $status = SessionClaimStatus::Pending;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSession(): TournamentSession
    {
        return $this->session;
    }

    public function setSession(TournamentSession $session): static
    {
        $this->session = $session;

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

    public function getStatus(): SessionClaimStatus
    {
        return $this->status;
    }

    public function setStatus(SessionClaimStatus $status): static
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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }
}
