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

namespace pixelwhiz\herobrine\commands;

use pixelwhiz\herobrine\entity\HerobrineEntity;
use pixelwhiz\herobrine\Herobrine;
use pixelwhiz\herobrine\sessions\EntityManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Skin;
use pocketmine\player\Player;

class HerobrineCommands extends Command {

    public function __construct(Herobrine $plugin)
    {
        parent::__construct("herobrine", "HerobrineEntity main commands", "Usage: /herobrine help", []);
        $this->setPermission("herobrine.cmd");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be executed in-game.");
            return false;
        }

        // Call the static method from the trait
        $skin = self::getSkin();
        $entity = new HerobrineEntity($sender->getLocation(), $skin);
        $entity->spawnToAll();
        $sender->sendMessage("Herobrine entity created!");
        return true;
    }



}
