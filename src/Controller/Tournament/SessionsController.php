<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Response\Tournament\TournamentContextDTO;
use App\Entity\Tournament;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/sessions', name: 'tournament_sessions', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        VenueRepresentativeRepository $representativeRepository,
        Mapper $mapper,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        $player = $user?->getPlayer();

        $canSubmitClaim = false;
        if ($player !== null) {
            $canSubmitClaim = $representativeRepository->hasVenuesByPlayer($player);
        }

        return $this->render('tournament/sessions.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'canSubmitClaim' => $canSubmitClaim,
        ]);
    }
}
