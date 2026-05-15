<?php

declare(strict_types=1);

namespace App\Controller\My\Dispute;

use App\DTO\Response\My\DisputeTournamentDTO;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\TournamentSessionTeamAnswerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/disputes', name: 'my_disputes', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(TournamentSessionTeamAnswerRepository $answerRepository, Mapper $mapper): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        if ($player === null) {
            throw $this->createAccessDeniedException();
        }

        $stats = $answerRepository->findJuryTournamentStats($player);

        return $this->render('my/disputes.html.twig', [
            'tournaments' => $mapper->mapMultiple($stats, DisputeTournamentDTO::class),
        ]);
    }
}
