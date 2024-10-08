<?php

namespace pixelwhiz\herobrine\utils;

use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\Server;
use pocketmine\world\World;

class Weather {

    public static array $time = [];

    public static function saveTime(World $world) : bool {
        if (self::$time[$world->getFolderName()] = $world->getTime()) return true;
        return false;
    }

    public static function resetTime(World $world) {
        $time = self::$time[$world->getFolderName()] ?? 0;
        $world->setTime($time);
    }

    public static function thunder(World $world){
        $worldData = $world->getProvider()->getWorldData();
        $worldData->setLightningLevel(1);
        $packets = [LevelEventPacket::create(LevelEvent::START_THUNDER, 65535, null)];

        foreach ($world->getPlayers() as $player) {
            foreach ($packets as $pk) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }

    public static function clear(World $world) {
        $worldData = $world->getProvider()->getWorldData();
        $worldData->setRainTime(0);
        $worldData->setRainLevel(0);
        $worldData->setLightningLevel(0);

        $packets = [
            LevelEventPacket::create(LevelEvent::STOP_RAIN, 0, null),
            LevelEventPacket::create(LevelEvent::STOP_THUNDER, 0, null)
        ];

        foreach ($world->getPlayers() as $player) {
            foreach($packets as $packet){
                $player->getNetworkSession()->sendDataPacket($packet);
            }
        }
    }

}