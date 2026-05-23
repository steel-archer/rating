<?php

declare(strict_types=1);

namespace App\Common\Controller\My\Venue;

use App\Common\Attribute\RateLimited;
use App\Common\DTO\Request\Venue\UpdateRequestDTO;
use App\Common\Entity\User;
use App\Common\Entity\Venue;
use App\Common\Service\VenueManagementService;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/venues/{id}', name: 'my_venue_update', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('mutation')]
class UpdateController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(
        Venue $venue,
        #[MapRequestPayload] UpdateRequestDTO $dto,
        VenueManagementService $service,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($venue->getCreatedBy()?->getId() !== $user->getPlayer()->getId()) {
            return $this->json(['error' => 'common.not_found'], 404);
        }

        try {
            $service->updateRepresentatives($venue, $dto);
        } catch (LogicException $ex) {
            return $this->json(['error' => $ex->getMessage()], 422);
        }

        $this->addFlash('success', 'venue.my.saved');

        return $this->json(['success' => true]);
    }
}
