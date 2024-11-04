<?php


namespace pixelwhiz\herobrine\utils;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class Sound {

    public const MOB_WITHER_SPAWN = "mob.wither.spawn";
    public const MOB_WITHER_SHOOT = "mob.wither.shoot";
    public const MOB_WITHER_DEATH = "mob.wither.death";
    public const MOB_WITHER_AMBIENT = "mob.wither.ambient";


    public static function playSound(Entity $entity, string $soundName): void {
        $packet = new PlaySoundPacket();
        $packet->soundName = $soundName;
        $packet->x = $entity->getPosition()->getX();
        $packet->y = $entity->getPosition()->getY();
        $packet->z = $entity->getPosition()->getZ();
        $packet->volume = 1.0;
        $packet->pitch = 1.0;

        foreach ($entity->getWorld()->getPlayers() as $player) {
            $player->getNetworkSession()->sendDataPacket($packet);
        }
    }

}