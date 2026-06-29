<?php

declare(strict_types=1);

namespace App\Common\Entity;

use App\Common\Repository\VenueRepresentativeRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenueRepresentativeRepository::class)]
#[ORM\Table(name: 'common_venue_representative')]
#[ORM\UniqueConstraint(name: 'UQ_venue_player', columns: ['venue_id', 'player_id'])]
#[ORM\Index(name: 'IDX_vr_venue', columns: ['venue_id'])]
#[ORM\Index(name: 'IDX_vr_player', columns: ['player_id'])]
class VenueRepresentative
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Venue $venue;

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

    public function getVenue(): Venue
    {
        return $this->venue;
    }

    public function setVenue(Venue $venue): static
    {
        $this->venue = $venue;

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

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
