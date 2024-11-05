<?php

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

class SkullEntity extends Projectile {

    protected function getInitialGravity(): float
    {
        return 0.1;
    }

    public static function getNetworkTypeId(): string
    {
        return EntityIds::WITHER_SKULL;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo
    {
        return new EntitySizeInfo(0.5, 0.5);
    }

    protected function getInitialDragMultiplier(): float
    {
        return 0.005;
    }

    protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void
    {
        $this->kill();
        $pos = $this->getPosition();
        $explosion = new Explosion($pos, 3);
        $explosion->explodeB();
        $this->getWorld()->addParticle($pos, new ExplodeParticle(), $this->getWorld()->getPlayers());
        $this->getWorld()->addSound($pos, new ExplodeSound(), $this->getWorld()->getPlayers());
        if ($entityHit instanceof Player) {
            $entityHit->knockBack($this->getDirectionVector()->getX(), $this->getDirectionVector()->getZ());
        }
        parent::onHitEntity($entityHit, $hitResult);
    }

    protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void
    {
        $this->close();
        $world = $this->getWorld();
        $pos = $this->getPosition();
        $boundaries = 0.1 * 2;
        for ($x = $boundaries; $x >= -$boundaries; $x -= 0.1) {
            for ($z = $boundaries; $z >= -$boundaries; $z -= 0.1) {
                $fire = new FallingBlock(Location::fromObject($pos, $world), VanillaBlocks::FIRE());
                $fire->setMotion(new Vector3($x, 0.1, $z));
                $fire->setOnFire(5);
                $fire->spawnToAll();
            }
        }

        $explosion = new Explosion($pos, 3);
        $explosion->explodeB();
        $this->getWorld()->addParticle($pos, new HugeExplodeParticle(), $this->getWorld()->getPlayers());
        $this->getWorld()->addSound($pos, new ExplodeSound(), $this->getWorld()->getPlayers());
        parent::onHitBlock($blockHit, $hitResult);
    }

}