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

class HerobrineHead extends Human {

    use EntityManager;
    use EntitySession;

    public int $spawnTime = 20 * 5;

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.3, 0.3);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->spawnTime--;
        if ($this->spawnTime === 20 * 3) {
            Weather::thunder($this->getWorld());
        }

        if ($this->spawnTime === 0) {
            $world = $this->getWorld();
            $pos = $this->getPosition();
            $block = $world->getBlock($pos);
            $world->setBlock($pos, VanillaBlocks::SOUL_SOIL());
            $world->setBlock($pos->add(0, 1, 0), VanillaBlocks::SOUL_FIRE());
            $world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));

            $entity = new HerobrineEntity(Location::fromObject($pos->add(0, 2, 0), $pos->getWorld()), $this->getSkin(), $this->createBaseNBT());
            $entity->setPhase($this->PHASE_START());

            $nearestPlayer = null;
            foreach ($entity->getWorld()->getPlayers() as $player) {
                $distance = $entity->getPosition()->distance($player->getPosition()->asVector3());
                if ($distance < PHP_FLOAT_MAX) {
                    $nearestPlayer = $player;
                }
            }
            $yaw = $nearestPlayer !== null ? $nearestPlayer->getLocation()->getYaw() - 180 : 0;
            $entity->setRotation($yaw, 0);
            $entity->spawnToAll();

            $entity->sendLightning();
        }

        return parent::entityBaseTick($tickDiff);
    }

    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setSkin($this->getHeadSkin($this));
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() === $source::CAUSE_SUFFOCATION) {
            $this->flagForDespawn();
        }

        $source->cancel();
    }

}