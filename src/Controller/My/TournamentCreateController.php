<?php

namespace App\Controller\My;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/new', name: 'my_tournament_new', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class TournamentCreateController extends AbstractController
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getPlayer() === null) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('my/tournament_form.html.twig');
    }
}
