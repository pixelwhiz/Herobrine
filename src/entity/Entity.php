<?php

namespace pixelwhiz\herobrine\entity;

use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;

class Entity extends Human {

    use EntityManager;

    public function getNameTag(): string
    {
        return "Herobrine";
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


    protected function onDeath(): void
    {
        Weather::clear($this->getWorld());
        Weather::resetTime($this->getWorld());
    }

}