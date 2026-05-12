<?php

declare(strict_types=1);

namespace App\Controller\My\SessionClaim;

use App\Attribute\RateLimited;
use App\Entity\TournamentSession;
use App\Entity\User;
use App\Service\SessionResultUploadService;
use App\Service\SessionSquadService;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/session-claims/{id}/results/upload', name: 'my_session_claim_results_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
#[RateLimited('upload')]
class ResultsUploadController extends AbstractController
{
    public function __invoke(
        TournamentSession $session,
        Request $request,
        SessionSquadService $squadService,
        SessionResultUploadService $uploadService,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $squadService->ensureCanManageSquad($session, $user->getPlayer());

        $file = $request->files->get('file');
        if ($file === null) {
            return $this->json(['errors' => ['results.error.no_file']], 422);
        }

        try {
            $errors = $uploadService->uploadResults($session, $file);
        } catch (LogicException $ex) {
            return $this->json(['errors' => [$ex->getMessage()]], 422);
        }

        if ($errors !== []) {
            return $this->json(['errors' => $errors], 422);
        }

        return $this->json(['success' => true]);
    }
}
