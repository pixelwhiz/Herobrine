<?php

namespace pixelwhiz\herobrine\utils;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\libs\apibossbar\DiverseBossBar;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

class BossBar {

    public const COLOR_WHITE = 0;
    public const COLOR_RED = 1;
    public const COLOR_GREEN = 2;
    public const COLOR_BLUE = 3;
    public const COLOR_YELLOW = 4;
    public const COLOR_PURPLE = 5;
    public const COLOR_PINK = 6;

    public static function handle(Entity $entity) : void {
        $bar = new DiverseBossBar();
        $bar->setTitle("Herobrine");
        $bar->setColor(self::COLOR_BLUE);
        $player = self::getNearestPlayer($entity);
        if ($player instanceof Player) {
            if (self::isNearestToPlayer($entity)) {
                $bar->addPlayer($player);
            } else {
                $bar->removePlayer($player);
            }
        }
    }

    private static function isNearestToPlayer(Entity $entity): bool {
        if ($entity->getPhase() === $entity->PHASE_START() or
            $entity->getPhase() === $entity->PHASE_GAME()) {
            $closestDistance = PHP_FLOAT_MAX;
            foreach($entity->getWorld()->getPlayers() as $player){
                $distance = $entity->getLocation()->distance($player->getLocation());
                if ($player !== null) {
                    if($distance < $closestDistance && $distance <= 15) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private static function getNearestPlayer(Entity $entity): ?Player {
        $nearestPlayer = null;
        if ($entity->getPhase() === $entity->PHASE_START() or
            $entity->getPhase() === $entity->PHASE_GAME()) {
            $closestDistance = PHP_FLOAT_MAX;
            foreach($entity->getWorld()->getPlayers() as $player){
                $distance = $entity->getLocation()->distance($player->getLocation());
                if ($player !== null) {
                    if($distance < $closestDistance && $distance <= 15) {
                        $nearestPlayer = $player;
                    }
                }
            }
        }
        return $nearestPlayer;
    }

}