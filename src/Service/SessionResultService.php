<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Response\Tournament\SessionTeamDTO;
use App\Entity\TournamentSession;
use App\Helper\SessionTeamResultBuilder;
use App\Repository\TournamentSessionTeamRepository;
use Doctrine\DBAL\Exception as DbalException;

class SessionResultService
{
    public function __construct(
        private TournamentSessionTeamRepository $sessionTeamRepository,
        private SessionTeamResultBuilder $resultBuilder,
    ) {
    }

    /**
     * @return list<SessionTeamDTO>
     * @throws DbalException
     */
    public function getSessionResults(TournamentSession $session): array
    {
        $sessionTeams = $this->sessionTeamRepository->findBy(
            ['tournamentSession' => $session],
            ['score' => 'DESC'],
        );

        return $this->resultBuilder->build($sessionTeams, $session->getTournament()->getSeason());
    }
}
