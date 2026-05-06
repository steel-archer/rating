<?php

namespace App\Controller\My\Tournament;

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
#[IsGranted('ROLE_PLAYER')]
final class ListController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] ?ListRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        TournamentModerationClaimRepository $claimRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $dto ??= new ListRequestDTO();
        $sort = strtolower($dto->sort) === 'asc' ? 'asc' : 'desc';
        $page = max(1, $dto->page);

        $tournaments = $tournamentRepository->findByCreator($user, $sort, $page);
        $claims = $claimRepository->findByTournaments($tournaments);
        $total = $tournamentRepository->countByCreator($user);
        $lastPage = max(1, (int) ceil($total / 50));

        return $this->render('my/tournaments.html.twig', [
            'tournaments' => $tournaments,
            'claims' => $claims,
            'sort' => $sort,
            'page' => $page,
            'lastPage' => $lastPage,
        ]);
    }
}
