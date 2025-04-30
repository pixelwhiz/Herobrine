<?php


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

    public function initialize(): void {
        $this->menu = InvMenu::create(self::TAG_INVENTORY_TYPE);
        self::$nbtSerializer = new BigEndianNbtSerializer();
        $config = $this->plugin->getConfig();
        $this->read($config->get("rewards-key", $this->write()));
    }

    public function write(): string {
        $contents = [];
        foreach($this->menu->getInventory()->getContents() as $slot => $item) {
            $contents[] = $item->nbtSerialize($slot);
        }

        $nbtData = self::$nbtSerializer->write(new TreeRoot(CompoundTag::create()
            ->setTag(self::TAG_INVENTORY, new ListTag($contents, NBT::TAG_Compound))
        ));

        return base64_encode(zlib_encode($nbtData, ZLIB_ENCODING_GZIP));
    }

    public function read(string $data): void {
        $decoded = base64_decode($data);
        $inventoryTag = self::$nbtSerializer->read(zlib_decode($decoded))->mustGetCompoundTag()->getListTag(self::TAG_INVENTORY);

        $contents = [];
        foreach ($inventoryTag as $tag) {
            $contents[$tag->getByte("Slot")] = Item::nbtDeserialize($tag);
        }
        $this->menu->getInventory()->setContents($contents);
    }

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