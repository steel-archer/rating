<?php

declare(strict_types=1);

namespace App\Controller\Tournament;

use App\DTO\Response\Tournament\ModerationClaimDTO;
use App\Enum\TournamentStatus;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentModerationClaimRepository;
use App\Service\TournamentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}', name: 'tournament_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(
        int $id,
        TournamentService $tournamentService,
        TournamentModerationClaimRepository $claimRepository,
        Mapper $mapper,
    ): Response {
        $tournament = $tournamentService->get($id);

        if ($tournament->status !== TournamentStatus::Published->value) {
            /** @var User|null $user */
            $user = $this->getUser();
            $isOwner = $user !== null && $tournament->createdById === $user->getId();
            $isModerator = $this->isGranted('ROLE_MODERATOR');

            if (!$isOwner && !$isModerator) {
                throw $this->createNotFoundException();
            }
        }

        $claim = $this->isGranted('ROLE_MODERATOR')
            ? $claimRepository->findByTournamentId($id)
            : null;

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'moderationClaim' => $claim !== null ? $mapper->map($claim, ModerationClaimDTO::class) : null,
        ]);
    }
}
