<?php

declare(strict_types=1);

namespace App\Common\Controller\My\Venue;

use App\Common\DTO\Response\My\VenueListDTO;
use App\Common\Entity\User;
use App\Common\Mapping\Mapper;
use App\Common\Repository\VenueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/my/venues', name: 'my_venues', methods: ['GET'])]
class ListController extends AbstractController
{
    public function __invoke(VenueRepository $venueRepository, Mapper $mapper): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $venues = $mapper->mapMultiple(
            $venueRepository->findByCreator($user->getPlayer()),
            VenueListDTO::class,
        );

        return $this->render('my/venue/list.html.twig', [
            'venues' => $venues,
        ]);
    }
}
