<?php

namespace App\Controller\My\Tournament;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/tournaments/new', name: 'my_tournament_new', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class CreateController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('my/tournament_form.html.twig');
    }
}
