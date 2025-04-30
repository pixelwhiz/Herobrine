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

use muqsit\invmenu\InvMenu;
use pixelwhiz\herobrine\entity\HerobrineEntity;
use pixelwhiz\herobrine\Herobrine;
use pixelwhiz\herobrine\sessions\EntityManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class HerobrineCommands extends Command {

    use EntityManager;

    private Herobrine $plugin;

    public function __construct(Herobrine $plugin)
    {
        parent::__construct("herobrine", "HerobrineEntity main commands", "§7Usage: §c/hb help", ["hb"]);
        $this->setPermission("herobrine.command");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cOnly players can execute this command!");
            return false;
        }

        if (!isset($args[0])) {
            $sender->sendMessage($this->getUsage());
            return false;
        }

        switch ($args[0]) {
            case "help":
                if (!$sender->hasPermission("herobrine.command.help")) {
                    $sender->sendMessage("§cYou don't have permission to use this command!");
                    return false;
                }

                $sender->sendMessage(
                    "§aAll herobrine main commands: \n".
                    "§7- /hb help (Showing all commands)\n".
                    "§7- /hb spawn (Spawn herobrine on your location)\n".
                    "§7- /hb position (Get actual position of herobrine)\n".
                    "§7- /hb teleport (Teleport to herobrine position)\n".
                    "§7- /hb kill (Kill herobrine in your world)\n".
                    "§7- /hb rewards (Set reward after killing herobrine)\n"
                );
                break;
            case "spawn":

                if (!$sender->hasPermission("herobrine.command.spawn")) {
                    $sender->sendMessage("§cYou don't have permission to spawn herobrine!");
                    return false;
                }

                $world = $sender->getWorld();
                $pos = $sender->getPosition();

                if ($this->plugin->isEntityExists($world)) {
                    $sender->sendMessage("§eAnother herobrine already exists in this world, kill it first. Please use §c/hb position §eto track position");
                } else {
                    $entity = new HerobrineEntity(Location::fromObject($pos->add(0, 2, 0), $pos->getWorld()), $this->getSkin(), $this->createBaseNBT());
                    $entity->setPhase($entity->PHASE_GAME());
                    $entity->sendLightning();
                    $entity->spawnToAll();
                    $sender->sendMessage("§aHerobrine spawned successfully in world: ".$sender->getWorld()->getFolderName()."!");
                }
                break;
            case "position":
            case "pos":

                if (!$sender->hasPermission("herobrine.command.position")) {
                    $sender->sendMessage("§cYou don't have permission to get Herobrine's position!");
                    return false;
                }

                $world = $sender->getWorld();
                $found = false;

                foreach ($world->getEntities() as $entity) {
                    if ($entity instanceof HerobrineEntity) {
                        $sender->sendMessage("Herobrine found in world: §e" . $entity->getWorld()->getFolderName());
                        $sender->sendMessage("Position: x: " . (int)$entity->getLocation()->getX() . ", y: " . (int)$entity->getLocation()->getY() . ", z: " . (int)$entity->getLocation()->getZ());
                        $found = true;
                    }
                }

                if (!$found) {
                    $filePath = Herobrine::getInstance()->getDataFolder() . "data/position.json";

                    if (file_exists($filePath)) {
                        $positions = json_decode(file_get_contents($filePath), true);

                        if (is_array($positions)) {
                            foreach ($positions as $position) {
                                // Cek apakah pemain ada di world yang sama
                                if ($world->getFolderName() === $position['world']) {
                                    $sender->sendMessage("Herobrine was last seen in world: §e" . $position['world']);
                                    $sender->sendMessage("Position: x: " . (int)$position['x'] . ", y: " . (int)$position['y'] . ", z: " . (int)$position['z']);
                                    return true;
                                }
                            }
                        }
                    }

                    $sender->sendMessage("No Herobrine found in world: §e" . $world->getFolderName());
                }

                break;

            case "tphere":
            case "tph":

                if (!$sender->hasPermission("herobrine.command.tphere")) {
                    $sender->sendMessage("§cYou do not have permission to use this command!");
                    return false;
                }

                $found = false;
                foreach ($sender->getWorld()->getEntities() as $entity) {
                    if ($entity instanceof HerobrineEntity) {
                        $entity->teleport($sender->getLocation());
                        $sender->sendMessage("§aTeleported Herobrine to your position in world: §e" . $entity->getWorld()->getFolderName());
                        $found = true;
                    }
                }

                if (!$found) {
                    $world = $sender->getWorld();
                    $lastPos = $sender->getLocation();
                    $filePath = Herobrine::getInstance()->getDataFolder() . "data/position.json";

                    if (file_exists($filePath)) {
                        $positions = json_decode(file_get_contents($filePath), true);

                        if (is_array($positions)) {
                            foreach ($positions as $position) {
                                if ($world->getFolderName() === $position['world']) {
                                    $herobrinePos = new Vector3($position["x"], $position["y"], $position["z"]);
                                    $sender->teleport($herobrinePos);
                                    Herobrine::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($sender, $lastPos, $herobrinePos) {
                                        $sender->teleport($lastPos);
                                        foreach ($sender->getWorld()->getEntities() as $entity) {
                                            if ($entity instanceof HerobrineEntity) {
                                                $entity->teleport($sender->getLocation());
                                            }
                                        }
                                    }), 20 * 5);

                                    $sender->sendMessage("§aTeleported to Herobrine's position. You will be returned in 5 seconds.");
                                    return true;
                                }
                            }
                        }
                    }

                    $sender->sendMessage("No Herobrine found in world: §e" . $sender->getWorld()->getFolderName());
                }

                break;
            case "teleport":
            case "tp":

                if (!$sender->hasPermission("herobrine.command.teleport")) {
                    $sender->sendMessage("§cYou do not have permission to use this command!");
                    return false;
                }

                $found = false;
                foreach ($sender->getWorld()->getEntities() as $entity) {
                    if ($entity instanceof HerobrineEntity) {
                        $sender->teleport($entity->getLocation());
                        $sender->sendMessage("§aTeleported to herobrine position in world: §e". $entity->getWorld()->getFolderName());
                        $found = true;
                    }
                }

                if (!$found) {
                    $world = $sender->getWorld();
                    $filePath = Herobrine::getInstance()->getDataFolder() . "data/position.json";

                    if (file_exists($filePath)) {
                        $positions = json_decode(file_get_contents($filePath), true);

                        if (is_array($positions)) {
                            foreach ($positions as $position) {
                                if ($world->getFolderName() === $position['world']) {
                                    $sender->teleport(new Vector3($position['x'], $position['y'], $position['z']));
                                    $sender->sendMessage("§aTeleported to herobrine position in world: §e". $entity->getWorld()->getFolderName());
                                    return true;
                                }
                            }
                        }
                    }

                    $sender->sendMessage("No herobrine found in world: §e". $sender->getWorld()->getFolderName());
                    return false;
                }

                break;
            case "kill":

                if (!$sender->hasPermission("herobrine.command.kill")) {
                    $sender->sendMessage("§cYou do not have permission to use this command!");
                    return false;
                }

                $world = $sender->getWorld();

                $entityCount = 0;
                foreach ($world->getEntities() as $entity) {
                    if ($entity instanceof HerobrineEntity) {
                        for ($i = 0; $i < count($world->getEntities()); $i++) {
                            $entityCount = $i;
                        }
                        $entity->kill();
                    }
                }

                $sender->sendMessage("§aKilled (".$entityCount.") herobrine at world ".$world->getFolderName()."");

                break;

            case "rewards":
                if (!$sender->hasPermission("herobrine.command.rewards")) {
                    $sender->sendMessage("§cYou do not have permission to use this command!");
                    return false;
                }

                $this->plugin->rewardsManager->sendToPlayer($sender);

                break;
        }

        return true;
    }



}
