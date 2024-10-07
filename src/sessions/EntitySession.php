<?php

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\entity\EntityHead;
use pixelwhiz\herobrine\Herobrine;
use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\sessions\EntitySessionScheduler;
use pocketmine\entity\Location;
use pocketmine\world\Position;

trait EntitySession {

    use EntityManager;

    public function PHASE_NULL() : int { return 0; }
    public function PHASE_SPAWN() : int { return 1; }
    public function PHASE_START() : int { return 2; }
    public function PHASE_END() : int { return 3; }

    public function spawnSession(Position $pos): void {
        Herobrine::getInstance()->getScheduler()->scheduleRepeatingTask(new EntitySessionScheduler($this->PHASE_SPAWN(), $pos), 20);
    }

    public function startSession(Entity $entity): void {
        Herobrine::getInstance()->getScheduler()->scheduleRepeatingTask(new EntitySessionScheduler($this->PHASE_START(), $entity->getPosition(), $entity), 20);
    }

    public function endSession(): void {
    }

}