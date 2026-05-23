<?php

declare(strict_types=1);

namespace App\Common\Controller\Venue;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/venues', name: 'venue_index', methods: ['GET'])]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('venue/index.html.twig');
    }
}
