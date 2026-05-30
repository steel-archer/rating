<?php

declare(strict_types=1);

namespace App\Common\Controller\Moderator\User;

use App\Common\DTO\Response\Moderator\BlockedUserDTO;
use App\Common\Mapping\Mapper;
use App\Common\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/users/{id}/block', name: 'moderator_user_block', requirements: ['id' => '\d+'], methods: ['GET'])]
class BlockPageController extends AbstractController
{
    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(
        int $id,
        UserRepository $userRepository,
        Mapper $mapper,
    ): Response {
        $user = $userRepository->find($id);

        if ($user === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('moderator/user_block.html.twig', [
            'blockedUser' => $mapper->map($user, BlockedUserDTO::class),
        ]);
    }
}
