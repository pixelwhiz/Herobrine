<?php

namespace pixelwhiz\herobrine\utils;

use pixelwhiz\herobrine\entity\Entity;

class HealthUtils {

    public function getHealth(Entity $entity) : int {
        return $entity->getHealth();
    }

    public function addHealth(Entity $entity, int $health) : void {

    }

    public function setHealth(Entity $entity, int $health) : void {

    }

}