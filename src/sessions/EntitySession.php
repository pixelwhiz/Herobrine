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
use pixelwhiz\herobrine\utils\BlockPattern;
use pixelwhiz\herobrine\utils\Sound;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;

trait EntitySession {

    use EntityManager;

    /**
     * @method static HerobrineEntity getPhase()
     * @return int
     */

    public function PHASE_NULL() : int { return 0; }
    public function PHASE_SPAWN() : int { return 1; }
    public function PHASE_START() : int { return 2; }
    public function PHASE_GAME() : int { return 3; }
    public function PHASE_END() : int { return 4; }

    public function spawnSession(Position $pos): bool {
        $world = $pos->getWorld();
        $block = $world->getBlock($pos);

        $entityHead = new HerobrineHead(Location::fromObject($pos->add(0.5, 0, 0.5), $world), $this->getSkin());

        $world->setTime(18000);

        $nearestPlayer = null;
        foreach ($entityHead->getWorld()->getPlayers() as $player) {
            $distance = $entityHead->getPosition()->distance($player->getPosition()->asVector3());
            if ($distance < PHP_FLOAT_MAX) {
                $nearestPlayer = $player;
            }
        }

        $yaw = $nearestPlayer !== null ? $nearestPlayer->getLocation()->getYaw() - 180 : 0;
        $entityHead->setRotation($yaw, 0);

        $world->setBlock($pos, VanillaBlocks::AIR());
        $world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
        $entityHead->spawnToAll();

        return true;
    }

    public function startSession(HerobrineEntity $entity): bool {
        $pos = $entity->getPosition();
        $world = $entity->getWorld();

        if ($entity->startTime <= 20 * 10 && $this->startTime >= 20 * 1) {
            $entity->setPhase($this->PHASE_START());
        }

        if ($this->startTime > 20 * 1 and $entity->startSession === 0) {
            Sound::playSound($entity, Sound::MOB_WITHER_SPAWN);
            $entity->startSession = 20;
        }

        if ($this->startTime === 20 * 1) {
            $world->addSound($pos, new ExplodeSound(), $world->getPlayers());

            BlockPattern::clearPattern($world, $pos);
            $entity->sendLightning($entity);

            $entity->setFireTicks(0);
            $entity->setPhase($this->PHASE_GAME());
        }

        return true;
    }

    public function gameSession(HerobrineEntity $entity): bool {
        $entity->setPhase($this->PHASE_GAME());
        $entity->isInGame = true;

        if ($entity->isAlive() and $entity->gameSession === 0) {
            $entity->shoot();
            Sound::playSound($entity, Sound::MOB_WITHER_AMBIENT);
            $entity->gameSession = 20;
        } else {
            $entity->isInGame = false;
        }
        return true;
    }

    public function endSession(): void {
    }

}