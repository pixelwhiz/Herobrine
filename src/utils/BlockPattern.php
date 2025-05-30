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

namespace pixelwhiz\herobrine\utils;

use pocketmine\block\Block;
use pocketmine\block\MobHead;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class BlockPattern {

    /**
     *
     * @description: Try spawn Minecart() with some pattern including GOLD(), REDSTONE_TORCH() and REDSTONE_WIRE().
     * @priority: HIGH
     *
     */
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

        // TODO: Check block GOLD type id
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

            // TODO: Check block REDSTONE type id
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


    /**
     *
     * @description: Clear and replace any BLOCKS to AIR when startTime === 0
     * @param World $world
     * @param Position $pos
     * @return void
     */
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
     * @description: protect SPAWN_PHASE() block pattern from BlockBreakEvent()
     * @priority HIGH
     * @param BlockBreakEvent $event
     * @param World $world
     * @param Position $pos
     * @return void
     */
    public static function protectSpawnPattern(BlockBreakEvent $event, World $world, Position $pos) : void {

        $blocks = [
            $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 1)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1)),
            $world->getBlock($pos->subtract(1, 1, 1)->add(0, 0, 0)),

            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 1)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0)),

            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1)),
            $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 0)),
            $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0)),

            $world->getBlock($pos->subtract(0, 0, 0)->add(0, 0, 1)),
            $world->getBlock($pos->subtract(1, 0, 0)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 0, 1)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 0, 0)->add(1, 0, 0)),
            
            $world->getBlock($pos->subtract(1, 0, 0)->add(0, 0, 1)),
            $world->getBlock($pos->subtract(1, 0, 1)->add(0, 0, 0)),
            $world->getBlock($pos->subtract(0, 0, 0)->add(1, 0, 1)),
            $world->getBlock($pos->subtract(0, 0, 1)->add(1, 0, 0))
        ];

        foreach ($blocks as $block) {
            if ($block instanceof Block and $event->getBlock() === $block) {

                // TODO: Cancel block breaking
                $event->cancel();
            }
        }
    }


    /**
     *
     * @description: protect START_PHASE() block pattern when BlockbreakEvent()
     * @priority HIGH
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

                // TODO: Cancel block breaking
                $event->cancel();
            }
        }
    }

}