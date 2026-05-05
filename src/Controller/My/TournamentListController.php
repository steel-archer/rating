<?php

namespace App\Controller\My;

use App\DTO\Request\Tournament\My\ListRequestDTO;
use App\Entity\User;
use App\Repository\TournamentModerationClaimRepository;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments', name: 'my_tournaments', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class TournamentListController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] ?ListRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        TournamentModerationClaimRepository $claimRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() === null) {
            throw $this->createAccessDeniedException();
        }

        $dto ??= new ListRequestDTO();
        $sort = strtolower($dto->sort) === 'asc' ? 'asc' : 'desc';

        $tournaments = $tournamentRepository->findByCreator($user, $sort);
        $claims = $claimRepository->findByTournaments($tournaments);

        return $this->render('my/tournaments.html.twig', [
            'tournaments' => $tournaments,
            'claims' => $claims,
            'sort' => $sort,
        ]);
    }
}
