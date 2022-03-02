<?php

/*
__PocketMine Plugin__
name=mobTest
description=New spawn system for mobs!
version=2.1
author=zhuowei
class=MobTest
apiversion=12
*/

	/*
	Not Small Changelog
	===============
	
	1.0: Initial release
	1.1: NPCs now chase you
	1.2: NPCs now save, updated for API 9, added allstatic configuration parameter to emulate 1.0 behaviour
	1.2.1: Killing an NPC no longer crashes the server
	1.3: NPCs was removed from plugin (New name it's mobTest (original by onlypuppy7))
	1.4: Now mob spawns with original hp and with radius 3 block around center mob, API 12 (ArkQuark's updates)
	1.5: Fixed sheep color
	1.6: Added config file
	1.7: If server has 0 player this plugins don't spawn mobs
	1.7.1: Fixed spawn time for mobs
	
	2.0: (World Update!)Added Zombie Pigman and mobs now spawn in world where located player(work if server has >1 players)
	2.1: (ClearMob Update)Mobs will disappear every 15 minutes
	
	*/

  class MobTest implements Plugin{

    private $api, $config;

    public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
		$this->api = $api;
      //$this->npclist = array();
    }

    public function init(){
		
	$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
		"//in minutes",
		"dayMobsTime" => 3,
		"nightMobsTime" => 3,
		"clearMob" => true,
		"clearTime" => 15,
	));
	
    $this->api->schedule($this->config->get("dayMobsTime")*60*20, array($this,"spawnDayMobs"), array(), true); //change to set mob spawn delay in ticks (minutes*seconds*20)
    $this->api->schedule($this->config->get("nightMobsTime")*60*20, array($this,"spawnNightMobs"), array(), true); 
	$this->api->schedule($this->config->get("clearTime")*60*20, array($this, "mobClear"), array(), true);
	 
    }

    public function spawnDayMobs(){
		
		if(($this->api->time->get() >= 0) and ($this->api->time->get() <= 9500)) {
			$o = $this->api->player->online();
			
		//$npcplayer = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
		//$npcplayer->spawned = true;
	if(count($o) > 0){
		
		$rand_p = mt_rand(0, (count($o) - 1));
		$world = $this->api->player->get($o[$rand_p])->level;
		
		if(($world->getName() == "Nether") or ($world->getName() == "nether")){
			return;
		}
		else{
			
		//console("not in nether");
        $type = mt_rand(10, 13);
		$hp = array(
				10 => 4,
				11 => 10,
				12 => 10,
				13 => 8,
		);
        $randomAreaX = mt_rand(5,250);
        $randomAreaZ = mt_rand(5,250);
		
        $entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX,
            "y" => 80,
            "z"  => $randomAreaZ,
            "Health" => $hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit, $world);
        $entityit2 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX + mt_rand(1,3),
            "y"  => 80,
            "z" => $randomAreaZ - mt_rand(1,3),
            "Health" => $hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit2, $world);
        $entityit3 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX - mt_rand(1,3),
            "y" => 80,
            "z" => $randomAreaZ - mt_rand(1,3),
            "Health" => $hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit3, $world);
		//console("spawned ". $randomAreaX .", 80, ". $randomAreaZ);
	}
    }
	}
	}


    public function spawnNightMobs(){
		
        //$npcplayer = new Player("0", "127.0.0.1", 0, 0); //all NPC related packets are fired at localhost
        //$npcplayer->spawned = true;
        //console("nightcheck...");
	  
    if(($this->api->time->get() >= 10000) and ($this->api->time->get() <= 18000)) {
		$o = $this->api->player->online();
		
		if(count($o) > 0){
			
			$rand_p = mt_rand(0, (count($o) - 1));
			$world = $this->api->player->get($o[$rand_p])->level;
			
			if(($world->getName() == "Nether") or ($world->getName() == "nether")){
				
				$type = 36;
				$randomAreaX = mt_rand(5,250);
				$randomAreaZ = mt_rand(5,250);
				$entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX,
				"y" => 80,
				"z" => $randomAreaZ,
				"Health" => 20,
			));
			$this->api->entity->spawnToAll($entityit, $world);
			//console("spawned ". $randomAreaX .", 80, ". $randomAreaZ);
			}
			else{
			
			//console("not in nether");
            $type = mt_rand(32,35);
			$hp = array(
				32 => 20,
				33 => 20,
				34 => 20,
				35 => 16,
				36 => 20,
			);
            $randomAreaX = mt_rand(5,250);
            $randomAreaZ = mt_rand(5,250);
			
            $entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX,
				"y" => 80,
				"z" => $randomAreaZ,
				"Health" => $hp[$type],
			));
		  
            $this->api->entity->spawnToAll($entityit, $world);
            $entityit2 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX + mt_rand(1,3),
				"y" => 80,
				"z" => $randomAreaZ - mt_rand(1,3),
				"Health" => $hp[$type],
			));
		  
			$this->api->entity->spawnToAll($entityit2, $world);
			$entityit3 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX - mt_rand(1,3),
				"y" => 80,
				"z"  => $randomAreaZ - mt_rand(1,3),
				"Health" => $hp[$type],
			));
		  
			$this->api->entity->spawnToAll($entityit3, $world);
			//console("spawned ". $randomAreaX .", 80, ". $randomAreaZ);
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

public function mobClear(){
	if($this->config->get("dayMobsTime") == true){
		if((count($this->api->player->online())) > 0 ){
			$cnt = 0;
			$l = $this->server->query("SELECT EID FROM entities WHERE class = ".ENTITY_MOB.";");
			if($l !== false and $l !== true){
				while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
				$e = $this->api->entity->get($e["EID"]);
				if($e instanceof Entity){
					$this->api->entity->remove($e->eid);
					$cnt++;
				}
				}
			}
		}
	}
	//console("Mob cleared");
	return;
}
    

    public function __destruct(){
    }

}

  //Rising, 18000, midday is 4500, midnight is around 15000, decline, I think is the evening? I think that is 10000 (zhuowei)