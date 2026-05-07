<?php

declare(strict_types=1);

namespace App\Controller\My\Venue;

use App\DTO\Response\My\VenueListDTO;
use App\Entity\User;
use App\Mapping\Mapper;
use App\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/my/venues', name: 'my_venues', methods: ['GET'])]
#[IsGranted('ROLE_PLAYER')]
class ListController extends AbstractController
{
    public function __invoke(VenueRepository $venueRepository, Mapper $mapper): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $venues = $mapper->mapMultiple(
            $venueRepository->findByCreator($user),
            VenueListDTO::class,
        );

        return $this->render('my/venue/list.html.twig', [
            'venues' => $venues,
        ]);
    }
}
