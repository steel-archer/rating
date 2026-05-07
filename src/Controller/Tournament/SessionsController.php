<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Enum\SessionClaimStatus;
use App\Entity\Tournament;
use App\Entity\User;
use App\Repository\SessionClaimRepository;
use App\Repository\TournamentOfficialRepository;
use App\Repository\VenueRepresentativeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/sessions', name: 'tournament_sessions', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        SessionClaimRepository $sessionClaimRepository,
        TournamentOfficialRepository $officialRepository,
        VenueRepresentativeRepository $representativeRepository,
    ): Response {
        try {
            /** @var User|null $user */
            $user = $this->getUser();
            $player = $user?->getPlayer();

            $isOrganizer = false;
            $venues = [];
            $claims = [];

            if ($player !== null) {
                $isOrganizer = $officialRepository->isOrganizer($player, $tournament);
                $venues = $representativeRepository->findVenuesByPlayer($player);
            }

            if ($isOrganizer) {
                $claims = $sessionClaimRepository->findByTournamentAndStatus($tournament->getId(), SessionClaimStatus::Pending);
            } elseif ($player !== null) {
                $claims = $sessionClaimRepository->findByTournamentAndPlayer($tournament->getId(), $player);
            }

            return $this->render('tournament/sessions.html.twig', [
                'tournament' => $tournament,
                'isOrganizer' => $isOrganizer,
                'venues' => $venues,
                'claims' => $claims,
                'canSubmitClaim' => $venues !== [],
            ]);
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
