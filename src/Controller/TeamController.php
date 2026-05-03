<?php

namespace App\Controller;

use App\Exception\EntityNotFoundException;
use App\Service\TeamService;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team')]
final class TeamController extends AbstractController
{
    #[Route('/{id}', name: 'team_show', requirements: ['id' => '\d+'])]
    public function show(int $id, TeamService $teamService): Response
    {
        try {
            $team = $teamService->get($id);
        } catch (EntityNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (DBALException $exception) {
            throw new ServiceUnavailableHttpException(message: 'Database error', previous: $exception);
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }
}
