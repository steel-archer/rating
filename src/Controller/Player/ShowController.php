<?php

declare(strict_types=1);

namespace App\Controller\Player;

use App\Entity\User;
use App\Repository\PlayerRepository;
use App\Service\PlayerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}', name: 'player_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(
        int $id,
        PlayerService $playerService,
        PlayerRepository $playerRepository,
    ): Response {
        $player = $playerService->get($id);

        $playerEmail = null;
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser !== null) {
            $isOwnProfile = $currentUser->getPlayer()?->getId() === $id;
            $isModerator = $this->isGranted('ROLE_MODERATOR');

            if ($isOwnProfile || $isModerator) {
                $playerEmail = $playerRepository->findEmailByPlayerId($id);
            }
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
            'playerEmail' => $playerEmail,
        ]);
    }
}
