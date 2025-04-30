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

namespace pixelwhiz\herobrine;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;

/**
 * Class RewardsManager
 *
 * Manages the reward inventory system for Herobrine plugin.
 * Handles saving/loading reward inventories to/from config,
 * and provides interface for players to view rewards.
 */
class RewardsManager {

    public Herobrine $plugin;
    public InvMenu $menu;

    public const TAG_INVENTORY = "Inventory";
    public const TAG_NAME = "Herobrine's Rewards";
    public const TAG_INVENTORY_TYPE = InvMenu::TYPE_DOUBLE_CHEST;

    private static BigEndianNbtSerializer $nbtSerializer;

    public function __construct(Herobrine $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Initializes the rewards manager
     * - Creates the inventory menu
     * - Sets up NBT serializer
     * - Loads saved rewards from config or creates new if none exist
     */
    public function initialize(): void {
        $this->menu = InvMenu::create(self::TAG_INVENTORY_TYPE);
        self::$nbtSerializer = new BigEndianNbtSerializer();
        $config = $this->plugin->getConfig();
        $this->read($config->get("rewards-key", $this->write()));
    }

    /**
     * Serializes the current inventory contents to a compressed string
     *
     * @return string Base64 encoded, GZIP compressed NBT data string
     */
    public function write(): string {
        $contents = [];
        foreach($this->menu->getInventory()->getContents() as $slot => $item) {
            $contents[] = $item->nbtSerialize($slot);
        }

        $nbtData = self::$nbtSerializer->write(new TreeRoot(
            CompoundTag::create()
                ->setTag(self::TAG_INVENTORY, new ListTag($contents, NBT::TAG_Compound))
        ));

        return base64_encode(zlib_encode($nbtData, ZLIB_ENCODING_GZIP));
    }

    /**
     * Reads inventory data from a compressed string and loads it into the menu
     *
     * @param string $data The base64 encoded, GZIP compressed NBT data string
     */
    public function read(string $data): void {
        $decoded = base64_decode($data);
        $inventoryTag = self::$nbtSerializer->read(zlib_decode($decoded))
            ->mustGetCompoundTag()
            ->getListTag(self::TAG_INVENTORY);

        $contents = [];
        foreach ($inventoryTag as $tag) {
            $contents[$tag->getByte("Slot")] = Item::nbtDeserialize($tag);
        }
        $this->menu->getInventory()->setContents($contents);
    }

    /**
     * Sends the rewards inventory to a player
     *
     * @param Player $player The player to show the inventory to
     */
    public function sendToPlayer(Player $player) {
        $menu = $this->menu;
        $menu->setName(self::TAG_NAME);

        $menu->setListener(function (InvMenuTransaction $transaction) : InvMenuTransactionResult {
            return $transaction->continue();
        });

        $menu->setInventoryCloseListener(function (Player $viewer, Inventory $inventory) {
            $config = $this->plugin->getConfig();
            $config->set("rewards-key", $this->write());
            $config->save();
        });

        $menu->send($player);
    }
}