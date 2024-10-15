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

namespace pixelwhiz\herobrine\entity;

use pixelwhiz\herobrine\libs\apibossbar\BossBar;
use pixelwhiz\herobrine\sessions\EntitySession;
use pixelwhiz\herobrine\sessions\EntityManager;
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