<?php

namespace App\Controller\My\Venue;

use App\DTO\Request\Venue\CreateRequestDTO;
use App\Entity\User;
use App\Service\VenueManagementService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[Route('/my/venues', name: 'my_venue_store', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
#[IsCsrfTokenValid('venue_create')]
final class StoreController extends AbstractController
{
    public function __invoke(
        #[MapRequestPayload] CreateRequestDTO $dto,
        VenueManagementService $service,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() === null) {
            throw $this->createAccessDeniedException();
        }

        try {
            $service->create($dto->name, $dto->townId, $user);
        } catch (LogicException $ex) {
            $this->addFlash('error', $ex->getMessage());

            return $this->redirectToRoute('my_venue_new');
        } catch (Throwable) {
            $this->addFlash('error', 'common.error');

            return $this->redirectToRoute('my_venue_new');
        }

        $this->addFlash('success', 'venue.my.created');

        return $this->redirectToRoute('my_venues');
    }
}
