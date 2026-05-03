<?php

namespace App\Controller\Team;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/teams', name: 'team_index', methods: ['GET'])]
final class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('team/index.html.twig');
    }
}
