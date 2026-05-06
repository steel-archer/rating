<?php

namespace App\Controller\My\Venue;

use App\Entity\User;
use App\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/venues', name: 'my_venues', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
final class ListController extends AbstractController
{
    public function __invoke(VenueRepository $venueRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('my/venue/list.html.twig', [
            'venues' => $venueRepository->findByCreator($user),
        ]);
    }
}
