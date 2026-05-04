<?php

namespace App\Controller\Claim;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/claim/submitted', name: 'claim_submitted', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class SubmittedController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('claim/submitted.html.twig');
    }
}
