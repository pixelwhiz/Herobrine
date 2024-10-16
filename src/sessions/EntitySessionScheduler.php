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

use Cassandra\ExecutionOptions;
use pixelwhiz\herobrine\entity\HerobrineEntity;
use pixelwhiz\herobrine\entity\HerobrineHead;
use pixelwhiz\herobrine\utils\BlockPattern;
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
    private ?HerobrineEntity $entity;


    public function __construct(int $phase, Position $pos, ?HerobrineEntity $entity = null) {
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
                    $entityHead = new HerobrineHead(Location::fromObject($pos->add(0.5, 0, 0.5), $world), $this->getSkin());

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

                    $entity = new HerobrineEntity(Location::fromObject($this->pos->add(0.5, 2, 0.5), $this->pos->getWorld()), $this->getSkin(), $this->createBaseNBT($pos));
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
                    $packet->actorUniqueId = HerobrineEntity::nextRuntimeId();
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
                if (!$this->entity instanceof HerobrineEntity) $this->getHandler()->cancel();

                $entity = $this->entity;

                $pos = $entity->getPosition();
                $world = $entity->getWorld();

                if ($this->startTime <= 10 && $this->startTime >= 1) {
                    $entity->setPhase($this->PHASE_START());
                }

                if ($this->startTime === 1) {
                    $world->addSound($pos, new ExplodeSound(), $world->getPlayers());

                    BlockPattern::clearPattern($world, $pos);

                    $entity->setFireTicks(0);
                    $entity->setPhase($this->PHASE_GAME());
                }

                if ($this->startTime === 0) {
                    $this->getHandler()->cancel();
                }

                break;
            case $this->PHASE_GAME():
                if (!$this->entity instanceof HerobrineEntity) $this->getHandler()->cancel();

                $entity = $this->entity;

                $pos = $entity->getPosition();
                $world = $entity->getWorld();
                break;
            case $this->PHASE_END():
                $this->endTime--;
                break;
        }
    }

}