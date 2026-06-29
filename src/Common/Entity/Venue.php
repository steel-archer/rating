<?php

declare(strict_types=1);

namespace App\Common\Entity;

use App\Common\Repository\VenueRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VenueRepository::class)]
#[ORM\Table(name: 'common_venue')]
#[ORM\UniqueConstraint(name: 'UQ_venue_name_town', columns: ['name', 'town_id'])]
#[ORM\Index(name: 'IDX_venue_town', columns: ['town_id'])]
#[ORM\Index(name: 'IDX_venue_created_by', columns: ['created_by_id'])]
class Venue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Town $town;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Player $createdBy = null;

    #[ORM\Column]
    private bool $isOnline = false;

    #[ORM\Column]
    private bool $isApproved = false;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getTown(): Town
    {
        return $this->town;
    }

    public function setTown(Town $town): static
    {
        $this->town = $town;

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

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function isApproved(): bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;

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
