<?php

namespace pixelwhiz\herobrine\entity\sessions;

use pixelwhiz\herobrine\entity\Entity;
use pixelwhiz\herobrine\entity\EntityHead;
use pixelwhiz\herobrine\utils\Weather;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;

class EntitySessionScheduler extends Task {

    use EntitySession;
    use EntityManager;

    private int $spawnTime = 5;
    private int $startTime = 10;
    private int $endTime = 10;


    private int $phase;
    private Position $pos;
    private ?Entity $entity;

    public function __construct(int $phase, Position $pos, ?Entity $entity = null) {
        $this->phase = $phase;
        $this->pos = $pos;
        $this->entity = $entity;
    }

    public function onRun(): void
    {
        switch ($this->phase) {
            case $this->PHASE_SPAWN():
                $this->spawnTime--;

                $pos = $this->pos;
                $world = $this->pos->getWorld();
                $block = $world->getBlock($pos);

                if ($this->spawnTime === 4) {
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

                if ($this->spawnTime === 2) {
                    Weather::thunder($world);
                }

                if ($this->spawnTime === 0) {
                    $world->setBlock($pos, VanillaBlocks::SOUL_SOIL());
                    $world->setBlock($pos->add(0, 1, 0), VanillaBlocks::SOUL_FIRE());
                    $world->addParticle($pos->add(0.5, 0.5, 0.5), new BlockBreakParticle($block));

                    $entity = new Entity(Location::fromObject($this->pos->add(0.5, 2, 0.5), $this->pos->getWorld()), $this->getSkin(), $this->createBaseNBT($pos));
                    $entity->setPhase($this->PHASE_SPAWN());

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
                    //$entity->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::IMMOBILE, true);

                    $packet = new AddActorPacket();
                    $packet->actorUniqueId = Entity::nextRuntimeId();
                    $packet->actorRuntimeId = 1;
                    $packet->position = $entity->getPosition()->asVector3();
                    $packet->type = EntityIds::LIGHTNING_BOLT;
                    $packet->yaw = $entity->getLocation()->getYaw();
                    $packet->syncedProperties = new PropertySyncData([], []);
                    $sound = PlaySoundPacket::create("ambient.weather.thunder", $pos->getX(), $pos->getY(), $pos->getZ(), 100, 1);
                    $world->addSound($pos, new ExplodeSound(), $world->getPlayers());
                    NetworkBroadcastUtils::broadcastPackets($entity->getWorld()->getPlayers(), [$packet, $sound]);
                    $world->addParticle($entity->getPosition()->floor(), new BlockBreakParticle($block));

                    $this->startSession($entity);
                    $this->getHandler()->cancel();
                }

                break;
            case $this->PHASE_START():
                $this->startTime--;
                if (!$this->entity instanceof Entity) $this->getHandler()->cancel();

                $entity = $this->entity;

                $pos = $entity->getPosition();
                $world = $entity->getWorld();

                if ($this->startTime === 9) {
                    $entity->setPhase($this->PHASE_START());
                }

                if ($this->startTime === 1) {
                    $world->addSound($pos, new ExplodeSound(), $world->getPlayers());
                    $blocksPos = [

                        ## GOLD ##
                        $world->getBlock($pos->subtract(1, 2, 0)->add(0, 0, 1))->getPosition(),
                        $world->getBlock($pos->subtract(0, 2, 1)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(1, 2, 1)->add(0, 0, 0))->getPosition(),

                        $world->getBlock($pos->subtract(1, 2, 0)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 2, 0)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 2, 0)->add(0, 0, 1))->getPosition(),

                        $world->getBlock($pos->subtract(0, 2, 0)->add(1, 0, 1))->getPosition(),
                        $world->getBlock($pos->subtract(0, 2, 0)->add(1, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 2, 1)->add(1, 0, 0))->getPosition(),
                        ## GOLD ##

                        ## REDSTONE ##
                        $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 1))->getPosition(),
                        $world->getBlock($pos->subtract(0, 1, 1)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(1, 1, 1)->add(0, 0, 0))->getPosition(),

                        $world->getBlock($pos->subtract(1, 1, 0)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 1))->getPosition(),

                        $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 1))->getPosition(),
                        $world->getBlock($pos->subtract(0, 1, 0)->add(1, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 1, 1)->add(1, 0, 0))->getPosition(),
                        ## REDSTONE ##

                        $world->getBlock($pos->subtract(0, 1, 0)->add(0, 0, 0))->getPosition(),
                        $world->getBlock($pos->subtract(0, 0, 0)->add(0, 0, 0))->getPosition(),
                    ];

                    foreach ($blocksPos as $blockPos) {
                        $world->setBlock($blockPos, VanillaBlocks::AIR());
                    }
                    $entity->setPhase($this->PHASE_GAME());
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