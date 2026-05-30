<?php

declare(strict_types=1);

namespace App\Common\Controller\Player;

use App\Common\Contract\PlayerDetailProviderInterface;
use App\Common\DTO\Response\UserContactsDTO;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Common\Repository\PlayerRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/player/{id}', name: 'player_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(
        int $id,
        PlayerDetailProviderInterface $playerService,
        PlayerRepository $playerRepository,
        Mapper $mapper,
    ): Response {
        $player = $playerService->get($id);

        $contacts = null;
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentPlayerId = $currentUser->getPlayer()?->getId();

        $isOwnProfile = $currentPlayerId === $id;
        $isModerator = $this->isGranted('ROLE_MODERATOR');

        $userId = null;
        $userIsBlocked = false;
        $playerUser = $playerRepository->findUserByPlayerId($id);

        if ($playerUser !== null) {
            $userIsBlocked = $playerUser->isBlocked();

            if ($isOwnProfile || $isModerator) {
                $contacts = $mapper->map($playerUser, UserContactsDTO::class);
                $userId = $playerUser->getId();
            }
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
            'contacts' => $contacts,
            'canEditContacts' => $currentPlayerId === $id,
            'userId' => $isModerator ? $userId : null,
            'userIsBlocked' => $userIsBlocked,
        ]);
    }
}
