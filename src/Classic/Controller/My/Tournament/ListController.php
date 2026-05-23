<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament;

use App\Classic\DTO\Request\Tournament\My\ListRequestDTO;
use App\Classic\DTO\Response\My\TournamentListDTO;
use App\Classic\DTO\Response\My\TournamentModerationListDTO;
use App\Classic\Entity\TournamentModerationClaim;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentModerationClaimRepository;
use App\Classic\Repository\TournamentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments', name: 'my_tournaments', methods: ['GET'])]
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

        $claims = array_map(static function ($claim) use ($mapper) {
            return $mapper->map($claim, TournamentModerationListDTO::class);
        }, $claimEntities);

        return $this->render('my/tournaments.html.twig', [
            'tournaments' => $tournamentDtos,
            'claims' => $claims,
            'sort' => $dto->sort,
            'page' => $dto->page,
            'lastPage' => $lastPage,
        ]);
    }
}
