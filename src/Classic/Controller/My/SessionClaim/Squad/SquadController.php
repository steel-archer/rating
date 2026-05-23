<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Squad;

use App\Classic\DTO\Response\My\SquadSessionDTO;
use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Classic\Service\SessionSquadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/squad', name: 'my_session_claim_squad', requirements: ['id' => '\d+'], methods: ['GET'])]
class SquadController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionSquadService $service,
        Mapper $mapper,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $service->ensureCanManageSquad($session, $user->getPlayer());

        return $this->render('my/session_claim_squad.html.twig', [
            'session' => $mapper->map($session, SquadSessionDTO::class),
        ]);
    }
}
