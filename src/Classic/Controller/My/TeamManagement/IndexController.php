<?php

declare(strict_types=1);

namespace App\Classic\Controller\My\TeamManagement;

use App\Common\Entity\User;
use App\Classic\Service\TeamManagementService;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/team', name: 'my_team_management', methods: ['GET'])]
class IndexController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     */
    public function __invoke(TeamManagementService $teamManagementService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $player = $user->getPlayer();

        $teamManagement = $teamManagementService->getForPlayer($player);

        return $this->render('my/team_management.html.twig', [
            'team' => $teamManagement,
        ]);
    }
}
