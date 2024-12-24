<?php

/*
 *   _    _                _          _
 *  | |  | |              | |        (_)
 *  | |__| | ___ _ __ ___ | |__  _ __ _ _ __   ___
 *  |  __  |/ _ \ '__/ _ \| '_ \| '__| | '_ \ / _ \
 *  | |  | |  __/ | | (_) | |_) | |  | | | | |  __/
 *  |_|  |_|\___|_|  \___/|_.__/|_|  |_|_| |_|\___|
 *
 * Copyright (C) 2024 pixelwhiz
 *
 * This software is distributed under "GNU General Public License v3.0".
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License v3.0
 * along with this program. If not, see <https://opensource.org/licenses/GPL-3.0>.
 */

namespace pixelwhiz\herobrine\utils;

use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\world\World;

class Weather {
    
    /**
     * @TODO: Generate thunder bolt
     * @param World $world
     * @return void
     */
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

    /**
     *
     * @TODO: Change weather to clear when HerobrineEntity() was Dead
     * @param World $world
     * @return void
     */
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