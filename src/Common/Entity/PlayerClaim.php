<?php

declare(strict_types=1);

namespace App\Common\Entity;

use App\Common\Enum\PlayerClaimStatus;
use App\Common\Repository\PlayerClaimRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerClaimRepository::class)]
#[ORM\Index(name: 'IDX_player_claim_user', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_player_claim_player', columns: ['player_id'])]
#[ORM\Index(name: 'IDX_player_claim_town', columns: ['town_id'])]
class PlayerClaim
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne]
    private ?Player $player = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private string $lastName;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $patronymic = null;

    #[ORM\ManyToOne]
    private ?Town $town = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $townName = null;

    #[ORM\Column(length: 20, enumType: PlayerClaimStatus::class)]
    private PlayerClaimStatus $status = PlayerClaimStatus::Pending;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function setPatronymic(?string $patronymic): static
    {
        $this->patronymic = $patronymic;

        return $this;
    }

    public function getTown(): ?Town
    {
        return $this->town;
    }

    public function setTown(?Town $town): static
    {
        $this->town = $town;

        return $this;
    }

    public function getTownName(): ?string
    {
        return $this->townName;
    }

    public function setTownName(?string $townName): static
    {
        $this->townName = $townName;

        return $this;
    }

    public function getStatus(): PlayerClaimStatus
    {
        return $this->status;
    }

    public function setStatus(PlayerClaimStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->player === null;
    }
}
