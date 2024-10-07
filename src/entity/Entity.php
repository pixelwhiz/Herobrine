<?php

namespace pixelwhiz\herobrine\entity;

use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class Entity extends Human {

    use EntityManager;

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
            $this->lookAt($nearestPlayer->getLocation());

            $direction = $nearestPlayer->getLocation()->subtract($this->getLocation()->x, $this->getLocation()->y, $this->getLocation()->z)->normalize()->multiply(0.3);
            $this->move($direction->getX(), $direction->getY(), $direction->getZ());
            $this->jump();

            if($closestDistance <= 2.5){
                $damageEvent = new EntityDamageEvent($nearestPlayer, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 5);
                $nearestPlayer->attack($damageEvent);
                if ($nearestPlayer instanceof Player) $nearestPlayer->knockBack(1, 1);
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

        parent::attack($source);

        if ($this->getHealth() <= 0) {
            Weather::clear($this->getWorld());
            Weather::resetTime($this->getWorld());
        }
    }

}