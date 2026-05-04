<?php

namespace App\Controller\Team;

use App\Exception\EntityNotFoundException;
use App\Service\TeamService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/team/{id}', name: 'team_show', requirements: ['id' => '\d+'], methods: ['GET'])]
final class ShowController extends AbstractController
{
    public function __invoke(int $id, TeamService $teamService): Response
    {
        try {
            $team = $teamService->get($id);
        } catch (EntityNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (Throwable $exception) { // @codeCoverageIgnoreStart
            throw new ServiceUnavailableHttpException(message: $exception->getMessage(), previous: $exception); // @codeCoverageIgnoreEnd
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }
}
