<?php

/*
__PocketMine Plugin__
name=mobTest
description=New spawn system for mobs!
version=2.1.1
author=zhuowei
class=MobTest
apiversion=12
*/

  class MobTest implements Plugin{

    private $api, $config;

    public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
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
		
		$this->spawnanimals = $this->server->api->getProperty("spawn-animals");
		$this->spawnmobs = $this->server->api->getProperty("spawn-mobs");
      //$this->npclist = array();
    }

    public function init(){
		
	$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
		"//in minutes",
		"dayMobsTime" => 3,
		"nightMobsTime" => 3,
		"mobDespawn" => true,
		"despawnTime" => 15,
		"debug" => false,
	));
	
	//(minutes*seconds*20(ticks))
    $this->api->schedule($this->config->get("dayMobsTime")*60*20, array($this,"spawnDayMobs"), array(), true); 
    $this->api->schedule($this->config->get("nightMobsTime")*60*20, array($this,"spawnNightMobs"), array(), true); 
	$this->api->schedule($this->config->get("despawnTime")*60*20, array($this, "mobDespawn"), array(), true);
	
	 
    }

    public function spawnDayMobs(){
		
		if(($this->api->time->get() >= 0) and ($this->api->time->get() <= 9500)) {
			$o = $this->api->player->online();
			
		//$npcplayer = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
		//$npcplayer->spawned = true;
	if((count($o) > 0) and ($this->spawn-animals == true)){
		
		$rand_p = mt_rand(0, (count($o) - 1));
		$world = $this->api->player->get($o[$rand_p])->level;
		
		if(($world->getName() == "Nether") or ($world->getName() == "nether")){//Animals dont spawn in nether
			return;
		}
		else{
			
        $type = mt_rand(10, 13);
        $randomAreaX = mt_rand(5,250);
        $randomAreaZ = mt_rand(5,250);
		
        $entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX,
            "y" => 80,
            "z"  => $randomAreaZ,
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit, $world);
        $entityit2 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX + mt_rand(1,3),
            "y"  => 80,
            "z" => $randomAreaZ - mt_rand(1,3),
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit2, $world);
        $entityit3 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX - mt_rand(1,3),
            "y" => 80,
            "z" => $randomAreaZ - mt_rand(1,3),
            "Health" => $thos->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit3, $world);
		if ($this->config->get("debug") == true){
			console("Spawned animals in ". $randomAreaX .", 80, ". $randomAreaZ. " world: ". $world->getName());
		}
	}
    }
	}
	}


    public function spawnNightMobs(){
		$o = $this->api->player->online();
		
        //$npcplayer = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
        //$npcplayer->spawned = true;
        //console("nightcheck...");
	  
    if(($this->api->time->get() >= 10000) and ($this->api->time->get() <= 18000)) {
		
		if((count($o) > 0) and ($this->spawnmobs == true)){
			
			$rand_p = mt_rand(0, (count($o) - 1));
			$world = $this->api->player->get($o[$rand_p])->level;
			
			if(($world->getName() == "Nether") or ($world->getName() == "nether")){//Zombie Pigman spawn 
				
				$type = 36;
				$randomAreaX = mt_rand(5,250);
				$randomAreaZ = mt_rand(5,250);
				
				$entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX,
				"y" => 80,
				"z" => $randomAreaZ,
				"Health" => $this->hp[$type],
			));
			$this->api->entity->spawnToAll($entityit, $world);
			if ($this->config->get("debug") == true){
				console("Spawned Zombie Pigman in ". $randomAreaX .", 80, ". $randomAreaZ. " world: ". $world->getName());
			}
			
			}
			else{
			
            $type = mt_rand(32,35);
            $randomAreaX = mt_rand(5,250);
            $randomAreaZ = mt_rand(5,250);
			
            $entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX,
				"y" => 80,
				"z" => $randomAreaZ,
				"Health" => $this->hp[$type],
			));
		  
            $this->api->entity->spawnToAll($entityit, $world);
            $entityit2 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX + mt_rand(1,3),
				"y" => 80,
				"z" => $randomAreaZ - mt_rand(1,3),
				"Health" => $this->hp[$type],
			));
		  
			$this->api->entity->spawnToAll($entityit2, $world);
			$entityit3 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX - mt_rand(1,3),
				"y" => 80,
				"z"  => $randomAreaZ - mt_rand(1,3),
				"Health" => $this->hp[$type],
			));
		  
			$this->api->entity->spawnToAll($entityit3, $world);
			if ($this->config->get("debug") == true){
				console("Spawned mobs in ". $randomAreaX .", 80, ". $randomAreaZ. " world: ". $world->getName());
			}
        }
		}
    }
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

	public function mobDespawn(){//tClearMob code 
	$o = $this->api->player->online();
	
	if (($this->config->get("mobDespawn") == true) and (count($o) > 0)) {
		if (($this->spawnanimals == true) or ($this->spawnmobs == true)){
				
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
			if ($this->config->get("debug") == true){
				console("Mob cleared");
			}	
		}
	}
	return;
}

    public function __destruct(){
    }

}

  //Rising, 18000, midday is 4500, midnight is around 15000, decline, I think is the evening? I think that is 10000 (zhuowei)