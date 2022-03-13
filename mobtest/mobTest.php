<?php

/*
__PocketMine Plugin__
name=mobTest
description=New spawn system for mobs!
version=2.3hotfix
author=zhuowei
class=MobTest
apiversion=12
*/

  class MobTest implements Plugin{

    private $api, $config;
	public $mobRange = 128;

    public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
		$this->api = $api;
		$this->hp = array(
				//animals 
				10 => 4,
				11 => 10,
				12 => 10,
				13 => 8,
				
				//mobs
				32 => 20,
				33 => 20,
				34 => 20,
				35 => 16,
				36 => 20,
		);
		
		$this->mob = array(
			//animals
			"chicken" => 10,
			"cow" => 11,
			"pig" => 12,
			"sheep" => 13,
			
			//mobs
			"zombie" => 32,
			"creeper" => 33,
			"skeleton" => 34,
			"spider" => 35,
			"pigman" => 36,
		);
		
		$this->spawnanimals = $this->server->api->getProperty("spawn-animals");
		$this->spawnmobs = $this->server->api->getProperty("spawn-mobs");
    }

    public function init(){
		$this->api->console->register("summon", "<mob>", array($this, "commandH"));
		//$this->api->console->register("despawn", "", array($this, "commandH"));
		$this->api->console->alias("spawnmob", "summon");
	
		//$this->api->addHandler("player.block.touch", array($this, "cowMilk"), 50);
		
		$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, array(
			"//in minutes",
			"dayMobsTime" => 1,
			"nightMobsTime" => 1,
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
		
	if(($this->api->time->get() >= 0) and ($this->api->time->get() <= 9500)){
		$o = $this->api->player->online();
			
	if($this->spawnanimals == true and count($o) > 0){
		
		$rand_p = mt_rand(0, (count($o) - 1));
		$world = $this->api->player->get($o[$rand_p])->level;
		
		if(($world->getName() == "Nether") or ($world->getName() == "nether")){//Animals don't spawn in nether
			return;
		}
		else{
			
        $type = mt_rand(10, 13);
        $randomAreaX = mt_rand(5,250);
        $randomAreaZ = mt_rand(5,250);
		
		for($y = 127; $y > 0; --$y){//get upper block script
			$block = $world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
			if($block->getID() !== 0){
				if($block->getID() == 18){
					continue;
				}
				elseif($block == 78){
					continue;
				}
				elseif($block == 31){
					continue;
				}
				break;
			}
		}
		
		$block = $world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
		if($block->getID() !== 2){//Grass
			$this->spawnDayMobs();
			return;
		}
		$y++;
		
        $entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX,
            "y" => $y,
            "z"  => $randomAreaZ,
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit, $world);
        $entityit2 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX + mt_rand(1,3),
            "y"  => $y,
            "z" => $randomAreaZ - mt_rand(1,3),
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit2, $world);
        $entityit3 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
            "x" => $randomAreaX - mt_rand(1,3),
            "y" => $y,
            "z" => $randomAreaZ - mt_rand(1,3),
            "Health" => $this->hp[$type],
			"Color" => mt_rand(0,15),
        ));
		
        $this->api->entity->spawnToAll($entityit3, $world);
		if ($this->config->get("debug") == true){
			console("Spawned animals in ". $randomAreaX .", ".$y.", ". $randomAreaZ. " world: ". $world->getName(). ".");
		}
		}
    }
	}
	}


    public function spawnNightMobs(){
		$o = $this->api->player->online();
	  
    if(($this->api->time->get() >= 10000) and ($this->api->time->get() <= 18000)) {
		
		if($this->spawnmobs == true and count($o) > 0){
			
			$rand_p = mt_rand(0, (count($o) - 1));
			$world = $this->api->player->get($o[$rand_p])->level;
			
			if(($world->getName() == "Nether") or ($world->getName() == "nether")){//Zombie Pigman spawn 
				
				$type = 36;
				$randomAreaX = mt_rand(5,250);
				$randomAreaZ = mt_rand(5,250);
				
				for($y = 127; $y > 0; --$y){//get upper block script
				$block = $world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
				if($block->getID() !== 0){
					if($block->getID() == 18){
						continue;
					}
					elseif($block->getID() == 78){
						continue;
					}
					elseif($block == 31){
						continue;
					}
					break;
				}
			}
		
			$block = $world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
			if($block->getID() == 8 or $block->getID() == 9 or $block->getID() == 10 or $block->getID() == 11){//Water or lava
				$this->spawnNightMobs();
				return;
			}
			$y++;
				
				$entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX,
				"y" => $y,
				"z" => $randomAreaZ,
				"Health" => $this->hp[$type],
			));
			$this->api->entity->spawnToAll($entityit, $world);
			if ($this->config->get("debug") == true){
				console("Spawned Zombie Pigman in ". $randomAreaX .", ". $y .", ". $randomAreaZ. " world: ". $world->getName(). ".");
			}
			}
			else{
			
            $type = mt_rand(32,35);
            $randomAreaX = mt_rand(5,250);
            $randomAreaZ = mt_rand(5,250);
			
			for($y = 127; $y > 0; --$y){//get upper block script
				$block = $world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
				if($block->getID() !== 0){
					if($block->getID() == 18){
						continue;
					}
					elseif($block->getID() == 78){
						continue;
					}
					elseif($block == 31){
						continue;
					}
					break;
				}
			}
		
			$block = $world->getBlock(new Vector3($randomAreaX, $y, $randomAreaZ));
			if($block->getID() == 8 or $block->getID() == 9 or $block->getID() == 10 or $block->getID() == 11){//Water or lava
				$this->spawnNightMobs();
				return;
			}
			$y++;
			
            $entityit = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX,
				"y" => $y,
				"z" => $randomAreaZ,
				"Health" => $this->hp[$type],
			));
		  
            $this->api->entity->spawnToAll($entityit, $world);
            $entityit2 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX + mt_rand(1,3),
				"y" => $y,
				"z" => $randomAreaZ - mt_rand(1,3),
				"Health" => $this->hp[$type],
			));
		  
			$this->api->entity->spawnToAll($entityit2, $world);
			$entityit3 = $this->api->entity->add($world, ENTITY_MOB, $type, array(
				"x" => $randomAreaX - mt_rand(1,3),
				"y" => $y,
				"z"  => $randomAreaZ - mt_rand(1,3),
				"Health" => $this->hp[$type],
			));
		  
			$this->api->entity->spawnToAll($entityit3, $world);
			if ($this->config->get("debug") == true){
				console("Spawned mobs in ". $randomAreaX .", ". $y .", ". $randomAreaZ. " world: ". $world->getName(). ".");
			}
        }
		}
    }
    }
	
	public function commandH($cmd, $params, $issuer, $alias){
		$output = "";
        switch ($cmd){
			/*case 'despawn':
			$cnt = 0;
			$l = $this->server->query("SELECT EID FROM entities WHERE class = ".ENTITY_MOB.";");
			if(count($l) == 1){
				$output .= "No mobs in all worlds!";
				break;
			}
			if ($l !== false and $l !== true){
				while(($e = $l->fetchArray(SQLITE3_ASSOC)) !== false){
				$e = $this->api->entity->get($e["EID"]);
				if ($e instanceof Entity){
					$this->api->entity->remove($e->eid);
					$cnt++;
				}
				}
				$output .= (count($l)." mobs has been despawned!");
				return;
			}*/
			case 'summon':
			if(!($issuer instanceof Player)){
				$output .= "Please run this command in-game.\n";
				break;
			}
			if((count($params) == 0) or (count($params) >= 2)){
				$output .= "Usage: /$cmd <mob>.\n";
				break;
			}
			elseif(count($params) == 1){
				$type = $this->mob[strtolower($params[0])];
				$mobname = array(
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
				
				if($type != (10 or 11 or 12 or 13 or 14 or 32 or 33 or 34 or 35 or 36)){
					$output .= "Unknown mob.\n";
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
					$output .= "[Summon] ".$mobname[$type]." spawned in ".$spawnX.", ".$spawnY.", ".$spawnZ.".";
				}
			}
		}
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
		if (($this->spawnanimals == true or $this->spawnmobs == true) and count($o) > 0){
				
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
				console(count($l)." mobs has been despawned!");
			}
		}
	}
	return;
}

    public function __destruct(){
    }

}

  //Rising, 18000, midday is 4500, midnight is around 15000, decline, I think is the evening? I think that is 10000 (zhuowei)