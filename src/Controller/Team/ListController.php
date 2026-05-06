<?php

namespace App\Controller\Team;

use App\DTO\Request\TeamListRequestDTO;
use App\Repository\CountryRepository;
use App\Repository\TeamRepository;
use App\Repository\TownRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/teams/list', name: 'team_list', methods: ['GET'])]
final class ListController extends AbstractController
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly TownRepository $townRepository,
        private readonly CountryRepository $countryRepository,
    ) {
    }

    public function __invoke(#[MapQueryString] TeamListRequestDTO $requestDto = new TeamListRequestDTO()): Response
    {
        try {
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
        } catch (Throwable $ex) { // @codeCoverageIgnoreStart
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex); // @codeCoverageIgnoreEnd
        }
    }
}
