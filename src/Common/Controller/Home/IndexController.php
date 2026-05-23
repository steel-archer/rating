<?php

declare(strict_types=1);

namespace App\Common\Controller\Home;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'home', methods: ['GET'])]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('home/index.html.twig');
    }
}
