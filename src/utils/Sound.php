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

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

/**
 * Sound effect management utility for Herobrine plugin
 *
 * This class provides:
 * - Common sound effect constants
 * - Sound playback functionality
 * - Network packet handling for sound effects
 */
class Sound
{
    /**
     * Sound constant: Wither spawn sound effect
     * @var string
     */
    public const MOB_WITHER_SPAWN = "mob.wither.spawn";

    /**
     * Sound constant: Wither shoot sound effect
     * @var string
     */
    public const MOB_WITHER_SHOOT = "mob.wither.shoot";

    /**
     * Sound constant: Wither death sound effect
     * @var string
     */
    public const MOB_WITHER_DEATH = "mob.wither.death";

    /**
     * Sound constant: Wither ambient sound effect
     * @var string
     */
    public const MOB_WITHER_AMBIENT = "mob.wither.ambient";

    /**
     * Plays a sound effect at an entity's location for all nearby players
     *
     * Creates and sends a PlaySoundPacket to all players in the same world
     * as the target entity. Uses default volume and pitch settings.
     *
     * @param Entity $entity The entity at whose location to play the sound
     * @param string $soundName The sound effect to play (use class constants)
     * @return void
     */
    public static function playSound(Entity $entity, string $soundName): void
    {
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