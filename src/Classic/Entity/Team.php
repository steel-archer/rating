<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Common\Entity\Town;
use App\Classic\Repository\TeamRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: 'classic_team')]
#[ORM\Index(name: 'IDX_team_town', columns: ['town_id'])]
class Team
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
}
