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
use pixelwhiz\herobrine\sessions\EntitySessionHandler;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\World;

/**
 * Main plugin class handling Herobrine entity management
 *
 * @property self $instance Singleton plugin instance
 * @property RewardsManager $rewardsManager Manages player reward system
 * @property Config $data Plugin configuration storage
 */
class Herobrine extends PluginBase
{
    use EntityManager;

    /** @var self Singleton plugin instance */
    public static self $instance;

    /** @var RewardsManager Handles the reward inventory system */
    public RewardsManager $rewardsManager;

    /** @var Config Plugin configuration storage */
    public Config $data;

    /**
     * Get the active plugin instance (Singleton pattern)
     *
     * @return self The current plugin instance
     */
    public static function getInstance(): self
    {
        return self::$instance;
    }

    /**
     * Plugin activation handler
     *
     * Initializes all plugin components including:
     * - Entity registration
     * - Event listeners
     * - Reward system
     * - Configuration
     * - Command registration
     */
    public function onEnable(): void
    {
        self::$instance = $this;
        parent::onEnable();

        $this->registerEntities();
        $this->registerSessions();
        $this->copySkinToDataFolder();

        $this->rewardsManager = new RewardsManager($this);
        $this->rewardsManager->initialize();

        $this->saveResource($this->getDataFolder() . "config.yml");
        $this->getServer()->getCommandMap()->register("herobrine", new HerobrineCommands($this));
    }

    /**
     * Plugin deactivation handler
     *
     * Saves all Herobrine entity positions to JSON storage
     * for persistence across server restarts
     */
    protected function onDisable(): void
    {
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            $positions = [];

            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof HerobrineEntity) {
                    $positions[] = [
                        'world' => $entity->getWorld()->getFolderName(),
                        'x' => $entity->getLocation()->getX(),
                        'y' => $entity->getLocation()->getY(),
                        'z' => $entity->getLocation()->getZ()
                    ];
                }
            }

            $dataFolder = $this->getDataFolder() . "data/";
            if (!is_dir($dataFolder)) {
                mkdir($dataFolder, 0755, true);
            }

            file_put_contents(
                $dataFolder . "position.json",
                json_encode($positions, JSON_PRETTY_PRINT)
            );
        }

        parent::onDisable();
    }

    /**
     * Registers all custom entities used by the plugin
     *
     * Registers three entity types:
     * 1. HerobrineHead - The floating head variant
     * 2. HerobrineEntity - The full entity
     * 3. SkullEntity - Decorative skull entity
     */
    private function registerEntities(): void
    {
        EntityFactory::getInstance()->register(
            HerobrineHead::class,
            function (World $world, CompoundTag $nbt): HerobrineHead {
                return new HerobrineHead(
                    EntityDataHelper::parseLocation($nbt, $world),
                    $this->getSkin(),
                    $nbt
                );
            },
            ["HerobrineHead"]
        );

        EntityFactory::getInstance()->register(
            HerobrineEntity::class,
            function (World $world, CompoundTag $nbt): HerobrineEntity {
                return new HerobrineEntity(
                    EntityDataHelper::parseLocation($nbt, $world),
                    $this->getSkin(),
                    $nbt
                );
            },
            ["Herobrine"]
        );

        EntityFactory::getInstance()->register(
            SkullEntity::class,
            function (World $world, CompoundTag $nbt): SkullEntity {
                return new SkullEntity(
                    EntityDataHelper::parseLocation($nbt, $world),
                    null,
                    $nbt
                );
            },
            ["SkullEntity"]
        );
    }

    /**
     * Registers event listeners for entity management
     *
     * Uses EntitySessionHandler to manage entity interactions
     * and behaviors
     */
    private function registerSessions(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(
            new EntitySessionHandler($this),
            $this
        );
    }

    /**
     * Copies the default Herobrine skin to plugin data folder
     *
     * Ensures the skin texture is available for entity rendering.
     * Creates necessary directories if they don't exist.
     * Logs success/failure of the operation.
     */
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

    /**
     * Locates a Herobrine entity in specified world
     *
     * @param World $world The world to search in
     * @return HerobrineEntity|null The found entity or null
     */
    public function getEntityByWorld(World $world): ?HerobrineEntity
    {
        foreach ($world->getEntities() as $entities) {
            if ($entities instanceof HerobrineEntity) {
                return $entities;
            }
        }
        return null;
    }

    /**
     * Checks if any Herobrine-related entity exists in a world
     *
     * @param World $world The world to check
     * @return bool True if any Herobrine entity or head is found
     */
    public function isEntityExists(World $world): bool
    {
        foreach ($world->getEntities() as $entities) {
            if ($entities instanceof HerobrineEntity || $entities instanceof HerobrineHead) {
                return true;
            }
        }
        return false;
    }
}