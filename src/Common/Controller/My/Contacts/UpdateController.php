<?php

declare(strict_types=1);

namespace App\Common\Controller\My\Contacts;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\UserContactsRequestDTO;
use App\Common\Entity\User;
use App\Common\Service\UserContactsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/contacts', name: 'my_contacts_update', methods: ['POST'])]
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
