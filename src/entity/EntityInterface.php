<?php

namespace pixelwhiz\herobrine\entity;

use pocketmine\entity\Entity;
use pocketmine\player\Player;

interface EntityInterface {


    public function walking(): void;
    public function running(): void;
    public function attackPlayer(): void;
    public function lookingEntity(Entity $entity): void;
}