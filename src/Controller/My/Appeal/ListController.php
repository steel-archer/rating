<?php

declare(strict_types=1);

namespace App\Controller\My\Appeal;

use App\Entity\User;
use App\Service\AppealService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/appeals', name: 'my_appeals', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(AppealService $appealService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $tournaments = $appealService->getJuryTournamentList($user->getPlayer());

        return $this->render('my/appeals.html.twig', [
            'tournaments' => $tournaments,
        ]);
    }
}
