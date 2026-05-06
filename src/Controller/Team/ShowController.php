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
        } catch (EntityNotFoundException $ex) {
            throw new NotFoundHttpException($ex->getMessage(), $ex);
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }
}
