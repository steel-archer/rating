<?php

declare(strict_types=1);

namespace App\Common\Service;

use App\Common\Contract\MenuActionCountsProviderInterface;
use App\Common\DTO\Response\ModerationCountsDTO;
use App\Common\DTO\Response\MyActionCountsDTO;
use App\Common\Entity\Player;
use App\Common\Enum\CacheTag;
use App\Common\Repository\PlayerClaimRepository;
use App\Common\Repository\VenueRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Aggregates pending action counts for the navigation menu badges.
 * Moderation counts are shared across all moderators; personal counts are
 * scoped to a single player (organizer / jury roles).
 */
class MenuCounterService
{
    /**
     * Safety-net TTL for shared moderation counts (invalidated on every relevant change).
     */
    private const int MODERATION_TTL = 600;

    /**
     * Short safety-net TTL for personal counts.
     */
    private const int PERSONAL_TTL = 300;

    public function __construct(
        private PlayerClaimRepository $playerClaimRepository,
        private VenueRepository $venueRepository,
        private MenuActionCountsProviderInterface $provider,
        private TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getModerationCounts(): ModerationCountsDTO
    {
        return $this->cache->get('menu_counts_moderation', function (ItemInterface $item) {
            $item->tag([CacheTag::ModerationCounts->value]);
            $item->expiresAfter(self::MODERATION_TTL);

            return new ModerationCountsDTO(
                playerClaims: $this->playerClaimRepository->countPending(),
                tournaments: $this->provider->countPendingTournamentModeration(),
                captainClaims: $this->provider->countPendingCaptainClaims(),
                venues: $this->venueRepository->countPendingApproval(),
            );
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getMyActionCounts(Player $player): MyActionCountsDTO
    {
        $playerId = (int) $player->getId();

        return $this->cache->get(
            'menu_counts_my_' . $playerId,
            function (ItemInterface $item) use ($player, $playerId): MyActionCountsDTO {
                $tags = [CacheTag::menuPlayer($playerId)];
                foreach ($this->provider->getInvolvedTournamentIds($player) as $tournamentId) {
                    $tags[] = CacheTag::menuTournament($tournamentId);
                }

                $item->tag($tags);
                $item->expiresAfter(self::PERSONAL_TTL);

                return new MyActionCountsDTO(
                    organizerClaims: $this->provider->countPendingOrganizerClaims($player),
                    disputes: $this->provider->countSubmittedDisputesForJury($player),
                    appeals: $this->provider->countPendingAppealsForJury($player),
                );
            },
        );
    }
}
