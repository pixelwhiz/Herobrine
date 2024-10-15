<?php

namespace pixelwhiz\herobrine\commands;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\Herobrine;
use pixelwhiz\herobrine\sessions\EntityManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Skin;
use pocketmine\player\Player;

class HerobrineCommands extends Command {

    public function __construct(Herobrine $plugin)
    {
        parent::__construct("herobrine", "Entity main commands", "Usage: /herobrine help", []);
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
        $entity = new Entity($sender->getLocation(), $skin);
        $entity->spawnToAll();
        $sender->sendMessage("Herobrine entity created!");
        return true;
    }



}
