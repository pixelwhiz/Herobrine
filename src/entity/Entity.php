<?php

namespace pixelwhiz\herobrine\entity;

use pixelwhiz\herobrine\entity\sessions\EntityManager;
use pixelwhiz\herobrine\entity\sessions\EntitySession;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\entity\Human;
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

    public static int $phase = 0;

    public function getPhase() : int {
        return self::$phase;
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
        return 20;
    }

    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $nearestPlayer = null;
        $closestDistance = PHP_FLOAT_MAX;

        if ($this->getPhase() === $this->PHASE_GAME()) {
            foreach($this->getWorld()->getEntities() as $entity){
                $distance = $this->location->distance($entity->getLocation());

                if($distance < $closestDistance && $distance <= 15){
                    if (!$entity instanceof Entity) {
                        $nearestPlayer = $entity;
                        $closestDistance = $distance;
                    }
                }
            }

            if($nearestPlayer !== null){
                $direction = $nearestPlayer->getLocation()->subtract($this->getLocation()->x, $this->getLocation()->y, $this->getLocation()->z)->normalize()->multiply(0.3);
                $this->lookAt($nearestPlayer->getLocation());
                $this->move($direction->getX(), $direction->getY(), $direction->getZ());

                if($closestDistance <= 3){
                    $damageEvent = new EntityDamageEvent($nearestPlayer, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 5);
                    $nearestPlayer->attack($damageEvent);
                    if ($nearestPlayer instanceof Player) $nearestPlayer->knockBack(1, 1);
                }
            }
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
            Weather::clear($this->getWorld());
            Weather::resetTime($this->getWorld());
        }
    }

}