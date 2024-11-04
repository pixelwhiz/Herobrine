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

use pixelwhiz\herobrine\utils\Sound;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\ExplodeSound;

trait EntityAbilitiesTrait {

    private float $lastShootTime = 0;

    public function sendLightning(): void {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $block = $world->getBlock($pos);
        $packet = new AddActorPacket();
        $packet->actorUniqueId = HerobrineEntity::nextRuntimeId();
        $packet->actorRuntimeId = 1;
        $packet->position = $this->getPosition()->asVector3();
        $packet->type = EntityIds::LIGHTNING_BOLT;
        $packet->yaw = $this->getLocation()->getYaw();
        $packet->syncedProperties = new PropertySyncData([], []);
        $sound = PlaySoundPacket::create("ambient.weather.thunder", $pos->getX(), $pos->getY(), $pos->getZ(), 100, 1);
        $world->addSound($pos, new ExplodeSound(), $world->getPlayers());
        NetworkBroadcastUtils::broadcastPackets($this->getWorld()->getPlayers(), [$packet, $sound]);
        $world->addParticle($this->getPosition()->floor(), new BlockBreakParticle($block));

    }

    public function handleWeather() : void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if ($this->getWorld()->getFolderName() === $player->getWorld()->getFolderName()) {
                Weather::thunder($this->getWorld());
            } else {
                Weather::clear($player->getWorld());
                $this->bar->removePlayer($player);
            }
        }
    }

    public function handleBossBar() : void {
        foreach ($this->getWorld()->getPlayers() as $player) {

            if ($this->getLocation()->distance($player->getLocation()->asVector3()) < 35) {
                $this->bar->addPlayer($player);
            } else {
                $this->bar->removePlayer($player);
            }
        }
    }

    public function look(): void {
        $nearestEntity = $this->getNearestEntity(35)['entity'];
        if ($nearestEntity !== null) {
            if ($nearestEntity instanceof Entity || $nearestEntity instanceof Player) {
                $this->lookAt($nearestEntity->getLocation());
            }
        }
    }

    public function normalAttack() : void {
        $nearestEntity = $this->getNearestEntity(5)['entity'];
        $closestDistance = $this->getNearestEntity(5)['distance'];

        if ($nearestEntity !== null) {
            $direction = $nearestEntity->getLocation()->subtract($this->getLocation()->x, $this->getLocation()->y, $this->getLocation()->z)->normalize()->multiply(0.3);
            $this->move($direction->getX(), $direction->getY(), $direction->getZ());

            if($closestDistance <= 2.5){
                $damageEvent = new EntityDamageEvent($nearestEntity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 5);
                $nearestEntity->attack($damageEvent);
                if ($nearestEntity instanceof Player) $nearestEntity->knockBack(mt_rand(-1, 1), mt_rand(-1, 1));
            }
        }
    }

    public function shoot() : void {
        $nearestEntity = $this->getNearestEntity(35)["entity"];
        if ($nearestEntity instanceof Entity or $nearestEntity instanceof Player) {
            $pos = $this->getLocation()->add(0, 1, 0);
            $world = $this->getWorld();

            $currentTime = microtime(true);
            if ($currentTime - $this->lastShootTime < mt_rand(0, 5)) {
                return;
            }

            $this->lastShootTime = $currentTime;

            $skull = new SkullEntity(Location::fromObject($pos->add(0, 1, 0), $world), $this);
            Sound::playSound($skull, Sound::MOB_WITHER_SHOOT);
            $skull->setMotion($this->getDirectionVector()->normalize()->multiply(5));
            $skull->spawnToAll();
        }
    }

    public function doRandomTeleport() : void {
        $chanceToDo = mt_rand(0, 100);
        $nearestEntity = $this->getNearestEntity(35)["entity"];

        if ($chanceToDo <= 5 and $nearestEntity instanceof Entity || $nearestEntity instanceof Player) {
            $world = $nearestEntity->getWorld();
            $isAllAir = true;
            for ($i = 1; $i <= 2; $i++) {
                $blockBelow = $world->getBlock($nearestEntity->getLocation()->subtract(0, $i, 0));

                if ($blockBelow !== VanillaBlocks::AIR()) {
                    $isAllAir = false;
                    break;
                }

            }

            if (!$isAllAir) {
                $chanceToTeleport = mt_rand(0, 100);

                if ($chanceToTeleport <= 100 and $chanceToTeleport > 75) {
                    $chanceVector = mt_rand(1, 4);
                    switch ($chanceVector) {
                        case 1:
                            $this->teleport($nearestEntity->getLocation()->subtract(mt_rand(5, 15), 0, mt_rand(5, 15))->floor());
                            break;
                        case 2:
                            $this->teleport($nearestEntity->getLocation()->add(mt_rand(5, 15), 0, mt_rand(5, 15))->floor());
                            break;
                        case 3:
                            $this->teleport($nearestEntity->getLocation()->subtract(mt_rand(5, 15), 0, 0)->add(0, 0, mt_rand(5, 15))->floor());
                            break;
                        case 4:
                            $this->teleport($nearestEntity->getLocation()->add(mt_rand(5, 15), 0, 0)->subtract(0, 0, mt_rand(5, 15))->floor());
                            break;
                    }
                }

                if ($chanceToTeleport <= 25 and $chanceToTeleport > 20) {
                    $this->setHealth($this->getHealth() + mt_rand(0, 20));
                    $this->sendLightning($this);
                }

                if ($chanceToTeleport <= 5 and $chanceToTeleport >= 0) {
                    $this->teleport($nearestEntity->getLocation());
                }
            }

        }
    }


    public function doNormalBehavior(): void {
        $chancesToMove = mt_rand(0, 100);
        if ($chancesToMove <= 100 and $chancesToMove > 50) {
            $this->doRandomTeleport();
        }

        if ($chancesToMove === 50) {
            $this->lookAt();
        }
    }

}