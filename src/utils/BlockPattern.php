<?php

namespace pixelwhiz\herobrine\utils;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class BlockPattern {

    public static function clearPattern(World $world, Position $pos) : void {
        $blocksPos = [
            ## GOLD ##
            $world->getBlock($pos->subtract(1, 2, 0)->add(0, 0, 1))->getPosition(),
            $world->getBlock($pos->subtract(0, 2, 1)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(1, 2, 1)->add(0, 0, 0))->getPosition(),

            $world->getBlock($pos->subtract(1, 2, 0)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 2, 0)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 2, 0)->add(0, 0, 1))->getPosition(),

            $world->getBlock($pos->subtract(0, 2, 0)->add(1, 0, 1))->getPosition(),
            $world->getBlock($pos->subtract(0, 2, 0)->add(1, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 2, 1)->add(1, 0, 0))->getPosition(),
            ## GOLD ##

            ## REDSTONE ##
            $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 1))->getPosition(),
            $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(1, 1, 1)->add(0, 0, 0))->getPosition(),

            $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 1))->getPosition(),

            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1))->getPosition(),
            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0))->getPosition(),
            ## REDSTONE ##

            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0))->getPosition(),
            $world->getBlock($pos->subtract(0, 0, 0)->add(0, 0, 0))->getPosition(),
        ];

        foreach ($blocksPos as $blockPos) {
            $world->setBlock($blockPos, VanillaBlocks::AIR());
        }
    }

}