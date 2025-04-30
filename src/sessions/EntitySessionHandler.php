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
use pixelwhiz\herobrine\Herobrine;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\MobHead;
use pocketmine\block\utils\MobHeadType;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\item\VanillaItems;
use pixelwhiz\herobrine\utils\BlockPattern;
use pocketmine\player\GameMode;

/**
 * Event listener class for Herobrine entity interactions
 *
 * Handles:
 * - Block break protection around Herobrine entities
 * - Player teleportation effects
 * - World unloading persistence
 * - Herobrine spawning interactions
 */
class EntitySessionHandler implements Listener
{
    use EntitySession;
    use EntityManager;

    /** @var float Timestamp of last interaction to prevent double clicks */
    private float $lastClickTime = 0;

    /**
     * Handles block break events near Herobrine entities
     *
     * Protects spawn patterns when:
     * - Near HerobrineHead entities
     * - Near HerobrineEntity in START phase
     *
     * @param BlockBreakEvent $event The block break event
     */
    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        foreach ($player->getWorld()->getEntities() as $entity) {
            if ($entity instanceof HerobrineHead) {
                BlockPattern::protectSpawnPattern($event, $entity->getWorld(), $entity->getPosition());
            }
            if ($entity instanceof HerobrineEntity && $entity->getPhase() === $entity->PHASE_START()) {
                BlockPattern::protectStartPattern($event, $entity->getWorld(), $entity->getPosition());
            }
        }
    }

    /**
     * Handles entity teleportation events
     *
     * Clears weather effects and removes boss bars when players
     * teleport away from Herobrine's world
     *
     * @param EntityTeleportEvent $event The teleport event
     */
    public function onTeleport(EntityTeleportEvent $event): void
    {
        $entity = $event->getEntity();
        $to = $event->getTo();

        $hb = Herobrine::getInstance()->getEntityByWorld($entity->getWorld());
        if ($hb instanceof HerobrineEntity) {
            if ($hb->getWorld()->getFolderName() !== $to->getWorld()->getFolderName()) {
                $hb->bar->removePlayer($entity);
                Weather::clear($entity->getWorld());
            }
        }
    }

    /**
     * Handles world unload events
     *
     * Saves positions of all Herobrine entities when their world unloads
     *
     * @param WorldUnloadEvent $event The world unload event
     */
    public function onUnload(WorldUnloadEvent $event): void
    {
        $world = $event->getWorld();
        $positions = [];

        foreach ($world->getEntities() as $entity) {
            if ($entity instanceof HerobrineEntity) {
                $positions[] = [
                    'world' => $entity->getWorld()->getFolderName(),
                    'x' => $entity->getLocation()->getX(),
                    'y' => $entity->getLocation()->getY(),
                    'z' => $entity->getLocation()->getZ()
                ];
            }
        }

        $dataFolder = Herobrine::getInstance()->getDataFolder() . "data/";
        if (!is_dir($dataFolder)) {
            mkdir($dataFolder, 0755, true);
        }

        file_put_contents(
            $dataFolder . "position.json",
            json_encode($positions, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Handles player interaction events
     *
     * Manages Herobrine spawning when players interact with:
     * - Nether Star items
     * - Player head blocks
     *
     * Includes click cooldown protection
     *
     * @param PlayerInteractEvent $event The interaction event
     */
    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $action = $event->getAction();
        $block = $event->getBlock();
        $item = $event->getItem();

        if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK ||
            $action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {

            if ($item->getTypeId() === VanillaItems::NETHER_STAR()->getTypeId() &&
                $block instanceof MobHead &&
                $block->getMobHeadType() === MobHeadType::PLAYER) {

                $currentTime = microtime(true);
                if ($currentTime - $this->lastClickTime < 3) {
                    return;
                }

                $this->lastClickTime = $currentTime;

                if (!Herobrine::getInstance()->isEntityExists($player->getWorld())) {
                    if (BlockPattern::trySpawnFromPattern($player, $block)) {
                        $this->spawnSession($block->getPosition());
                        if ($player->getGamemode() !== GameMode::CREATIVE()) {
                            $item->setCount($item->getCount() - 1);
                            $player->getInventory()->setItemInHand($item);
                        }
                    }
                } else {
                    $player->sendMessage(
                        "§eAnother herobrine already exists in this world, kill it first. " .
                        "Please use §c/hb position §eto track position"
                    );
                }
            }
        }
    }
}