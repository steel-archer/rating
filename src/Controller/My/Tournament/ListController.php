<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use App\DTO\Request\Tournament\My\ListRequestDTO;
use App\DTO\Response\My\TournamentListDTO;
use App\DTO\Response\My\TournamentModerationListDTO;
use App\Entity\TournamentModerationClaim;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentModerationClaimRepository;
use App\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments', name: 'my_tournaments', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class ListController extends AbstractController
{
    public function __invoke(
        #[MapQueryString] ?ListRequestDTO $dto,
        TournamentRepository $tournamentRepository,
        TournamentModerationClaimRepository $claimRepository,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $dto ??= new ListRequestDTO();

        $tournaments = $tournamentRepository->findByCreator($user->getPlayer(), $dto->sort, $dto->page);
        $claimEntities = $claimRepository->findByTournaments($tournaments);
        $total = $tournamentRepository->countByCreator($user->getPlayer());
        $lastPage = max(1, (int) ceil($total / 50));

        $tournamentDtos = $mapper->mapMultiple($tournaments, TournamentListDTO::class);

        $claims = [];
        foreach ($claimEntities as $tournamentId => $claim) {
            /** @var TournamentModerationClaim $claim */
            $claims[$tournamentId] = $mapper->map($claim, TournamentModerationListDTO::class);
        }

        return $this->render('my/tournaments.html.twig', [
            'tournaments' => $tournamentDtos,
            'claims' => $claims,
            'sort' => $dto->sort,
            'page' => $dto->page,
            'lastPage' => $lastPage,
        ]);
    }
}
