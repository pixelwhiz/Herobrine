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

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\entity\HerobrineEntity;
use pixelwhiz\herobrine\entity\HerobrineHead;
use pixelwhiz\herobrine\Herobrine;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;

trait EntityManager {

    /**
     * @method HerobrineHead setSkin()
     * @return string
     */
    public function HEAD_GEOMETRY() : string { return '{"format_version": "1.12.0", "minecraft:geometry": [{"description": {"identifier": "geometry.player_head", "texture_width": 64, "texture_height": 64, "visible_bounds_width": 2, "visible_bounds_height": 4, "visible_bounds_offset": [0, 0, 0]}, "bones": [{"name": "Head", "pivot": [0, 24, 0], "cubes": [{"origin": [-4, 0, -4], "size": [8, 8, 8], "uv": [0, 0]}, {"origin": [-4, 0, -4], "size": [8, 8, 8], "inflate": 0.5, "uv": [32, 0]}]}]}]}'; }


    /**
     * @TODO: Create Base NBT at Minecart()
     */
    public function createBaseNBT(): CompoundTag {
        return CompoundTag::create()->setInt("Phase", 1);
    }

    /**
     * @method HerobrineEntity setSkin()
     * @return Skin
     * @throws \JsonException
     */
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

    /**
     * @method HerobrineHead setSkin()
     * @param HerobrineHead $entity
     * @return Skin
     * @throws \JsonException
     */
    public function getHeadSkin(HerobrineHead $entity) : Skin {
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
