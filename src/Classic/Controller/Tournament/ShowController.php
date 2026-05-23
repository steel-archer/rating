<?php

declare(strict_types=1);

namespace App\Classic\Controller\Tournament;

use App\Classic\DTO\Response\Tournament\ModerationClaimDTO;
use App\Common\Entity\User;
use App\Classic\Enum\TournamentStatus;
use App\Common\Exception\EntityNotFoundException;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentModerationClaimRepository;
use App\Classic\Service\TournamentDisputeAccessService;
use App\Classic\Service\TournamentService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournament/{id}', name: 'tournament_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    /**
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     */
    public function __invoke(
        int $id,
        TournamentService $tournamentService,
        TournamentModerationClaimRepository $claimRepository,
        TournamentDisputeAccessService $disputeAccessService,
        Mapper $mapper,
    ): Response {
        $tournament = $tournamentService->get($id);

        /** @var User $user */
        $user = $this->getUser();

        if ($tournament->status !== TournamentStatus::Published->value) {
            $isOwner = $tournament->createdById === $user->getPlayer()?->getId();
            $isModerator = $this->isGranted('ROLE_MODERATOR');

            if (!$isOwner && !$isModerator) {
                throw $this->createNotFoundException();
            }
        }

        $claim = $this->isGranted('ROLE_MODERATOR')
            ? $claimRepository->findByTournamentId($id)
            : null;

        $tournamentEntity = $tournamentService->getEntity($id);
        $canViewDisputes = $disputeAccessService->canView($tournamentEntity, $user->getPlayer());

        return $this->render('tournament/show.html.twig', [
            'tournament' => $tournament,
            'moderationClaim' => $claim !== null ? $mapper->map($claim, ModerationClaimDTO::class) : null,
            'canViewDisputes' => $canViewDisputes,
            'canViewAppeals' => $canViewDisputes,
        ]);
    }
}
