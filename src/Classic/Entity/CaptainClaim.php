<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Classic\Enum\CaptainClaimStatus;
use App\Classic\Repository\CaptainClaimRepository;
use App\Common\Entity\Player;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaptainClaimRepository::class)]
#[ORM\Table(name: 'classic_captain_claim')]
#[ORM\Index(name: 'IDX_cc_player', columns: ['player_id'])]
#[ORM\Index(name: 'IDX_cc_team', columns: ['team_id'])]
#[ORM\Index(name: 'IDX_cc_status', columns: ['status'])]
class CaptainClaim
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

    #[ORM\Column(length: 20, enumType: CaptainClaimStatus::class)]
    private CaptainClaimStatus $status = CaptainClaimStatus::Pending;

    #[ORM\Column(type: 'text')]
    private string $comment;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $moderatorComment = null;

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

    public function getStatus(): CaptainClaimStatus
    {
        return $this->status;
    }

    public function setStatus(CaptainClaimStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getModeratorComment(): ?string
    {
        return $this->moderatorComment;
    }

    public function setModeratorComment(?string $moderatorComment): static
    {
        $this->moderatorComment = $moderatorComment;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
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
