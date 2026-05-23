<?php

declare(strict_types=1);

namespace App\Common\Controller\My\Venue;

use App\Common\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/venues/new', name: 'my_venue_new', methods: ['GET'])]
class CreateController extends AbstractController
{
    public function __invoke(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $town = $user->getPlayer()->getTown();

        return $this->render('my/venue/create.html.twig', [
            'townId' => $town?->getId(),
            'townName' => $town?->getName(),
        ]);
    }
}
