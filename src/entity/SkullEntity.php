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

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Explosive;
use pocketmine\entity\Location;
use pocketmine\entity\object\FallingBlock;
use pocketmine\entity\projectile\Projectile;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\Explosion;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\HugeExplodeParticle;
use pocketmine\world\sound\ExplodeSound;

/**
 * Wither Skull projectile entity for Herobrine
 *
 * Extends Projectile class to implement:
 * - Custom flight characteristics
 * - Entity impact effects
 * - Block impact effects
 * - Explosive behavior
 */
class SkullEntity extends Projectile
{
    /**
     * Gets the initial gravity value for the projectile
     *
     * Determines how quickly the skull falls during flight
     *
     * @return float Gravity multiplier (0.1 = slow fall)
     */
    protected function getInitialGravity(): float
    {
        return 0.1;
    }

    /**
     * Gets the network entity type ID
     *
     * Identifies the entity type to clients
     *
     * @return string Minecraft entity ID
     */
    public static function getNetworkTypeId(): string
    {
        return EntityIds::WITHER_SKULL;
    }

    /**
     * Gets the initial size information
     *
     * Defines the collision box dimensions
     *
     * @return EntitySizeInfo Size configuration (0.5x0.5 blocks)
     */
    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.5, 0.5);
    }

    /**
     * Gets the initial drag multiplier
     *
     * Controls air resistance during flight
     *
     * @return float Drag coefficient (0.005 = minimal drag)
     */
    protected function getInitialDragMultiplier(): float
    {
        return 0.005;
    }

    /**
     * Handles entity impact events
     *
     * Triggers when skull hits an entity:
     * - Creates explosion effect
     * - Plays explosion sound
     * - Knocks back player targets
     * - Destroys the projectile
     *
     * @param Entity $entityHit The impacted entity
     * @param RayTraceResult $hitResult Ray trace details
     */
    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $this->kill();
        $pos = $this->getPosition();

        $explosion = new Explosion($pos, 3);
        $explosion->explodeB();

        $this->getWorld()->addParticle($pos, new ExplodeParticle());
        $this->getWorld()->addSound($pos, new ExplodeSound());

        if ($entityHit instanceof Player) {
            $entityHit->knockBack(
                $this->getDirectionVector()->getX(),
                $this->getDirectionVector()->getZ()
            );
        }

        parent::onHitEntity($entityHit, $hitResult);
    }

    /**
     * Handles block impact events
     *
     * Triggers when skull hits a block:
     * - Creates circular fire pattern
     * - Generates explosion effect
     * - Plays explosion sound
     * - Destroys the projectile
     *
     * @param Block $blockHit The impacted block
     * @param RayTraceResult $hitResult Ray trace details
     */
    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
    {
        $this->close();
        $world = $this->getWorld();
        $pos = $this->getPosition();

        // Create circular fire spread pattern
        $boundaries = 0.1 * 2;
        for ($x = $boundaries; $x >= -$boundaries; $x -= 0.1) {
            for ($z = $boundaries; $z >= -$boundaries; $z -= 0.1) {
                $fire = new FallingBlock(
                    Location::fromObject($pos, $world),
                    VanillaBlocks::FIRE()
                );
                $fire->setMotion(new Vector3($x, 0.1, $z));
                $fire->setOnFire(5);
                $fire->spawnToAll();
            }
        }

        $explosion = new Explosion($pos, 3);
        $explosion->explodeB();

        $this->getWorld()->addParticle($pos, new HugeExplodeParticle());
        $this->getWorld()->addSound($pos, new ExplodeSound());

        parent::onHitBlock($blockHit, $hitResult);
    }
}