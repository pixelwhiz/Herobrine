<?php

namespace pixelwhiz\herobrine\entity;

use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\sessions\EntitySession;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class EntityHead extends Human {

    use EntityManager;
    use EntitySession;

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.3, 0.3);
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