<?php

namespace pixelwhiz\herobrine\entity\sessions;

use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\MobHead;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class EntitySessionHandler implements Listener {

    use EntitySession;
    use EntityManager;

    private float $lastClickTime = 0;

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $action = $event->getAction();
        $block = $event->getBlock();
        $item = $event->getItem();

        if ($action === PlayerInteractEvent::LEFT_CLICK_BLOCK || $action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            if ($item->getTypeId() === VanillaItems::NETHER_STAR()->getTypeId() && $block instanceof MobHead && $block->getMobHeadType() === MobHeadType::PLAYER) {
                $currentTime = microtime(true);
                if ($currentTime - $this->lastClickTime < 3) {
                    return;
                }

                $this->lastClickTime = $currentTime;

                if ($this->trySpawnFromPattern($player, $block)) {
                    $this->spawnSession($block->getPosition());
                    Weather::saveTime($player->getWorld());
                    if ($player->getGamemode() !== GameMode::CREATIVE()) {
                        $item->setCount($item->getCount() - 1);
                        $player->getInventory()->setItemInHand($item);
                    }
                }
            }
        }
    }


    private function trySpawnFromPattern(Player $source, MobHead $block): bool {
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

}