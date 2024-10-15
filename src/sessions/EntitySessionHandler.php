<?php

/*
 *   _    _                _          _
 *  | |  | |              | |        (_)
 *  | |__| | ___ _ __ ___ | |__  _ __ _ _ __   ___
 *  |  __  |/ _ \ '__/ _ \| '_ \| '__| | '_ \ / _ \
 *  | |  | |  __/ | | (_) | |_) | |  | | | | |  __/
 *  |_|  |_|\___|_|  \___/|_.__/|_|  |_|_| |_|\___|
 *
 * Copyright (C) 2024 pixelwhiz
 *
 * This software is distributed under "GNU General Public License v3.0".
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see <https://opensource.org/licenses/GPL-3.0>.
 */

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\entity\HerobrineEntity;
use pixelwhiz\herobrine\entity\HerobrineHead;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\Block;
use pocketmine\block\MobHead;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pixelwhiz\herobrine\utils\BlockPattern;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;

class EntitySessionHandler implements Listener {

    use EntitySession;
    use EntityManager;

    private float $lastClickTime = 0;

    public function onBreak(BlockBreakEvent $event) {
        $player = $event->getPlayer();
        foreach ($player->getWorld()->getEntities() as $entity) {
            if ($entity instanceof HerobrineHead) {

                // TODO: Cancel BlockBreakEvent() when PHASE_SPAWN()
                BlockPattern::protectSpawnPattern($event, $entity->getWorld(), $entity->getPosition());
            }
            if ($entity instanceof HerobrineEntity and $entity->getPhase() === $entity->PHASE_START()) {

                // TODO: Cancel BlockBreakEvent() when PHASE_START()
                BlockPattern::protectStartPattern($event, $entity->getWorld(), $entity->getPosition());
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $action = $event->getAction();
        $block = $event->getBlock();
        $item = $event->getItem();

        if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if ($item->getTypeId() === VanillaItems::NETHER_STAR()->getTypeId() && $block instanceof MobHead && $block->getMobHeadType() === MobHeadType::PLAYER) {
                /**
                 * @TODO: Avoid the appearance of double click HerobrineEntity()
                 * @var $currentTime bool
                 */
                $currentTime = microtime(true);
                if ($currentTime - $this->lastClickTime < 3) {
                    return;
                }

                $this->lastClickTime = $currentTime;

                if (BlockPattern::trySpawnFromPattern($player, $block)) {
                    $this->spawnSession($block->getPosition());
                    Weather::saveTime($player->getWorld());
                    if ($player->getGamemode() !== GameMode::CREATIVE()) {
                        $item->setCount($item->getCount() - 1);
                        $player->getInventory()->setItemInHand($item);
                    }
                }
            }
        }
    }
}