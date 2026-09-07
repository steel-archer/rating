<?php

declare(strict_types=1);

namespace App\Classic\Entity;

use App\Common\Entity\Player;
use App\Classic\Repository\TournamentDocumentDownloadRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TournamentDocumentDownloadRepository::class)]
#[ORM\Table(name: 'classic_tournament_document_download')]
#[ORM\Index(name: 'IDX_tdd_document', columns: ['document_id'])]
#[ORM\Index(name: 'IDX_tdd_player', columns: ['player_id'])]
class TournamentDocumentDownload
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private TournamentDocument $document;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Player $player;

    #[ORM\Column]
    private DateTimeImmutable $downloadedAt;

    public function __construct()
    {
        $this->downloadedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): TournamentDocument
    {
        return $this->document;
    }

    public function setDocument(TournamentDocument $document): static
    {
        $this->document = $document;

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

    public function getDownloadedAt(): DateTimeImmutable
    {
        return $this->downloadedAt;
    }
}
