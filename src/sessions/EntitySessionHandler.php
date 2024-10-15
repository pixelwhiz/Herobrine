<?php

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\MobHead;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pixelwhiz\herobrine\utils\BlockPattern;
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

                if (BlockPattern::trySpawnFromPattern($player, $block)) {
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
}