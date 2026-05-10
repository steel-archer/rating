<?php

declare(strict_types=1);

namespace App\Controller\My\Contacts;

use App\Attribute\RateLimited;
use App\DTO\Request\UserContactsRequestDTO;
use App\Entity\User;
use App\Service\UserContactsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/contacts', name: 'my_contacts_update', methods: ['POST'])]
#[IsGranted('ROLE_PLAYER')]
#[RateLimited('mutation')]
class UpdateController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] UserContactsRequestDTO $dto,
        UserContactsService $contactsService,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $contactsService->updateFromDto($user, $dto);
        $entityManager->flush();

        return $this->json(['success' => true]);
    }
}
