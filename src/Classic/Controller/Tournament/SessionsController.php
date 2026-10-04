<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Response\Tournament\TournamentContextDTO;
use App\Classic\Entity\Tournament;
use App\Classic\Service\TournamentAccessService;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Common\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}/sessions', name: 'tournament_sessions', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsController extends AbstractController
{
    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(
        Tournament $tournament,
        TournamentAccessService $accessService,
        VenueRepresentativeRepository $representativeRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        // Unpublished tournaments stay hidden here just like on the main card.
        if (!$accessService->isVisible($tournament, $player, $this->isGranted('ROLE_MODERATOR'))) {
            throw $this->createNotFoundException();
        }

        $registrationOpen = $tournament->isRegistrationOpen();
        $hasApprovedVenue = $player !== null && $representativeRepository->hasVenuesByPlayer($player);

        return $this->render('tournament/sessions.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentContextDTO::class),
            'canSubmitClaim' => $registrationOpen && $hasApprovedVenue,
            // Prompt the player to get an approved venue when that is the only thing missing.
            'needsApprovedVenue' => $registrationOpen && $player !== null && !$hasApprovedVenue,
        ]);
    }
}
