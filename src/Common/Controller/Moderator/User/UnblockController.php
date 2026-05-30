<?php

declare(strict_types=1);

namespace App\Common\Controller\Moderator\User;

use App\Common\Attribute\RateLimited;
use App\Common\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/users/{id}/unblock', name: 'moderator_user_unblock', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('moderator')]
class UnblockController extends AbstractController
{
    /**
     * @throws NotFoundHttpException
     */
    public function __invoke(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $userRepository->find($id);

        if ($user === null) {
            throw $this->createNotFoundException();
        }

        $user->setBlockedReason(null);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
