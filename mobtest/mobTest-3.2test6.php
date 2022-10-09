<?php

/*
__PocketMine Plugin__
name=mobTest
description=New spawn system for mobs! And testing AI
version=3.2test6
author=zhuowei
class=MobTest
apiversion=12.1
*/

define("MOB_RANGE", 128);
define("CONFIG_VERSION", 1);

class MobTest implements Plugin{

    private $api, $config, $pmserver;
    public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
		$serverReflection = new ReflectionClass('ServerAPI');
		$this->pmserver = $serverReflection->getProperty("server");
		$this->api = $api;

		$this->hp = array(
				10 => 4,
				11 => 10,
				12 => 10,
				13 => 8,

				32 => 20,
				33 => 20,
				34 => 20,
				35 => 16,
				36 => 20,
		);
		
		$this->mob = array(
			"chicken" => 10,
			"cow" => 11,
			"pig" => 12,
			"sheep" => 13,

			"zombie" => 32,
			"creeper" => 33,
			"skeleton" => 34,
			"spider" => 35,
			"pigman" => 36,
		);
		
		$this->mobName = array(
			10 => "Chicken",
			11 => "Cow",
			12 => "Pig",
			13 => "Sheep",

			32 => "Zombie",
			33 => "Creeper",
			34 => "Skeleton",
			35 => "Spider",
			36 => "Pigman",
		);
		
		$this->serverSpawnAnimals = $this->server->api->getProperty("spawn-animals");
		$this->serverSpawnMobs = $this->server->api->getProperty("spawn-mobs");
    }

    public function init(){
		
		$this->ai = new AI($this->api);
		
		$configv = CONFIG_VERSION;
		$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
			"version" => $configv,
			"in seconds",
			"spawnMobs" => 30,
			"scheduleDespawn" => true,
			"despawnTime" => 900,
			"debug" => false,
			"AI" => true
		));
		
		if(CONFIG_VERSION != $this->config->get("version")) console("[MobTest] REMOVE OLD CONFIG FILE!");
		
		//seconds*20(ticks)
		$this->api->schedule($this->config->get("despawnTime")*20, array($this, "scheduleDespawn"), array(), true);
		$this->api->schedule(0, array($this, "spawnMobs"), array());
		if($this->config->get("AI")) $this->api->schedule(0, array($this->ai, "AI"), array(), true);
	}

	public function spawnSchedule(){
		$this->api->schedule($this->config->get("spawnMobs")*2010, array($this, "spawnMobs"), array());
	}

	public function spawnMobs(){
		$o = $this->api->player->online();

		if($this->serverSpawnAnimals == false and $this->serverSpawnMobs == false) return;
		if(count($o) > 0){
			$rand_p = mt_rand(0, (count($o) - 1));
			$world = $this->api->player->get($o[$rand_p])->level;
			$worldName = $world->getName();
			$time = $this->api->time->getDate($world);
			
			$this->mobCount();
			if($this->ecnt > 100){
				$this->spawnSchedule();
				return;
			}

			$this->o = $o;
			$this->rand_p = $rand_p;
			$this->world = $world;
			$this->worldName = $worldName;

			if($time >= 0 and $time <= 9500){
				if(isset($worldName) == "Nether" or $isset($worldName) == "nether"){//Don't spawn animals in nether
					$this->spawnSchedule();
					return;
				}
				$this->spawnDayMobs();
				$this->spawnSchedule();
				return;
			}
			elseif($time >= 10000 and $time <= 18000){
				if(isset($worldName) == "Nether" or isset($worldName) == "nether"){
					$this->spawnNetherMob();
					$this->spawnSchedule();
					return;
				}
				else{
					$this->spawnNightMobs();
					$this->spawnSchedule();
					return;
				}
			}
			else{
				$this->spawnSchedule();
				return;
			}
		}
		else{
			$this->spawnSchedule();
			return;
		}
	}

	public function babyChance(){
		$chance = mt_rand(1,20);
		if($chance == 1) return 1;
		else return 0;
	}

    public function spawnDayMobs(){
			
        $type = mt_rand(10, 13);
        $randomX = mt_rand(5,250);
        $randomZ = mt_rand(5,250);
        $x1 = $randomX + mt_rand(1,3);
		$z1 = $randomZ + mt_rand(-3,-1);
		$x2 = $randomX + mt_rand(-3,-1);
		$z2 = $randomZ + mt_rand(-3,-1);
		
		for($y = 127; $y > 0; --$y){//get highest block
			$block = $this->world->getBlock(new Vector3($randomX, $y, $randomZ));
			$blockID = $block->getID();
			if($blockID !== 0){
				if($blockID == 18 or $blockID == 78 or $blockID == 31){//Ignore Leaves, Snow Layer, Tall Grass
					continue;
				}
				break;
			}
		}
		
		$block = $this->world->getBlock(new Vector3($randomX, $y, $randomZ));
		if($block->getID() !== 2){//Spawn only on grass
			$this->spawnDayMobs();
			return;
		}
		$y++;

		$chance = mt_rand(1,3);
		if($chance == 3){//bunch animals spawn
			
			$babyChance = $this->babyChance();
       		$entityit0 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
         	    "x" => $randomX + 0.5,
         	 	"y" => $y,
          		"z"  => $randomZ + 0.5,
           	    "Health" => $this->hp[$type],
				"isBaby" => $babyChance,
       		));
        	$this->api->entity->spawnToAll($entityit0, $this->world);
			
			$babyChance = $this->babyChance();
			if($this->world->getBlock(new Vector3($x1, $y, $z1))->getID() == 0){
				$entityit1 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
					"x" => $x1 + 0.5,
					"y"  => $y,
					"z" => $z1 + 0.5,
					"Health" => $this->hp[$type],
					"isBaby" => $babyChance,
				));
				$this->api->entity->spawnToAll($entityit1, $this->world);
			}
			
			$babyChance = $this->babyChance();
			if($this->world->getBlock(new Vector3($x2, $y, $z2))->getID() == 0){
				$entityit2 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
					"x" => $x2 + 0.5,
					"y" => $y,
					"z" => $z2 + 0.5,
					"Health" => $this->hp[$type],
					"isBaby" => $babyChance,
				));
				$this->api->entity->spawnToAll($entityit2, $this->world);
			}
			
			if($this->config->get("debug")) console("Spawned ".$this->mobName[$type]."s in center ".($randomX + 0.5).", ".$y.", ".($randomZ + 0.5)." world: ".$this->worldName.".");
		}
		else{//one animal spawn
		
			$babyChance = $this->babyChance();
			$entityit0 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
         	    "x" => $randomX + 0.5,
         	 	"y" => $y,
          		"z"  => $randomZ + 0.5,
           	    "Health" => $this->hp[$type],
				"isBaby" => $babyChance,
       		));
       		$this->api->entity->spawnToAll($entityit0, $this->world);
			
			if($this->config->get("debug")) console("Spawned ".$this->mobName[$type]." in ".($randomX + 0.5).", ".$y.", ".($randomZ + 0.5)." world: ".$this->worldName.".");
		}
	}

    public function spawnNightMobs(){	
	
		$type = mt_rand(32,35);
		$randomX = mt_rand(5,250);
		$randomZ = mt_rand(5,250);
			
		for($y = 1; $y < 127; ++$y){//get lowest block
			$block = $this->world->getBlock(new Vector3($randomX, $y, $randomZ));
			if($block->getID() == 0 or ($block instanceof LiquidBlock) or ($block instanceof TransperentBlock)){//Check Air, Liquid and TransperentBlock
				break;
			}
		}
		
		$block = $this->world->getBlock(new Vector3($randomX, $y, $randomZ));
		if(($block instanceof LiquidBlock) or ($block instanceof TransperentBlock)){//Don't spawn mob in Liquid or TransperentBlocks
			$this->spawnNightMobs();
			return;
		}
		if($y == 1 and $block->getID() == 0){//Don't spawn in y=1 don't any block
			$this->spawnNightMobs();
			return;
		}
		if($this->world->getBlock(new Vector3($randomX, ++$y, $randomZ)) instanceof TransparentBlock){//Don't spawn if upper spawn block not a TransparentBlock
			$this->spawnNightMobs();
			return;
		}
			
		$entityit0 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
			"x" => $randomX + 0.5,
			"y" => $y,
			"z" => $randomZ + 0.5,
			"Health" => $this->hp[$type],
		));

		$this->api->entity->spawnToAll($entityit0, $this->world);
		if ($this->config->get("debug")) console("Spawned ".$this->mobName[$type]." in ".($randomX + 0.5).", ".$y.", ". ($randomZ + 0.5) ." world: ".$this->worldName.".");
	}
	
	public function spawnNetherMob(){
		
		$randomX = mt_rand(1,255);
		$randomZ = mt_rand(1,255);
				
		for($y = 1; $y < 127; ++$y){//get lowest block
			$block = $this->world->getBlock(new Vector3($randomX, $y, $randomZ));
			if($block->getID() == 0 or ($block instanceof LiquidBlock) or ($block instanceof TransperentBlock)){//Check Air, Liquid or TransparentBlock
				break;
			}
		}
		
		$block = $this->world->getBlock(new Vector3($randomX, $y, $randomZ));
		if(($block instanceof LiquidBlock) or ($block instanceof TransparentBlock)){//Don't spawn mob in Liquid or TransparentBlocks
			$this->spawnNetherMob();
			return;
		}
		if($y == 1 and $block->getID() == 0){//Don't spawn in y=1 don't any block
			$this->spawnNetherMob();
			return;
		}
		if($this->world->getBlock(new Vector3($randomX, ++$y, $randomZ)) instanceof TransparentBlock){//Don't spawn if upper spawn block not a TransparentBlock
			$this->spawnNetherMob();
			return;
		}
				
		$entityit0 = $this->api->entity->add($this->world, ENTITY_MOB, 36, array(
			"x" => $randomX + 0.5,
			"y" => $y,
			"z" => $randomZ + 0.5,
			"Health" => 20,
		));
		$this->api->entity->spawnToAll($entityit0, $this->world);
		
		if($this->config->get("debug")) console("Spawned Zombie Pigman in ".($randomX + 0.5).", ".$y.", ".($randomZ+ 0.5)." world: ".$this->worldName.".");
	}
	
	
	public function mobCount(){
		$cnt = 0;
		$l = $this->server->query("SELECT EID FROM entities WHERE class = ".ENTITY_MOB.";");
		if ($l !== false and $l !== true){
			while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
			$e = $this->api->entity->get($e["EID"]);
			if ($e instanceof Entity){
				$cnt++;
			}
			}
		}
		$this->ecnt = $cnt;
	}
	
    /*public function fireMoveEvent($entity)
    {
      if ($entity->speedX != 0) {
        $entity->x += $entity->speedX * 5;
      }
      if ($entity->speedY != 0) {
        $entity->y += $entity->speedY * 5;
      }
      if ($entity->speedZ != 0) {
        $entity->z += $entity->speedZ * 5;
      }
      if (($entity->last[0] != $entity->x or $entity->last[1] != $entity->y or $entity->last[2] != $entity->z or $entity->last[3] != $entity->yaw or $entity->last[4] != $entity->pitch)) {
        if ($this->api->handle("entity.move", $entity) === false) {
          $entity->setPosition($entity->last[0], $entity->last[1], $entity->last[2], $entity->last[3], $entity->last[4]);
        }
        $entity->updateLast();
      }
    } */

	public function scheduleDespawn(){
		if($this->config->get("scheduleDespawn") == true){
			$o = $this->api->player->online();
			if(($this->serverSpawnAnimals == true or $this->serverSpawnMobs == true) and count($o) > 0){
				$this->mobCount();
				$this->despawn();
				if($this->config->get("debug")) console($this->ecnt." mobs has been despawned!");
			}
		}
	}

	public function despawn(){//tClearMob code 
		$cnt = 0;
		$l = $this->server->query("SELECT EID FROM entities WHERE class = ".ENTITY_MOB.";");
		if ($l !== false and $l !== true){
			while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
			$e = $this->api->entity->get($e["EID"]);
			if ($e instanceof Entity){
				$this->api->entity->remove($e->eid);
				$cnt++;
			}
			}
		}
		return;
	}

    public function __destruct(){
    }

}

class AI{

    private $api;
    public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
		$this->api = $api;
	}

	public function init(){
	}

	public function look($pos2, $eid){
		$pos = $this->api->entity->getPosition();
		$angle = Utils::angle3D($pos2, $pos);
		$this->yaw = $angle["yaw"];
		$this->pitch = $angle["pitch"];
		$this->server->query("UPDATE entities SET pitch = ".$this->pitch.", yaw = ".$this->yaw." WHERE EID = ".$this->eid.";");
	}
	
	public function lookAt(Entity $entity, Player $target){
		$horizontal = sqrt(pow(($target->entity->x - $entity->x), 2) + pow(($target->entity->z - $entity->z) , 2));
		$vertical = $target->entity->y - ($entity->y + -0.5/*$entity->getEyeHeight()*/);
		$pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down
	
		$xDist = $target->entity->x - $entity->x;
		$zDist = $target->entity->z - $entity->z;
	
		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}
	
		return [$yaw, $pitch];
	}
	
	
	public function handleUpdate($entity){
		$players = $this->server->api->player->getAll($entity->level);
		$pk = new MoveEntityPacket_PosRot;
		$pk->eid = $entity->eid;
		$pk->x = $entity->x;
		$pk->y = $entity->y;
		$pk->z = $entity->z;
		$pk->yaw = $entity->yaw;
		$pk->pitch = $entity->pitch;
		$this->server->api->player->broadcastPacket($players, $pk);
	}
	
	public function resetMotion($entity){
		$entity->speedX = 0;
		$entity->speedY = 0;
		$entity->speedZ = 0;
	}
	
	public function AI($level = null){
		$entities = array();
		$l = $this->server->query("SELECT EID FROM entities WHERE class = ".ENTITY_MOB.";");
		if($l !== false and $l !== true){
			while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
				$e = $this->api->entity->get($e["EID"]);
				if($e instanceof Entity){
					$entities[$e->eid] = $e;
				}
			}
		}
		
		foreach($entities as $entity){
			if($entity == null) return;
			if($entity->x > 255 || $entity->y > 255 || $entity->z > 255){
                continue;
            }
			$lastPosX = $entity->last[0];
			$lastPosY = $entity->last[1];
			$lastPosZ = $entity->last[2];
			if($entity->x != $lastPosX or $entity->z != $lastPosZ){
				$entity->last[0] = $entity->x;
				$entity->last[2] = $entity->z;
				continue;
			}
			//console($entity);
			$players = $this->server->api->player->getAll($entity->level);
			//$rand_p = array_rand($players);
			//$this->look(new Position2($rand_p->x, $rand->z), $entity->pos);
			if((abs($entity->speedZ) > 0.00001 or abs($entity->speedZ) > 0.00001)){
				$friction = mt_rand(0.1, 0.2);
			}else{
				$friction = 0.2;
			}
			
			/*$y = $entity->y-1;
			if($entity->level->getBlock(new Vector3(round($entity->x), round($y), round($entity->z)))->getID() === 0){//mob fall
				console($entity->class.", ".round($entity->x).", ".round($y).", ".round($entity->z));
				$entity->move(new Vector3(round($entity->x), round($y), round($entity->z)), $entity->yaw, $entity->pitch);
				$entity->updateMovement();
				$this->handleUpdate($entity);
				$this->resetMotion($entity);
				break;
			}*/

			//$entity->speedX += mt_rand(0,2) == 1 ? $friction : 0;
			//$this->motionY *= 1 - $this->drag;
			//$entity->speedZ += mt_rand(0,2) == 1 ? $friction : 0;
			$entity->speedX += 0.0001;
			if(count($players) > 0){
			$randPlayer = $players[array_rand($players)];
			if($randPlayer == NULL){
				$randPlayerYawPitch = [$entity->yaw, $entity->pitch];
			//	$this->pmserver->close("ded");
			}else{
				$randPlayerYawPitch = $this->lookAt($entity, $randPlayer);
			}
			$entity->yaw = $randPlayerYawPitch[0];
			$entity->pitch = $randPlayerYawPitch[1];
			}
			//$x = $this->server->query("SELECT x FROM entities WHERE eid = ".$eid.";");
			//console($eid);
			//$this->setEntityMotion(new Vector3(2,2,0), $entity);
			//$entity->speedX += 0.2;
			$entity->closed = false;
			$block = $entity->level->getBlock(new Vector3(round($entity->x+$entity->speedX),round($entity->y+$entity->speedY),round($entity->z+$entity->speedZ)));
			if($block->getID() !== 0){
				$this->resetMotion($entity); 
				continue;
			}
			$entity->move(new Vector3($entity->speedX, $entity->speedY, $entity->speedZ), $entity->yaw, $entity->pitch);
			$entity->updateMovement();
			$this->handleUpdate($entity);
			$this->resetMotion($entity); 
		}
		
		//}
	}

	public function __destruct(){
    }

}