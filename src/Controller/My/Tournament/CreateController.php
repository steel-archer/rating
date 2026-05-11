<?php

declare(strict_types=1);

namespace App\Controller\My\Tournament;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/tournaments/new', name: 'my_tournament_new', methods: ['GET'])]
class CreateController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('my/tournament_form.html.twig');
    }
}
