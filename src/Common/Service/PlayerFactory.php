<?php

declare(strict_types=1);

namespace App\Common\Service;

use App\Common\Entity\Country;
use App\Common\Entity\Player;
use App\Common\Entity\Town;
use App\Common\Repository\CountryRepository;
use App\Common\Repository\TownRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

/**
 * Creates a brand new player from form input, resolving its town and country.
 * Shared by every form that lets the user add a player that is not yet in the
 * database (session squad, tournament officials, ...).
 */
final readonly class PlayerFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private TownRepository $townRepository,
        private CountryRepository $countryRepository,
    ) {
    }

    /**
     * Builds and persists a new player. Last and first name are mandatory; the
     * town (picked by id or typed by name) is optional but, when given, requires
     * a country alongside it.
     *
     * @throws LogicException
     */
    public function create(
        ?string $lastName,
        ?string $firstName,
        ?string $patronymic,
        ?int $townId,
        ?string $townName,
        ?int $countryId,
    ): Player {
        if ($lastName === null || trim($lastName) === '') {
            throw new LogicException('player.error.last_name_required');
        }

        if ($firstName === null || trim($firstName) === '') {
            throw new LogicException('player.error.first_name_required');
        }

        $player = new Player();
        $player->setLastName(trim($lastName));
        $player->setFirstName(trim($firstName));
        $player->setPatronymic($patronymic !== null && trim($patronymic) !== '' ? trim($patronymic) : null);
        $player->setTown($this->resolveTown($townId, $townName, $countryId));

        $this->em->persist($player);

        return $player;
    }

    /**
     * Resolves the town for a brand new player. A town picked from the list
     * (townId) is used as is; a hand-typed name is matched within the chosen
     * country or created there. Both forms require a country.
     *
     * @throws LogicException
     */
    private function resolveTown(?int $townId, ?string $townName, ?int $countryId): ?Town
    {
        if ($townId !== null) {
            return $this->townRepository->find($townId)
                ?? throw new LogicException('common.not_found');
        }

        if ($townName === null || trim($townName) === '') {
            return null;
        }

        $country = $this->resolveCountry($countryId);
        $trimmedName = trim($townName);

        $existing = $this->townRepository->findOneBy(['name' => $trimmedName, 'country' => $country]);
        if ($existing !== null) {
            return $existing;
        }

        $town = new Town();
        $town->setName($trimmedName);
        $town->setCountry($country);
        $this->em->persist($town);

        return $town;
    }

    /**
     * @throws LogicException
     */
    private function resolveCountry(?int $countryId): Country
    {
        if ($countryId === null) {
            throw new LogicException('player.error.country_required');
        }

        return $this->countryRepository->find($countryId)
            ?? throw new LogicException('common.not_found');
    }
}
