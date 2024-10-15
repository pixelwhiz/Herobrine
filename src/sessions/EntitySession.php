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

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\Herobrine;
use pocketmine\world\Position;

trait EntitySession {

    use EntityManager;

    public function PHASE_NULL() : int { return 0; }
    public function PHASE_SPAWN() : int { return 1; }
    public function PHASE_START() : int { return 2; }
    public function PHASE_GAME() : int { return 3; }
    public function PHASE_END() : int { return 4; }

    public function spawnSession(Position $pos): void {
        Herobrine::getInstance()->getScheduler()->scheduleRepeatingTask(new EntitySessionScheduler($this->PHASE_SPAWN(), $pos), 20);
    }

    public function startSession(Entity $entity): void {
        Herobrine::getInstance()->getScheduler()->scheduleRepeatingTask(new EntitySessionScheduler($this->PHASE_START(), $entity->getPosition(), $entity), 20);
    }

    public function gameSession(Entity $entity): void {
        Herobrine::getInstance()->getScheduler()->scheduleRepeatingTask(new EntitySessionScheduler($this->PHASE_GAME(), $entity->getPosition(), $entity), 20);
    }

    public function endSession(): void {
    }

}