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

declare(strict_types=1);

namespace pixelwhiz\herobrine;

use pixelwhiz\herobrine\commands\HerobrineCommands;
use pixelwhiz\herobrine\entity\HerobrineEntity;
use pixelwhiz\herobrine\entity\HerobrineHead;
use pixelwhiz\herobrine\entity\SkullEntity;
use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\sessions\EntitySession;
use pixelwhiz\herobrine\sessions\EntitySessionHandler;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\World;

class Herobrine extends PluginBase{

    use EntityManager;
    public static self $instance;

    public static function getInstance() : self {
        return self::$instance;
    }

    public function onEnable(): void
    {
        self::$instance = $this;
        parent::onEnable();
        $this->registerEntities();
        $this->registerSessions();
        $this->copySkinToDataFolder();

        $this->getServer()->getCommandMap()->register("herobrine", new HerobrineCommands($this));
    }

    private function registerEntities() : void {
        EntityFactory::getInstance()->register(HerobrineHead::class, function (World $world, CompoundTag $nbt): HerobrineHead {
            return new HerobrineHead(EntityDataHelper::parseLocation($nbt, $world), $this->getSkin(), $nbt);
        }, ["HerobrineHead"]);

        EntityFactory::getInstance()->register(HerobrineEntity::class, function (World $world, CompoundTag $nbt): HerobrineEntity {
            return new HerobrineEntity(EntityDataHelper::parseLocation($nbt, $world), $this->getSkin(), $nbt);
        }, ["Herobrine"]);

        EntityFactory::getInstance()->register(SkullEntity::class, function (World $world, CompoundTag $nbt): SkullEntity {
            return new SkullEntity(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ["SkullEntity"]);
    }

    private function registerSessions() : void {
        $this->getServer()->getPluginManager()->registerEvents(new EntitySessionHandler($this), $this);
    }

    private function copySkinToDataFolder(): void
    {
        $sourcePath = $this->getFile() . "resources/skins/herobrine.png";
        $destinationPath = $this->getDataFolder() . "skins/herobrine.png";

        if (!file_exists($destinationPath)) {
            if (!is_dir($this->getDataFolder() . "skins/")) {
                mkdir($this->getDataFolder() . "skins/", 0777, true);
            }

            if (copy($sourcePath, $destinationPath)) {
                $this->getLogger()->info("Skin file copied to plugin_data.");
            } else {
                $this->getLogger()->error("Failed to copy skin file to plugin_data.");
            }
        }
    }



}
