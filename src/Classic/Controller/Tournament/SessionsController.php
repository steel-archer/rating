<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Common\Repository\VenueRepresentativeRepository;
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
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('tournament/sessions.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'canSubmitClaim' => $tournament->isRegistrationOpen()
                && $representativeRepository->hasVenuesByPlayer($user->getPlayer()),
        ]);
    }
}
