<?php

declare(strict_types=1);

namespace App\Common\Controller\Moderator\User;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\BlockUserRequestDTO;
use App\Common\Entity\User;
use App\Common\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/users/{id}/block', name: 'moderator_user_block_action', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class BlockActionController extends AbstractController
{
    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(
        int $id,
        #[MapRequestPayload] BlockUserRequestDTO $dto,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $userRepository->find($id);

        if ($user === null) {
            throw $this->createNotFoundException();
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['error' => 'block.error.self'], 422);
        }

        if (in_array('ROLE_MODERATOR', $user->getRoles(), true) || in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->json(['error' => 'block.error.moderator'], 422);
        }

        $user->setBlockedReason($dto->reason);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
