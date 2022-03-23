<?php

/*
__PocketMine Plugin__
name=mobTest
description=New spawn system for mobs!
version=3.0
author=zhuowei
class=MobTest
apiversion=10,11,12
*/

	define("MOB_RANGE", 128);

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
		$this->api->console->register("summon", "<mob>", array($this, "commandH"));
		$this->api->console->register("despawn", "", array($this, "despawnCommand"));
		$this->api->console->alias("spawnmob", "summon");
	
		//$this->api->addHandler("player.block.touch", array($this, "cowMilk"), 50);
		
		$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
			"//in seconds",
			"spawnMobs" => 30,
			"mobDespawn" => true,
			"despawnTime" => 900,
			"debug" => false,
		));
	
			//seconds*20(ticks)
			//$this->api->schedule($this->config->get("dayMobsTime")*20, array($this,"spawnDayMobs"), array(), true); 
			//$this->api->schedule($this->config->get("nightMobsTime")*20, array($this,"spawnNightMobs"), array(), true); 
			$this->api->schedule($this->config->get("despawnTime")*20, array($this, "mobDespawn"), array(), true);
			$this->api->schedule(0, array($this, "spawnMobs"), array());
	}

	public function spawnMobs(){
		$o = $this->api->player->online();

		if($this->serverSpawnAnimals == false and $this->serverSpawnMobs == false) return;
		if(count($o) > 0){
			$rand_p = mt_rand(0, (count($o) - 1));
			$world = $this->api->player->get($o[$rand_p])->level;
			$worldName = $world->getName();
			$time = $this->api->time->get();

			$this->o = $o;
			$this->rand_p = $rand_p;
			$this->world = $world;
			$this->worldName = $worldName;

			if($time >= 0 and $time <= 9500){
				if($world->getName() == "Nether" or $world->getName() == "nether"){//Don't spawn animals in nether
					$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
					return;
				}
				elseif($worldName == null){//trying fix gullcraft errors
					$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
					console("[ERROR] Cannot get a player world!");
					return;
				}
				$this->spawnDayMobs();
				$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
				return;
			}
			elseif($time >= 10000 and $time <= 18000){
				if($worldName == "Nether" or $worldName == "nether"){
					$this->spawnNetherMob();
					$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
					return;
				}
				elseif($worldName == null){//trying fix gullcraft errors
					$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
					console("[ERROR] Cannot get a player world!");
					return;
				}
				else{
					$this->spawnNightMobs();
					$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
					return;
				}
			}
			else{
				$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
				return;
			}
		}
		else{
			$this->api->schedule($this->config->get("spawnMobs")*20, array($this, "spawnMobs"), array());
			return;
		}
	}


    public function spawnDayMobs(){
			
        $type = mt_rand(10, 13);
        $randomAreaX = mt_rand(5,250);
        $randomAreaZ = mt_rand(5,250);
		
		for($y = 127; $y > 0; --$y){//get highest block
			$block = $this->world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
			$blockID = $block->getID();
			if($blockID !== 0){
				if($blockID == 18 or $blockID == 78 or $blockID == 31){//Ignore Leaves, Snow Layer, Tall Grass
					continue;
				}
				break;
			}
		}
		
		$block = $this->world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
		if($block->getID() !== 2){//if not Grass don't spawn
			$this->spawnDayMobs();
			return;
		}
		$y++;
		
        $entityit = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX + 0.5,
            "y" => $y,
            "z"  => $randomAreaZ + 0.5,
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit, $this->world);
        $entityit2 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX + mt_rand(1,3) + 0.5,
            "y"  => $y,
            "z" => $randomAreaZ - mt_rand(1,3) + 0.5,
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit2, $this->world);
        $entityit3 = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX - mt_rand(1,3) + 0.5,
            "y" => $y,
            "z" => $randomAreaZ - mt_rand(1,3) + 0.5,
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit3, $this->world);
		if ($this->config->get("debug") == true){
			console("Spawned animals in ". ($randomAreaX + 0.5) .", ".$y.", ". ($randomAreaZ + 0.5). " world: ". $this->worldName. ".");
		}
	}


    public function spawnNightMobs(){	

		$type = mt_rand(32,35);
		$randomAreaX = mt_rand(5,250);
		$randomAreaZ = mt_rand(5,250);
			
		for($y = 1; $y < 127; ++$y){//get lowest block
			$block = $this->world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
			if($block->getID() == 0 or ($block instanceof LiquidBlock)){//Check Air and Liquid
				break;
			}
		}
		
		$block = $this->world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
		if($block instanceof LiquidBlock){//Don't spawn mob in Liquid
			$this->spawnNightMobs();
			return;
		}
			
		$entityit = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
			"x" => $randomAreaX + 0.5,
			"y" => $y,
			"z" => $randomAreaZ + 0.5,
			"Health" => $this->hp[$type],
		));

		$this->api->entity->spawnToAll($entityit, $this->world);
		if ($this->config->get("debug") == true){
			console("Spawned mobs in ". ($randomAreaX + 0.5) .", ". $y .", ". ($randomAreaZ + 0.5) ." world: ". $this->worldName. ".");
		}
	}
	
	
	public function spawnNetherMob(){
				
		$type = 36;
		$randomAreaX = mt_rand(5,250);
		$randomAreaZ = mt_rand(5,250);
				
		for($y = 1; $y < 127; ++$y){//get lowest block
			$block = $this->world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
			if($block->getID() == 0 or ($block instanceof LiquidBlock)){//get lowest air or Liquid
				break;
			}
		}
		
		$block = $this->world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
		if($block instanceof LiquidBlock){//Don't spawn mob in Liquid
			$this->spawnNetherMob();
			return;
		}
				
		$entityit = $this->api->entity->add($this->world, ENTITY_MOB, $type, array(
			"x" => $randomAreaX + 0.5,
			"y" => $y,
			"z" => $randomAreaZ + 0.5,
			"Health" => $this->hp[$type],
		));
		$this->api->entity->spawnToAll($entityit, $this->world);
		if ($this->config->get("debug") == true){
			console("Spawned Zombie Pigman in ". ($randomAreaX + 0.5) .", ". $y .", ". ($randomAreaZ+ 0.5) ." world: ". $this->worldName. ".");
		}
	}
	
	
	public function commandH($cmd, $params, $issuer, $alias){
		$output = "";
        switch ($cmd){
			case 'summon':
			if(!($issuer instanceof Player)){
				$output .= "Please run this command in-game.";
				break;
			}
			if((count($params) == 0) or (count($params) >= 2)){
				$output .= "Usage: /$cmd <mob>.";
				break;
			}
			elseif(count($params) == 1){
				$type = $this->mob[strtolower($params[0])];
				
				if($type != (10 or 11 or 12 or 13 or 14 or 32 or 33 or 34 or 35 or 36)){
					$output .= "Unknown mob.";
				}
				else{
					$x = $issuer->entity->x;
					$spawnX = round($x, 1, PHP_ROUND_HALF_UP);
					$y = $issuer->entity->y;
					$spawnY = round($y, 1, PHP_ROUND_HALF_UP);
					$z = $issuer->entity->z;
					$spawnZ = round($z, 1, PHP_ROUND_HALF_UP);
				
					$spawnLevel = $issuer->entity->level;
					$entityit = $this->api->entity->add($spawnLevel, ENTITY_MOB, $type, array(
						"x" => $spawnX,
						"y" => $spawnY,
						"z" => $spawnZ,
						"Health" => $this->hp[$type],
					));
					$this->api->entity->spawnToAll($entityit, $level);
					$output .= "[Summon] ".$this->mobName[$type]." spawned in ".$spawnX.", ".$spawnY.", ".$spawnZ.".";
				}
			}
		}
		return $output;
	}
	
	
	public function despawnCommand($cmd, $params, $issuer, $alias){
		$output = "";
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
		$output .= "[Despawn] Mobs has been despawned!";
		return $output;
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
	
	/*public function chickenEggEvent(){
	}
	
	
	public function cowMilk($data, $event){
		switch($event){
			case "player.block.touch":
			
			$player = $data["player"];
			if($data["target"]->getID() >= 0) break;
			$targetMob = $data['targetentity'];
			$itemHeld = $player->getSlot($player->slot);
			
			if($targetMob->type == 11 and $itemHeld->getID == 325){
				$player->removeItem(325, 1, 1);
				break;
			}
		}
	}*/


	public function mobDespawn(){//tClearMob code 
	if ($this->config->get("mobDespawn") == true){
		$o = $this->api->player->online();
		if (($this->serverSpawnAnimals == true or $this->serverSpawnMobs == true) and count($o) > 0){	
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
				console("Mobs has been despawned!");
			}
		}
	}
	return;
}


    public function __destruct(){
    }

}