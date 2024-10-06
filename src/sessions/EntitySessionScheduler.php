<?php

namespace pixelwhiz\herobrine\sessions;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\entity\EntityHead;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;

class EntitySessionScheduler extends Task {

    use EntitySession;
    use EntityManager;

    private int $startTime = 15;
    private int $endTime = 10;
    private int $phase;
    private Position $pos;

    public function __construct(int $phase, Position $pos) {
        $this->phase = $phase;
        $this->pos = $pos;
    }

    public function onRun(): void
    {
        switch ($this->phase) {
            case $this->PHASE_START():
                $this->startTime--;

                $pos = $this->pos;
                $world = $this->pos->getWorld();
                $block = $world->getBlock($pos);

                if ($this->startTime === 14) {
                    $entityHead = new EntityHead(Location::fromObject($pos->add(0.5, 0, 0.5), $world), $this->getSkin());
                    $world->setTime(18000);

                    $nearestPlayer = null;
                    foreach ($entityHead->getWorld()->getPlayers() as $player) {
                        $distance = $entityHead->getPosition()->distance($player->getPosition()->asVector3());
                        if ($distance < PHP_FLOAT_MAX) {
                            $nearestPlayer = $player;
                        }
                    }
                    $yaw = $nearestPlayer !== null ? $nearestPlayer->getLocation()->getYaw() - 180 : 0;
                    $entityHead->setRotation($yaw, 0);

                    $world->setBlock($pos, VanillaBlocks::AIR());
                    $world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));
                    $entityHead->spawnToAll();

                }

                if ($this->startTime === 10) {

                    Weather::thunder($world);

                    $world->setBlock($pos, VanillaBlocks::NETHERRACK());
                    $world->setBlock($pos->add(0, 1, 0), VanillaBlocks::FIRE());
                    $world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));

                    $entity = new Entity(Location::fromObject($this->pos->add(0.5, 2, 0.5), $this->pos->getWorld()), $this->getSkin());
                    $nearestPlayer = null;
                    foreach ($entity->getWorld()->getPlayers() as $player) {
                        $distance = $entity->getPosition()->distance($player->getPosition()->asVector3());
                        if ($distance < PHP_FLOAT_MAX) {
                            $nearestPlayer = $player;
                        }
                    }
                    $yaw = $nearestPlayer !== null ? $nearestPlayer->getLocation()->getYaw() - 180 : 0;
                    $entity->setRotation($yaw, 0);
                    $entity->spawnToAll();

                    $packet = new AddActorPacket();
                    $packet->actorUniqueId = Entity::nextRuntimeId();
                    $packet->actorRuntimeId = 1;
                    $packet->position = $entity->getPosition()->asVector3();
                    $packet->type = EntityIds::LIGHTNING_BOLT;
                    $packet->yaw = $entity->getLocation()->getYaw();
                    $packet->syncedProperties = new PropertySyncData([], []);
                    $sound = PlaySoundPacket::create("ambient.weather.thunder", $pos->getX(), $pos->getY(), $pos->getZ(), 100, 1);
                    NetworkBroadcastUtils::broadcastPackets($entity->getWorld()->getPlayers(), [$packet, $sound]);
                    $world->addParticle($entity->getPosition()->floor(), new BlockBreakParticle($block));

                }

                if ($this->startTime === 0) {
                    $this->getHandler()->cancel();
                }

                break;
            case $this->PHASE_END():
                $this->endTime--;
                break;
        }
    }

}