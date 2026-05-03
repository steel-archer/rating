<?php

namespace App\Controller\Tournament;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tournaments', name: 'tournament_index')]
final class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('tournament/index.html.twig');
    }
}
