<?php

namespace pixelwhiz\herobrine\utils;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class BlockPattern {

    public static function clearPattern(World $world, Position $pos) : void {
        
        $blocks = [
            $world->getBlock($pos->subtract(1, 2, 0)->add(0, 0, 1)),
            $world->getBlock($pos->subtract(0, 2, 1)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(1, 2, 1)->add(0, 0, 0)),

            $world->getBlock($pos->subtract(1, 2, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 2, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 2, 0)->add(0, 0, 1)),

            $world->getBlock($pos->subtract(0, 2, 0)->add(1, 0, 1)),
            $world->getBlock($pos->subtract(0, 2, 0)->add(1, 0, 0)),
            $world->getBlock($pos->subtract(0, 2, 1)->add(1, 0, 0)),
            ## GOLD ##

            ## REDSTONE ##
            $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 1)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(1, 1, 1)->add(0, 0, 0)),

            $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 1)),

            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1)),
            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0)),
        ];

        foreach ($blocks as $block) {
            if ($block instanceof Block) {
                $world->addParticle($block->getPosition(), new BlockBreakParticle($block), $world->getPlayers());
                $world->setBlock($block->getPosition(), VanillaBlocks::AIR());
            }
        }
    }

}