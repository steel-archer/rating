<?php

declare(strict_types=1);

namespace App\Controller\Player;

use App\Service\PlayerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}', name: 'player_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(int $id, PlayerService $playerService): Response
    {
        $player = $playerService->get($id);

        return $this->render('player/show.html.twig', [
            'player' => $player,
        ]);
    }
}
