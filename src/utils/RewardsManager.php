<?php

namespace pixelwhiz\herobrine\utils;

use pixelwhiz\herobrine\entity\HerobrineEntity;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class RewardsManager {

    public static function giveTo(HerobrineEntity $source, Player $player) : void {
        if (isset($source->playerReward[$player->getName()])) {
            $player->sendMessage(TextFormat::YELLOW . "You have already received the reward.");
        }

        if (!isset($source->playerReward[$player->getName()])) {
            $source->playerReward[] = $player->getName();

            $item = StringToItemParser::getInstance()->parse("diamond_sword");
            $player->getInventory()->addItem($item);

            $player->sendMessage(TextFormat::GREEN . "You have received: \n".
                ""
            );
        }
    }

}