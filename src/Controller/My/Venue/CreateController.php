<?php

namespace App\Controller\My\Venue;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/venues/new', name: 'my_venue_new', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class CreateController extends AbstractController
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('my/venue/create.html.twig', [
            'town' => $user->getPlayer()->getTown(),
        ]);
    }
}
