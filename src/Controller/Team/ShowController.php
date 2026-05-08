<?php

declare(strict_types=1);

namespace App\Controller\Team;

use App\Service\TeamService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/team/{id}', name: 'team_show', requirements: ['id' => '\d+'], methods: ['GET'])]
class ShowController extends AbstractController
{
    public function __invoke(int $id, TeamService $teamService): Response
    {
        $team = $teamService->get($id);

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }
}
