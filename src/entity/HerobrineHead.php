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

namespace pixelwhiz\herobrine\entity;

use pixelwhiz\herobrine\sessions\EntitySession;
use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\particle\BlockBreakParticle;

/**
 * Floating head entity that transforms into Herobrine
 *
 * Features:
 * - Countdown timer before transformation
 * - Weather effects during spawn sequence
 * - Special block effects on transformation
 * - Damage immunity (except suffocation)
 * - Automatic facing toward nearest player
 */
class HerobrineHead extends Human
{
    use EntityManager;
    use EntitySession;

    /** @var int Countdown ticks until transformation (5 seconds at 20tps) */
    public int $spawnTime = 20 * 5;

    /**
     * Gets the initial size information
     *
     * @return EntitySizeInfo Compact size (0.3x0.3 blocks)
     */
    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.3, 0.3);
    }

    /**
     * Handles entity base tick logic
     *
     * Manages:
     * - Spawn countdown timer
     * - Weather effects
     * - Transformation sequence
     * - Herobrine entity spawning
     *
     * @param int $tickDiff Ticks since last update
     * @return bool Parent tick result
     */
    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->spawnTime--;

        // Trigger thunder at 3 seconds remaining
        if ($this->spawnTime === 20 * 3) {
            Weather::thunder($this->getWorld());
        }

        // Transformation sequence
        if ($this->spawnTime === 0) {
            $world = $this->getWorld();
            $pos = $this->getPosition();

            // Create soul fire transformation effect
            $block = $world->getBlock($pos);
            $world->setBlock($pos, VanillaBlocks::SOUL_SOIL());
            $world->setBlock($pos->add(0, 1, 0), VanillaBlocks::SOUL_FIRE());
            $world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));

            // Spawn main Herobrine entity
            $entity = new HerobrineEntity(
                Location::fromObject($pos->add(0, 2, 0), $pos->getWorld()),
                $this->getSkin(),
                $this->createBaseNBT()
            );
            $entity->setPhase($this->PHASE_START());

            // Face toward nearest player
            $nearestPlayer = null;
            foreach ($entity->getWorld()->getPlayers() as $player) {
                $distance = $entity->getPosition()->distance($player->getPosition()->asVector3());
                if ($distance < PHP_FLOAT_MAX) {
                    $nearestPlayer = $player;
                }
            }

            $yaw = $nearestPlayer !== null ? $nearestPlayer->getLocation()->getYaw() - 180 : 0;
            $entity->setRotation($yaw, 0);

            // Store spawn position
            $entity->spawnPosition = [
                "x" => $entity->getLocation()->getX(),
                "y" => $entity->getLocation()->getY() - 1,
                "z" => $entity->getLocation()->getZ()
            ];
            $entity->spawnToAll();
            $entity->sendLightning();
        }

        return parent::entityBaseTick($tickDiff);
    }

    /**
     * Initializes entity with custom skin
     *
     * @param CompoundTag $nbt Entity NBT data
     */
    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setSkin($this->getHeadSkin($this));
    }

    /**
     * Handles entity damage events
     *
     * - Cancels all damage sources except suffocation
     * - Despawns on suffocation damage
     *
     * @param EntityDamageEvent $source Damage event
     */
    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() === $source::CAUSE_SUFFOCATION) {
            $this->flagForDespawn();
        }

        $source->cancel();
    }
}