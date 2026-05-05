<?php

namespace App\Controller\My;

use App\DTO\Request\Tournament\My\CreateRequestDTO;
use App\Entity\User;
use App\Service\TournamentManagementService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments', name: 'my_tournament_store', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[IsCsrfTokenValid('tournament_create')]
final class TournamentStoreController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateRequestDTO $dto,
        TournamentManagementService $service,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() === null) {
            throw $this->createAccessDeniedException();
        }

        $tournament = $service->create($dto->name, $user);

        return $this->redirectToRoute('my_tournament_edit', ['id' => $tournament->getId()]);
    }
}
