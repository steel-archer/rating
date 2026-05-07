<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\Enum\TournamentStatus;
use App\Entity\User;
use App\Exception\EntityNotFoundException;
use App\Repository\TournamentModerationClaimRepository;
use App\Service\TournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/tournament/{id}', name: 'tournament_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentService $tournamentService,
        TournamentModerationClaimRepository $claimRepository,
    ): Response {
        try {
            $tournament = $tournamentService->get($id);
        } catch (EntityNotFoundException) {
            throw $this->createNotFoundException();
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }

        if ($tournament->status !== TournamentStatus::Published->value) {
            /** @var User|null $user */
            $user = $this->getUser();
            $isOwner = $user !== null && $tournament->createdById === $user->getId();
            $isModerator = $this->isGranted('ROLE_MODERATOR');

            if (!$isOwner && !$isModerator) {
                throw $this->createNotFoundException();
            }
        }

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'moderationClaim' => $this->isGranted('ROLE_MODERATOR')
                ? $claimRepository->findByTournamentId($id)
                : null,
        ]);
    }
}
