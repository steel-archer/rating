<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\Tournament;

use App\Classic\DTO\Response\My\ModerationClaimDTO;
use App\Classic\DTO\Response\My\TournamentDocumentDTO;
use App\Classic\DTO\Response\My\TournamentEditDTO;
use App\Classic\DTO\Response\My\TournamentOfficialDTO;
use App\Classic\Entity\Tournament;
use App\Common\Mapping\Mapper;
use App\Classic\Repository\TournamentDocumentRepository;
use App\Classic\Repository\TournamentModerationClaimRepository;
use App\Classic\Repository\TournamentOfficialRepository;
use App\Classic\Security\TournamentOrganizerVoter;
use App\Classic\Service\TournamentValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/{id}/edit', name: 'my_tournament_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
class EditController extends AbstractController
{
    public function __invoke(
        Tournament $tournament,
        TournamentModerationClaimRepository $claimRepository,
        TournamentOfficialRepository $officialRepository,
        TournamentDocumentRepository $documentRepository,
        TournamentValidator $validator,
        Mapper $mapper,
    ): Response {
        if (!$this->isGranted(TournamentOrganizerVoter::EDIT, $tournament)) {
            throw $this->createNotFoundException();
        }

        $readonly = $tournament->isStarted();

        $moderationClaim = $claimRepository->findByTournament($tournament);
        $claimDto = $moderationClaim !== null
            ? $mapper->map($moderationClaim, ModerationClaimDTO::class)
            : null;

        $officials = $mapper->mapMultiple(
            $officialRepository->findByTournament($tournament),
            TournamentOfficialDTO::class,
        );

        return $this->render('my/tournament_edit.html.twig', [
            'tournament' => $mapper->map($tournament, TournamentEditDTO::class),
            'claim' => $claimDto,
            'officials' => $officials,
            'documents' => $mapper->mapMultiple(
                $documentRepository->findByTournament($tournament),
                TournamentDocumentDTO::class,
            ),
            'publishErrors' => $validator->validatePublish($tournament),
            'readonly' => $readonly,
        ]);
    }
}
