<?php

declare(strict_types=1);

namespace App\Controller\Player;

use App\Exception\EntityNotFoundException;
use App\Service\PlayerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/player/{id}', name: 'player_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(int $id, PlayerService $playerService): Response
    {
        try {
            $player = $playerService->get($id);
        } catch (EntityNotFoundException) {
            throw $this->createNotFoundException();
        } catch (Throwable $ex) {
            throw new ServiceUnavailableHttpException(message: $ex->getMessage(), previous: $ex);
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
        ]);
    }
}
