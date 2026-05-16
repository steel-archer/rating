<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Attribute\RateLimited;
use App\Entity\User;
use App\Repository\TournamentOfficialRepository;
use App\Repository\TournamentSessionRepository;
use App\Service\UserContactsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(
    '/api/sessions/{sessionId}/contacts',
    name: 'api_session_contacts',
    requirements: ['sessionId' => '\d+'],
    methods: ['GET'],
)]
#[RateLimited('api_suggest')]
class SessionClaimContactsController extends AbstractController
{
    public function __invoke(
        int $sessionId,
        TournamentSessionRepository $sessionRepository,
        TournamentOfficialRepository $officialRepository,
        UserContactsService $contactsService,
    ): JsonResponse {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $currentPlayer = $currentUser->getPlayer();

        $session = $sessionRepository->findWithRepresentativeUser($sessionId);
        if ($session === null) {
            return $this->json(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        $tournament = $session->getTournament();

        $isOrganizer = $officialRepository->isOrganizer($currentPlayer, $tournament);
        $isModerator = $this->isGranted('ROLE_MODERATOR');

        if (!$isOrganizer && !$isModerator) {
            return $this->json(['error' => 'forbidden'], Response::HTTP_FORBIDDEN);
        }

        $representativeUser = $session->getRepresentative()->getUser();

        return $this->json($contactsService->getContacts($representativeUser));
    }
}
