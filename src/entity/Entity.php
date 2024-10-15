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

use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\sessions\EntitySession;
use pixelwhiz\herobrine\libs\apibossbar\BossBar;
use pixelwhiz\herobrine\libs\apibossbar\DiverseBossBar;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;

class Entity extends Human {

    use EntityManager;
    use EntitySession;

    public BossBar $bar;

    public static int $phase = 0;

    public function getPhase() : int {
        return self::$phase;
    }

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $skin, $nbt);
        $this->bar = new BossBar();
    }

    public function setPhase(int $currentPhase): void {
        if ($currentPhase < 0) {
            throw new \InvalidArgumentException("Phase cannot be negative");
        }
        self::$phase = $currentPhase;
    }

    public function getNameTag(): string
    {
        return "Herobrine";
    }

    public function getMaxHealth(): int
    {
        return 100;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $nearestEntity = null;
        $closestDistance = PHP_FLOAT_MAX;

        switch ($this->getPhase()) {
            case $this->PHASE_START():
                $this->bar->setPercentage($this->bar->getPercentage() + 1 / $this->getMaxHealth());
                foreach ($this->getWorld()->getPlayers() as $player) {
                    if ($this->getLocation()->distance($player->getLocation()->asVector3()) < 15) {
                        $this->bar->addPlayer($player);
                    } else {
                        $this->bar->removePlayer($player);
                    }
                }
                break;
            case $this->PHASE_GAME():
                $this->bar->setPercentage($this->getHealth() / $this->getMaxHealth());

                foreach ($this->getWorld()->getPlayers() as $player) {

                    if ($this->getLocation()->distance($player->getLocation()->asVector3()) < 15) {
                        $this->bar->addPlayer($player);
                    } else {
                        $this->bar->removePlayer($player);
                    }
                }

                foreach($this->getWorld()->getEntities() as $entity){
                    $distance = $this->location->distance($entity->getLocation());

                    if($distance < $closestDistance && $distance <= 15){
                        if (!$entity instanceof Entity) {
                            $nearestEntity = $entity;
                            $closestDistance = $distance;
                        }
                    }
                }

                if($nearestEntity !== null){
                    $direction = $nearestEntity->getLocation()->subtract($this->getLocation()->x, $this->getLocation()->y, $this->getLocation()->z)->normalize()->multiply(0.3);
                    $this->lookAt($nearestEntity->getLocation());
                    $this->move($direction->getX(), $direction->getY(), $direction->getZ());

                    if($closestDistance <= 3){
                        $damageEvent = new EntityDamageEvent($nearestEntity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 5);
                        $nearestEntity->attack($damageEvent);
                        if ($nearestEntity instanceof Player) $nearestEntity->knockBack(1, 1);
                    }
                }
                break;
        }

        return parent::entityBaseTick($tickDiff);
    }


    public function sendData(?array $targets, ?array $data = null): void
    {
        $targets = $targets ?? $this->hasSpawned;
        $data = $data ?? $this->getAllNetworkData();
        if(!isset($data[EntityMetadataProperties::NAMETAG])){
            parent::sendData($targets, $data);
            return;
        }
        foreach($targets as $p){
            $data[EntityMetadataProperties::NAMETAG] = new StringMetadataProperty($this->getNameTag());
            $p->getNetworkSession()->getEntityEventBroadcaster()->syncActorData([$p->getNetworkSession()], $this, $data);
        }


        $this->bar->setTitle("Herobrine");
        $this->bar->setPercentage(0);
        $this->getInventory()->setItemInHand($this->getMainWeapon());
    }

    public function attack(EntityDamageEvent $source): void
    {
        if ($source->getCause() === EntityDamageEvent::CAUSE_FIRE ||
            $source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK ||
            $source->getCause() === EntityDamageEvent::CAUSE_LAVA) {
            $source->cancel();
            return;
        }

        if ($this->getPhase() === $this->PHASE_START()) {
            $source->cancel();
        }

        parent::attack($source);

        if ($this->getHealth() <= 0) {

            foreach ($this->getWorld()->getPlayers() as $player) {
                $this->bar->removePlayer($player);
            }

            Weather::clear($this->getWorld());
            Weather::resetTime($this->getWorld());
        }
    }

}