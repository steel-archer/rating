<?php

declare(strict_types=1);

namespace App\Classic\Controller\Team;

use App\Classic\DTO\Request\TeamListRequestDTO;
use App\Common\Repository\CountryRepository;
use App\Classic\Repository\TeamRepository;
use App\Common\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teams/list', name: 'team_list', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly TownRepository $townRepository,
        private readonly CountryRepository $countryRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] TeamListRequestDTO $requestDto = new TeamListRequestDTO()): Response
    {
        return $this->render('team/_list.html.twig', [
            'teams' => $this->teamRepository->findForList($requestDto),
            'page' => $requestDto->page,
            'lastPage' => $this->teamRepository->getLastPageNumber($requestDto),
            'filters' => $requestDto->getFilters(),
            'sort' => $requestDto->sort,
            'dir' => $requestDto->dir,
            'toggleDir' => $requestDto->toggleDir(),
            'townName' => $requestDto->townId ? $this->townRepository->find($requestDto->townId)?->getName() : null,
            'countryName' => $requestDto->countryId ? $this->countryRepository->find($requestDto->countryId)?->getName() : null,
        ]);
    }
}
