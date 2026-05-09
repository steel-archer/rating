<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Tournament;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Tournament> */
final class TournamentOwnerVoter extends Voter
{
    public const string EDIT = 'TOURNAMENT_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::EDIT && $subject instanceof Tournament;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $player = $user->getPlayer();

        return $player !== null && $subject->getCreatedBy()?->getId() === $player->getId();
    }
}
