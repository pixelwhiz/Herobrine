<?php

namespace pixelwhiz\herobrine\utils;

use Brick\Math\BigInteger;
use pixelwhiz\herobrine\entity\Entity;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\MobHead;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class BlockPattern {

    public static function trySpawnFromPattern(Player $source, MobHead $block): bool {
        $world = $block->getPosition()->getWorld();
        $pos = $block->getPosition();
        $gold = VanillaBlocks::GOLD()->getTypeId();

        $g1 = $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 1))->getTypeId();
        $g2 = $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0))->getTypeId();
        $g3 = $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0))->getTypeId();
        $g4 = $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1))->getTypeId();
        $g5 = $world->getBlock($pos->subtract(1, 1, 1)->add(0, 0, 0))->getTypeId();

        $g6 = $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 1))->getTypeId();
        $g7 = $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0))->getTypeId();
        $g8 = $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 0))->getTypeId();
        $g9 = $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0))->getTypeId();
        $g10 = $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0))->getTypeId();

        $g11 = $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1))->getTypeId();
        $g12 = $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 0))->getTypeId();
        $g13 = $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0))->getTypeId();

        if ($g1 === $gold and $g2 === $gold and $g3 === $gold and
            $g4 === $gold and $g5 === $gold and $g6 === $gold and
            $g7 === $gold and $g8 === $gold and $g9 === $gold and
            $g10 === $gold and $g11 === $gold and $g12 === $gold and
            $g13 === $gold) {


            $redstone = VanillaBlocks::REDSTONE_WIRE()->getTypeId();
            $r1 = $world->getBlock($pos->subtract(0, 0, 0)->add(0, 0, 1))->getTypeId();
            $r2 = $world->getBlock($pos->subtract(1, 0, 0)->add(0, 0, 0))->getTypeId();
            $r3 = $world->getBlock($pos->subtract(0, 0, 1)->add(0, 0, 0))->getTypeId();
            $r4 = $world->getBlock($pos->subtract(0, 0, 0)->add(1, 0, 0))->getTypeId();

            if ($r1 === $redstone and $r2 === $redstone and $r3 === $redstone and $r4 === $redstone) {

                $redstoneTorch = VanillaBlocks::REDSTONE_TORCH()->getTypeId();
                $rt1 = $world->getBlock($pos->subtract(1, 0, 0)->add(0, 0, 1))->getTypeId();
                $rt2 = $world->getBlock($pos->subtract(1, 0, 1)->add(0, 0, 0))->getTypeId();
                $rt3 = $world->getBlock($pos->subtract(0, 0, 0)->add(1, 0, 1))->getTypeId();
                $rt4 = $world->getBlock($pos->subtract(0, 0, 1)->add(1, 0, 0))->getTypeId();

                if ($rt1 === $redstoneTorch and $rt2 === $redstoneTorch and $rt3 === $redstoneTorch and $rt4 === $redstoneTorch) {
                    return true;
                }

            }
        }

        return false;
    }


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

    /**
     *
     * @description: protect 'Spawn Phase' block pattern from block breaking
     * @priority HIGH
     *
     * @param BlockBreakEvent $event
     * @param World $world
     * @param Position $pos
     * @return void
     */
    public static function protectSpawnPattern(BlockBreakEvent $event, World $world, Position $pos) : void {

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
            if ($block instanceof Block and $event->getBlock() === $block) {
                $event->cancel();
            }
        }
    }


    /**
     *
     * @description: protect 'Start Phase' block pattern from block breaking
     * @priority HIGH
     *
     * @param BlockBreakEvent $event
     * @param World $world
     * @param Position $pos
     * @return void
     */

    public static function protectStartPattern(BlockBreakEvent $event, World $world, Position $pos) : void {

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
            if ($block instanceof Block and $event->getBlock() === $block) {
                $event->cancel();
            }
        }
    }

}