<?php

declare(strict_types=1);

namespace pixelwhiz\herobrine;

use pixelwhiz\herobrine\commands\HerobrineCommands;
use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\entity\EntityHead;
use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\sessions\EntitySessionHandler;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
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
        if (!Server::getInstance()->getWorldManager()->isWorldLoaded("flat")) {
            $this->getServer()->getWorldManager()->loadWorld("flat");
        }
        foreach (Server::getInstance()->getWorldManager()->getWorldByName("flat")->getEntities() as $entity) {
            $entity->kill();
        }
        $this->getServer()->getCommandMap()->register("herobrine", new HerobrineCommands($this));
    }

    private function registerEntities() : void {
        EntityFactory::getInstance()->register(EntityHead::class, function (World $world, CompoundTag $nbt): EntityHead {
            return new EntityHead(EntityDataHelper::parseLocation($nbt, $world), $this->getSkin(), $nbt);
        }, ["HerobrineHead"]);

        EntityFactory::getInstance()->register(Entity::class, function (World $world, CompoundTag $nbt): Entity {
            return new Entity(EntityDataHelper::parseLocation($nbt, $world), $this->getSkin(), $nbt);
        }, ["Herobrine"]);
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
