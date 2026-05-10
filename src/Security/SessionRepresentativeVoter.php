<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\TournamentSession;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, TournamentSession> */
final class SessionRepresentativeVoter extends Voter
{
    public const string MANAGE = 'SESSION_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::MANAGE && $subject instanceof TournamentSession;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $player = $user->getPlayer();
        if ($player === null) {
            return false;
        }

        return $subject->getRepresentative()->getId() === $player->getId();
    }
}
