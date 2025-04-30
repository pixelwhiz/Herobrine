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

use pixelwhiz\herobrine\Herobrine;
use pixelwhiz\herobrine\sessions\EntityManager;
use pixelwhiz\herobrine\sessions\EntitySession;
use pixelwhiz\herobrine\libs\apibossbar\BossBar;
use pixelwhiz\herobrine\utils\Sound;
use pixelwhiz\herobrine\utils\Weather;
use pixelwhiz\resinapi\ResinAPI;
use pocketmine\block\Liquid;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\FallingBlock;
use pocketmine\entity\object\ItemEntity;
use pocketmine\entity\object\Painting;
use pocketmine\entity\object\PaintingMotive;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;
use pocketmine\world\particle\EndermanTeleportParticle;
use pocketmine\world\sound\EndermanTeleportSound;

/**
 * Main Herobrine entity implementation
 *
 * Extends Human class to provide:
 * - Three-phase lifecycle (start/game/end)
 * - Custom boss bar tracking
 * - Special abilities and attacks
 * - Reward distribution system
 * - Advanced damage handling
 */
class HerobrineEntity extends Human
{
    use EntityManager;
    use EntitySession;
    use EntityAbilitiesTrait;

    /** @var BossBar The boss bar tracking Herobrine's status */
    public BossBar $bar;

    /** @var int Current phase (0=start, 1=game, 2=end) */
    public int $phase = 0;

    /** @var array Spawn position coordinates */
    public array $spawnPosition = [];

    /** @var array Players who have received rewards */
    public array $rewards = [];

    /** @var bool Game session active flag */
    public bool $isInGame = false;

    /** @var int Start phase duration (ticks) */
    public int $startTime = 20;

    /** @var int Game phase duration (ticks) */
    public int $gameTime = 20;

    /** @var int End phase duration (ticks) */
    public int $endTime = 20 * 60;

    /**
     * Constructor
     *
     * @param Location $location Spawn location
     * @param Skin $skin Entity skin
     * @param CompoundTag|null $nbt NBT data
     */
    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $skin, $nbt);
        $this->bar = new BossBar();
    }

    /**
     * Sets the current phase
     *
     * @param int $currentPhase Phase to set (0-2)
     * @throws \InvalidArgumentException If phase is negative
     */
    public function setPhase(int $currentPhase): void
    {
        if ($currentPhase < 0) {
            throw new \InvalidArgumentException("Phase cannot be negative");
        }
        $this->phase = $currentPhase;
    }

    /**
     * Gets the current phase
     *
     * @return int Current phase (0=start, 1=game, 2=end)
     */
    public function getPhase(): int
    {
        return $this->phase;
    }

    /**
     * Handles entity teleportation
     *
     * Adds special effects during game phase
     *
     * @param Vector3 $pos Target position
     * @param float|null $yaw Yaw rotation
     * @param float|null $pitch Pitch rotation
     * @return bool Teleport success status
     */
    public function teleport(Vector3 $pos, ?float $yaw = null, ?float $pitch = null): bool
    {
        if ($this->getPhase() === $this->PHASE_GAME()) {
            $this->getWorld()->addSound($pos->asVector3(), new EndermanTeleportSound());
            $this->getWorld()->addParticle($pos, new EndermanTeleportParticle());
        }
        return parent::teleport($pos, $yaw, $pitch);
    }

    /**
     * Gets maximum health
     *
     * @return int Maximum health value (200)
     */
    public function getMaxHealth(): int
    {
        return 200;
    }

    /**
     * Handles jumping
     *
     * Allows jumping from ground or liquid
     */
    public function jump(): void
    {
        if ($this->isOnGround() || $this->getWorld()->getBlock($this->location) instanceof Liquid) {
            $this->motion = $this->motion->withComponents(null, $this->jumpVelocity, null);
        }
        parent::jump();
    }

    /**
     * Base entity tick handler
     *
     * Manages:
     * - Weather effects
     * - Boss bar updates
     * - Phase-specific behavior
     * - Ability execution
     *
     * @param int $tickDiff Ticks since last update
     * @return bool Parent tick result
     */
    protected function entityBaseTick(int $tickDiff = 1): bool
    {
        $this->handleWeather();
        $this->handleBossBar();
        $this->getHungerManager()->setSaturation(20);
        $this->getHungerManager()->setFood(20);
        $this->setSkin($this->getSkin());

        // Start phase behavior
        if ($this->getPhase() === $this->PHASE_START()) {
            $this->setNameTag("Herobrine");
            $this->bar->setPercentage($this->bar->getPercentage() + 1 / $this->getMaxHealth());
            $this->startTime--;
            $this->startSession($this);
        }

        // Game phase behavior
        if ($this->getPhase() === $this->PHASE_GAME()) {
            $this->gameTime--;
            $this->setNameTag("Herobrine");
            $this->gameSession($this);
            $this->bar->setPercentage($this->getHealth() / $this->getMaxHealth());

            // Execute abilities
            $this->look();
            $this->normalAttack();
            $this->sneak();
            $this->randomMove();
            $this->doRandomTeleport();

            if (!$this->isInGame) {
                $this->gameSession($this);
            }
        }

        // End phase behavior
        if ($this->getPhase() === $this->PHASE_END()) {
            $this->endTime--;
            $this->endSession($this);
        }

        return parent::entityBaseTick($tickDiff);
    }

    /**
     * Sends entity data to players
     *
     * Custom implementation to sync nametag and boss bar
     *
     * @param array|null $targets Target players
     * @param array|null $data Entity data
     */
    public function sendData(?array $targets, ?array $data = null): void
    {
        $targets = $targets ?? $this->hasSpawned;
        $data = $data ?? $this->getAllNetworkData();

        if(!isset($data[EntityMetadataProperties::NAMETAG])){
            parent::sendData($targets, $data);
            return;
        }

        foreach($targets as $p){
            $data[EntityMetadataProperties::NAMETAG] = new StringMetadataProperty($this->getNameTag());
            $p->getNetworkSession()->getEntityEventBroadcaster()->syncActorData([$p->getNetworkSession()], $this, $data);
        }

        $this->bar->setTitle($this->getNameTag());
        $this->bar->setPercentage(0);
    }

    /**
     * Initializes entity from NBT
     *
     * @param CompoundTag $nbt Entity NBT data
     */
    protected function initEntity(CompoundTag $nbt): void
    {
        parent::initEntity($nbt);
        $this->setNameTagAlwaysVisible();
        $this->setNameTagVisible();
        $this->phase = $nbt->getInt("Phase");
    }

    /**
     * Saves entity to NBT
     *
     * @return CompoundTag Saved NBT data
     */
    public function saveNBT(): CompoundTag
    {
        $nbt = parent::saveNBT();
        $nbt->setInt("Phase", $this->phase);
        return $nbt;
    }

    /**
     * Handles damage events
     *
     * Manages:
     * - Phase-specific damage rules
     * - Reward distribution
     * - Death handling
     * - Immunity system
     *
     * @param EntityDamageEvent $source Damage event
     */
    public function attack(EntityDamageEvent $source): void
    {
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();

            // Game phase damage handling
            if ($this->getPhase() === $this->PHASE_GAME()) {
                if ($damager instanceof Player) {
                    $this->bar->addPlayer($damager);
                }
                Sound::playSound($this, Sound::MOB_WITHER_AMBIENT);
                $source->setKnockBack(0);
            }

            // End phase reward handling
            if ($this->getPhase() === $this->PHASE_END()) {
                if ($damager instanceof Player) {
                    if (isset($this->rewards[$damager->getName()])) {
                        $damager->sendMessage("§cYou have taken the reward please appear new herobrine again!");
                        return;
                    }

                    ResinAPI::getInstance()->sendInvoice(
                        $damager,
                        function (Player $player, string $resinType, int $amount) {
                            $inventory = Herobrine::getInstance()->rewardsManager->menu->getInventory();
                            $items = [];
                            foreach ($inventory->getContents() as $slot => $item) {
                                if ($item->getTypeId() !== VanillaItems::AIR()->getTypeId()) {
                                    $items[] = $item;
                                }
                            }

                            $item = $items[array_rand($items)];
                            $player->getInventory()->addItem($item);
                            $this->rewards[$player->getName()] = true;
                            $player->sendMessage("§aYou got §b".$item->getName()." §afrom Herobrine");
                        },
                    );
                }
            }
        }

        // Damage immunities
        if ($source->getCause() === EntityDamageEvent::CAUSE_FIRE ||
            $source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK ||
            $source->getCause() === EntityDamageEvent::CAUSE_LAVA ||
            $source->getCause() === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION ||
            $source->getCause() === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION ||
            $source->getCause() === EntityDamageEvent::CAUSE_DROWNING ||
            $source->getCause() === EntityDamageEvent::CAUSE_FALL
        ) {
            $source->cancel();
            return;
        }

        // Phase-based immunities
        if ($this->getPhase() === $this->PHASE_START() ||
            $this->getPhase() === $this->PHASE_END()
        ) {
            $source->cancel();
        }

        // Death handling
        if ($source->getFinalDamage() >= $this->getHealth()) {
            $source->cancel();
            $this->setPhase($this->PHASE_END());

            foreach ($this->getWorld()->getPlayers() as $player) {
                $this->bar->removePlayer($player);
            }

            // Clean up position data
            $dataFolder = Herobrine::getInstance()->getDataFolder() . "data/";
            $filePath = $dataFolder . "position.json";
            if (file_exists($filePath)) {
                $positions = json_decode(file_get_contents($filePath), true);

                if (is_array($positions)) {
                    $updatedPositions = array_filter($positions, function ($position) {
                        return !(
                            $position['world'] === $this->getWorld()->getFolderName()
                        );
                    });

                    file_put_contents($filePath, json_encode(array_values($updatedPositions), JSON_PRETTY_PRINT));
                }
            }

            $this->setHealth($this->getMaxHealth());
            $this->sendLightning();
            Weather::clear($this->getWorld());
            Sound::playSound($this, Sound::MOB_WITHER_DEATH);
        }

        parent::attack($source);
    }

    /**
     * Handles death sequence
     */
    protected function onDeath(): void
    {
        foreach ($this->getWorld()->getPlayers() as $player) {
            $this->bar->removePlayer($player);
        }

        // Clean up position data
        $dataFolder = Herobrine::getInstance()->getDataFolder() . "data/";
        $filePath = $dataFolder . "position.json";
        if (file_exists($filePath)) {
            $positions = json_decode(file_get_contents($filePath), true);

            if (is_array($positions)) {
                $updatedPositions = array_filter($positions, function ($position) {
                    return !(
                        $position['world'] === $this->getWorld()->getFolderName()
                    );
                });

                file_put_contents($filePath, json_encode(array_values($updatedPositions), JSON_PRETTY_PRINT));
            }
        }

        $this->sendLightning();
        Weather::clear($this->getWorld());
        Sound::playSound($this, Sound::MOB_WITHER_DEATH);
        parent::onDeath();
    }

    /**
     * Gets death drops
     *
     * @return array Dropped items (player head)
     */
    public function getDrops(): array
    {
        return [
            VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER)->asItem(),
        ];
    }

    /**
     * Finds nearest entity within range
     *
     * @param int $maxDistance Maximum search distance
     * @return array ['entity' => Entity|null, 'distance' => int]
     */
    public function getNearestEntity(int $maxDistance): array
    {
        $nearestEntity = null;
        $closestDistance = PHP_INT_MAX;

        foreach($this->getWorld()->getEntities() as $entity) {
            $distance = $this->location->distance($entity->getLocation());

            if($distance < $closestDistance && $distance <= $maxDistance) {
                if (!$entity instanceof HerobrineEntity &&
                    !$entity instanceof SkullEntity &&
                    !$entity instanceof HerobrineHead) {

                    if ($entity instanceof ExperienceOrb ||
                        $entity instanceof FallingBlock ||
                        $entity instanceof ItemEntity ||
                        $entity instanceof Painting ||
                        $entity instanceof PaintingMotive ||
                        $entity instanceof PrimedTNT) {
                        continue;
                    }

                    $nearestEntity = $entity;
                    $closestDistance = $distance;
                }
            }
        }

        return ['entity' => $nearestEntity, 'distance' => $closestDistance];
    }
}