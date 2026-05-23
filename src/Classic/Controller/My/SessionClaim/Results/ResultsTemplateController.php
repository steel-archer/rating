<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\SessionClaim\Results;

use App\Classic\Entity\TournamentSession;
use App\Common\Entity\User;
use App\Classic\Service\SessionResultUploadService;
use App\Classic\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/results/template', name: 'my_session_claim_results_template', requirements: ['id' => '\d+'], methods: ['GET'])]
class ResultsTemplateController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        SessionSquadService $squadService,
        SessionResultUploadService $uploadService,
    ): StreamedResponse {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        try {
            return $uploadService->generateTemplate($session);
        } catch (LogicException $ex) {
            throw $this->createNotFoundException($ex->getMessage());
        }
    }
}
