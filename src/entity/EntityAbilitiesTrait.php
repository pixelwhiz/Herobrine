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

use pixelwhiz\herobrine\utils\Sound;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\Block;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Water;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\World;

/**
 * Trait providing special abilities for Herobrine entities
 *
 * This trait implements:
 * - Lightning effects
 * - Weather control
 * - Boss bar management
 * - Movement behaviors
 * - Combat abilities
 * - Teleportation
 */
trait EntityAbilitiesTrait
{
    /** @var float Timestamp of last projectile attack */
    private float $lastShootTime = 0;

    /**
     * Creates lightning effect at Herobrine's location
     *
     * Generates:
     * - Lightning bolt entity
     * - Thunder sound
     * - Explosion sound
     * - Block break particles
     */
    public function sendLightning(): void
    {
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $block = $world->getBlock($pos);

        $packet = new AddActorPacket();
        $packet->actorUniqueId = HerobrineEntity::nextRuntimeId();
        $packet->actorRuntimeId = 1;
        $packet->position = $this->getPosition()->asVector3();
        $packet->type = EntityIds::LIGHTNING_BOLT;
        $packet->yaw = $this->getLocation()->getYaw();
        $packet->syncedProperties = new PropertySyncData([], []);

        $sound = PlaySoundPacket::create(
            "ambient.weather.thunder",
            $pos->getX(),
            $pos->getY(),
            $pos->getZ(),
            100,
            1
        );

        $world->addSound($pos, new ExplodeSound());
        NetworkBroadcastUtils::broadcastPackets($this->getWorld()->getPlayers(), [$packet, $sound]);
        $world->addParticle($this->getPosition()->floor(), new BlockBreakParticle($block));
    }

    /**
     * Manages weather effects based on Herobrine's phase
     *
     * - Activates thunder in current world during active phases
     * - Clears weather in other worlds
     * - Updates boss bar visibility
     */
    public function handleWeather(): void
    {
        if ($this->getPhase() === $this->PHASE_START() ||
            $this->getPhase() === $this->PHASE_GAME()) {

            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                if ($this->getWorld()->getFolderName() === $player->getWorld()->getFolderName()) {
                    Weather::thunder($this->getWorld());
                } else {
                    Weather::clear($player->getWorld());
                    $this->bar->removePlayer($player);
                }
            }
        }
    }

    /**
     * Manages boss bar visibility for nearby players
     *
     * Shows boss bar to players within 35 blocks
     * Hides from players further away
     */
    public function handleBossBar(): void
    {
        if ($this->getPhase() === $this->PHASE_START() ||
            $this->getPhase() === $this->PHASE_GAME()) {

            foreach ($this->getWorld()->getPlayers() as $player) {
                if ($this->getLocation()->distance($player->getLocation()->asVector3()) < 35) {
                    $this->bar->addPlayer($player);
                } else {
                    $this->bar->removePlayer($player);
                }
            }
        }
    }

    /**
     * Handles sneaking behavior
     *
     * Randomly toggles sneaking when no valid target is nearby
     * Always stands up when target is present
     */
    public function sneak(): void
    {
        $nearestEntity = $this->getNearestEntity(35)["entity"];
        if (!$this->isValidTarget($nearestEntity)) {
            $chanceToSneak = mt_rand(0, 100);
            if ($chanceToSneak === 50) {
                $this->setSneaking(true);
            }

            if ($chanceToSneak === 25) {
                $this->setSneaking(false);
            }
        } else {
            $this->setSneaking(false);
        }
    }

    /**
     * Handles random movement patterns
     *
     * Includes:
     * - Swimming in liquids
     * - Jumping out of liquids
     * - Random directional movement
     */
    public function randomMove(): void
    {
        $nearestEntity = $this->getNearestEntity(35)['entity'];

        // Handle liquid environments
        if ($this->getWorld()->getBlock($this->getPosition()->add(0, 2, 0)) instanceof Liquid) {
            $this->setSwimming(true);
            $this->doRandomTeleport();
        } else {
            $this->setSwimming(false);
        }

        if ($this->getWorld()->getBlock($this->getLocation()->add(0, 1, 0)) instanceof Liquid) {
            $this->jump();
            $this->doRandomTeleport();
        }

        // Random movement when no target
        if (!$this->isValidTarget($nearestEntity)) {
            $chanceToMove = mt_rand(0, 100);

            if ($chanceToMove <= 5) {
                $blockInFront = $this->getWorld()->getBlock(
                    $this->getLocation()->addVector($this->getDirectionVector())->floor()
                );

                if (!$blockInFront->isTransparent() && $blockInFront->isSolid()) {
                    $this->setMotion(new Vector3(0, 0.5, 0));
                }

                $this->move(
                    $this->getDirectionVector()->getX(),
                    $this->getDirectionVector()->getY(),
                    $this->getDirectionVector()->getZ()
                );
            }
        }
    }

    /**
     * Handles looking behavior
     *
     * - Looks at valid targets
     * - Occasionally looks at creative mode players
     * - Randomly looks around when no target
     */
    public function look(): void
    {
        $nearestEntity = $this->getNearestEntity(35)['entity'];
        if ($this->isValidTarget($nearestEntity)) {
            $this->lookAt($nearestEntity->getLocation()->add(0, 1, 0));
        } else {
            $chanceToLook = mt_rand(0, 100);

            if ($chanceToLook === 50 &&
                $nearestEntity instanceof Player &&
                $nearestEntity->getGamemode() === GameMode::CREATIVE) {
                $this->lookAt($nearestEntity->getLocation()->add(0, 1, 0));
            }

            if ($chanceToLook <= 25 && $chanceToLook > 24) {
                $randomX = $this->getLocation()->getX() + mt_rand(-10, 10);
                $randomY = $this->getLocation()->getY() + mt_rand(-5, 5);
                $randomZ = $this->getLocation()->getZ() + mt_rand(-10, 10);

                $randomLocation = new Vector3($randomX, $randomY, $randomZ);
                $this->lookAt($randomLocation);
            }
        }
    }

    /**
     * Performs melee attack on nearby targets
     *
     * - Moves toward target
     * - Deals damage at close range
     * - Applies knockback
     */
    public function normalAttack(): void
    {
        $nearestEntity = $this->getNearestEntity(5)['entity'];
        $closestDistance = $this->getNearestEntity(5)['distance'];

        if ($this->isValidTarget($nearestEntity)) {
            $direction = $nearestEntity->getLocation()->subtract(
                $this->getLocation()->x,
                $this->getLocation()->y,
                $this->getLocation()->z
            )->normalize()->multiply(0.3);

            $this->move($direction->getX(), $direction->getY(), $direction->getZ());

            if($closestDistance <= 2.25){
                $damageEvent = new EntityDamageEvent(
                    $nearestEntity,
                    EntityDamageEvent::CAUSE_ENTITY_ATTACK,
                    5
                );
                $this->broadcastAnimation(new ArmSwingAnimation($this));
                $nearestEntity->attack($damageEvent);
                $nearestEntity->knockBack(
                    $this->getDirectionVector()->getX(),
                    $this->getDirectionVector()->getZ()
                );
            }
        }
    }

    /**
     * Shoots projectile skull at target
     *
     * - Has cooldown period
     * - Plays sound effect
     * - Launches skull in target direction
     */
    public function shoot(): void
    {
        $nearestEntity = $this->getNearestEntity(35)['entity'];
        if ($this->isValidTarget($nearestEntity)) {
            $pos = $this->getLocation()->add(0, 1, 0);
            $world = $this->getWorld();

            $currentTime = microtime(true);
            if ($currentTime - $this->lastShootTime < mt_rand(0, 5)) {
                return;
            }

            $this->lastShootTime = $currentTime;

            $skull = new SkullEntity(
                Location::fromObject($pos->add(0, 1, 0),
                    $world),
                $this
            );
            Sound::playSound($skull, Sound::MOB_WITHER_SHOOT);
            $skull->setMotion($this->getDirectionVector()->normalize()->multiply(5));
            $skull->spawnToAll();
        }
    }

    /**
     * Performs random teleportation near target
     *
     * - Random chance to teleport
     * - Validates safe landing spots
     * - Occasionally heals during teleport
     * - Small chance to teleport directly to target
     */
    public function doRandomTeleport(): void
    {
        $chanceToDo = mt_rand(0, 100);
        $radius = 15;
        $nearestEntity = $this->getNearestEntity(35)["entity"];

        if ($chanceToDo <= 5 && $this->isValidTarget($nearestEntity)) {
            $world = $nearestEntity->getWorld();
            $isAllAir = true;

            for ($i = 1; $i <= 2; $i++) {
                $blockBelow = $world->getBlock($nearestEntity->getLocation()->subtract(0, $i, 0));

                if ($blockBelow !== VanillaBlocks::AIR()) {
                    $isAllAir = false;
                    break;
                }
            }

            if (!$isAllAir) {
                $chanceToTeleport = mt_rand(0, 100);

                if ($chanceToTeleport <= 100 && $chanceToTeleport > 75) {
                    $offsetX = mt_rand(-$radius, $radius);
                    $offsetZ = mt_rand(-$radius, $radius);

                    $targetX = $nearestEntity->getLocation()->getX() + $offsetX;
                    $targetY = $nearestEntity->getLocation()->getY();
                    $targetZ = $nearestEntity->getLocation()->getZ() + $offsetZ;

                    $targetPosition = new Vector3($targetX, $targetY, $targetZ);

                    if ($this->isSafeLocation($world, $targetPosition)) {
                        $this->teleport($targetPosition->floor());
                    }
                }

                if ($chanceToTeleport <= 25 && $chanceToTeleport > 20) {
                    $this->setHealth($this->getHealth() + mt_rand(0, 20));
                    $this->sendLightning();
                }

                if ($chanceToTeleport <= 50 && $chanceToTeleport >= 48) {
                    $this->teleport($nearestEntity->getLocation()->floor());
                }
            }
        }
    }

    /**
     * Checks if location is safe for teleportation
     *
     * @param World $world Target world
     * @param Vector3 $position Target position
     * @return bool True if location is safe
     */
    private function isSafeLocation(World $world, Vector3 $position): bool
    {
        for ($i = 0; $i < 5; $i++) {
            $blockBelow = $world->getBlock($position->subtract(0, $i, 0));
        }

        if ($blockBelow->getTypeId() === VanillaBlocks::AIR()->getTypeId()) {
            return false;
        }

        if ($blockBelow->getTypeId() === VanillaBlocks::WATER()->getTypeId() ||
            $blockBelow->getTypeId() === VanillaBlocks::LAVA()->getTypeId()) {
            return false;
        }

        $blockAtTarget = $world->getBlock($position);
        $blockAbove = $world->getBlock($position->add(0, 1, 0));

        return $blockAtTarget->getTypeId() === VanillaBlocks::AIR()->getTypeId() &&
            $blockAbove->getTypeId() === VanillaBlocks::AIR()->getTypeId();
    }

    /**
     * Determines if entity is valid target
     *
     * @param Entity|null $nearestEntity Entity to check
     * @return bool True if valid target (survival/adventure player)
     */
    public function isValidTarget(?Entity $nearestEntity = null): bool
    {
        return $nearestEntity instanceof Player &&
            ($nearestEntity->getGamemode() === GameMode::SURVIVAL ||
                $nearestEntity->getGamemode() === GameMode::ADVENTURE);
    }
}