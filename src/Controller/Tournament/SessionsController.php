<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Enum\SessionClaimStatus;
use App\Entity\User;
use App\Repository\SessionClaimRepository;
use App\Repository\TournamentRepository;
use App\Repository\VenueRepresentativeRepository;
use App\Service\SessionClaimService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}/sessions', name: 'tournament_sessions', requirements: ['id' => '\d+'], methods: ['GET'])]
class SessionsController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentRepository $tournamentRepository,
        SessionClaimRepository $sessionClaimRepository,
        SessionClaimService $service,
        VenueRepresentativeRepository $representativeRepository,
    ): Response {
        try {
            $tournament = $tournamentRepository->find($id)
                ?? throw new NotFoundHttpException("Tournament #$id not found");

            /** @var User|null $user */
            $user = $this->getUser();
            $player = $user?->getPlayer();

            $isOrganizer = false;
            $venues = [];
            $claims = [];

            if ($player !== null) {
                $isOrganizer = $service->isOrganizer($player, $tournament);
                $venues = $representativeRepository->findVenuesByPlayer($player);
            }

            if ($isOrganizer) {
                $claims = $sessionClaimRepository->findByTournamentAndStatus($id, SessionClaimStatus::Pending);
            } elseif ($player !== null) {
                $claims = $sessionClaimRepository->findByTournamentAndPlayer($id, $player);
            }

            return $this->render('tournament/sessions.html.twig', [
                'tournament' => $tournament,
                'isOrganizer' => $isOrganizer,
                'venues' => $venues,
                'claims' => $claims,
                'canSubmitClaim' => $venues !== [],
            ]);
        } catch (NotFoundHttpException $ex) {
            throw $ex;
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }
    }
}
