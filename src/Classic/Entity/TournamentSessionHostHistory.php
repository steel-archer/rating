<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Classic\Repository\TournamentSessionHostHistoryRepository;
use App\Common\Entity\Player;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Records every player that has ever been the host of a given session (the
 * initial host plus every replacement). The currently active host lives on
 * TournamentSession::host; this table is the audit trail used to flag former
 * hosts and to block them from being entered as players in the same tournament.
 */
#[ORM\Entity(repositoryClass: TournamentSessionHostHistoryRepository::class)]
#[ORM\Table(name: 'classic_tournament_session_host_history')]
#[ORM\Index(name: 'IDX_tshh_session', columns: ['session_id'])]
#[ORM\Index(name: 'IDX_tshh_player', columns: ['player_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_tshh_session_player', columns: ['session_id', 'player_id'])]
class TournamentSessionHostHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentSession $session;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

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

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
