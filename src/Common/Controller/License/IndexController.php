<?php

declare(strict_types=1);

namespace App\Common\Controller\License;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/license', name: 'license', methods: ['GET'])]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('license/index.html.twig');
    }
}
