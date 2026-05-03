<?php

namespace App\Controller\Player;

use App\Exception\EntityNotFoundException;
use App\Service\PlayerService;
use Doctrine\DBAL\Exception as DBALException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}', name: 'player_show', requirements: ['id' => '\d+'])]
final class ShowController extends AbstractController
{
    public function __invoke(int $id, PlayerService $playerService): Response
    {
        try {
            $player = $playerService->get($id);
        } catch (EntityNotFoundException $exception) {
            throw new NotFoundHttpException($exception->getMessage(), $exception);
        } catch (DBALException $exception) {
            throw new ServiceUnavailableHttpException(message: 'Database error', previous: $exception);
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
        ]);
    }
}
