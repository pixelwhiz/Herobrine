<?php

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\entity\EntityHead;
use pixelwhiz\herobrine\Herobrine;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

trait EntityManager {

    public function HEAD_GEOMETRY() : string { return '{"format_version": "1.12.0", "minecraft:geometry": [{"description": {"identifier": "geometry.player_head", "texture_width": 64, "texture_height": 64, "visible_bounds_width": 2, "visible_bounds_height": 4, "visible_bounds_offset": [0, 0, 0]}, "bones": [{"name": "Head", "pivot": [0, 24, 0], "cubes": [{"origin": [-4, 0, -4], "size": [8, 8, 8], "uv": [0, 0]}, {"origin": [-4, 0, -4], "size": [8, 8, 8], "inflate": 0.5, "uv": [32, 0]}]}]}]}'; }

    public function getSkin() : Skin {
        $path = Herobrine::getInstance()->getDataFolder() . "/skins/herobrine.png";
        $img = @imagecreatefrompng($path);
        if ($img === false) {
            throw new \Exception("Failed to create image from the provided path: $path");
        }

        $bytes = '';
        $l = (int) @getimagesize($path)[1];

        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        @imagedestroy($img);
        return new Skin("herobrine", $bytes);
    }

    public function getHeadSkin(EntityHead $entity) : Skin {
        return new Skin($entity->getSkin()->getSkinId(), $entity->getSkin()->getSkinData(), '', 'geometry.player_head', $this->HEAD_GEOMETRY());
    }

    public function getMainWeapon() : Item {
        $item = VanillaItems::DIAMOND_SWORD();
        $item->setCustomName("Herobrine's Sword");
        return $item;
    }

    public function getSecondaryWeapon() : Item {
        $item = VanillaItems::DIAMOND_PICKAXE();
        $item->setCustomName("Herobrine's PickAxe");
        return $item;
    }

}
