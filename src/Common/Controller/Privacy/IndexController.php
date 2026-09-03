<?php

declare(strict_types=1);

namespace App\Common\Controller\Privacy;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/privacy', name: 'privacy', methods: ['GET'])]
class IndexController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('privacy/index.html.twig');
    }
}
