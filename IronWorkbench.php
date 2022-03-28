<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=NEW Crafting system by using iron block!
version=1.4
author=DartMiner43
class=Iron
apiversion=11,12
*/

class Iron implements Plugin{
	
	//Special thx to SkilasticYT
	
	private $api;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->server = ServerAPI::request();
	}

	public function init(){
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 50);
		$this->api->console->register("crafts", "", array($this, "command"));
	}
	
	
	public function eventHandle($data, $event){

		switch ($event){

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				$block = $target;
				$itemheld = $player->getSlot($player->slot);
				$metadata = $itemheld->getMetadata(); 
				$pos = new Position($target->x, $target->y, $target->z, $target->level);
				$dropPos = new Position($target->x+0.5, $target->y+1, $target->z+0.5, $target->level);
				
				if($player->getGamemode() == "survival"){
					if(($target->getID() == 42) and ($itemheld->getID() == 318)){ //Flint -> Gunpowder
						$player->removeItem(318, 0, 1);
						$item = $this->api->block->fromString("GUNPOWDER");
						$this->api->entity->drop($dropPos, $item);
						break;
					}
					elseif(($target->getID() == 42) and ($itemheld->getID() == 17) and ($itemheld->getMetadata() == 3)){ //Jungle wood -> 4 Jungle planks
						$player->removeItem(17, 3, 1);
						$item = $this->api->block->fromString("PLANKS:3");
						for($i = 4; $i > 0; $i--){
							$this->api->entity->drop($dropPos, $item, 3);
						}
						break;
					}
					elseif(($target->getID() == 42) and ($itemheld->getID() == 406)){ //Quartz -> Bone
						$player->removeItem(406, 0, 1);
						$item = $this->api->block->fromString("BONE");
						$this->api->entity->drop($dropPos, $item);
						break;
					}	
					elseif(($target->getID() == 42) and ($itemheld->getID() == 31) and ($itemheld->getMetadata() == 1)){ //Grass -> Dead bush
						$player->removeItem(31, 1, 1);
						$item = $this->api->block->fromString("DEAD_BUSH");
						$this->api->entity->drop($dropPos, $item);
						break;
					}
					elseif(($target->getID() == 42) and ($itemheld->getID() == 6) and ($itemheld->count >= 8)) { //8 Saplings -> Grass block
						$player->removeItem(6, $metadata, 8);
						$item = $this->api->block->fromString("GRASS");
						$this->api->entity->drop($dropPos, $item);
						break;
					}
					elseif(($target->getID() == 42) and ($itemheld->getID() == 263)){ //Coal -> Inc sac
						$player->removeItem(263, $metadata, 1);
						$item = $this->api->block->fromString("DYE");
						$this->api->entity->drop($dropPos, $item);
						break;
					}
				}
		}
	}

	public function command($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case 'crafts';
			$output .= "Crafts with IronWorkbench:
Flint -> Gunpowder
Jungle Wood -> 4 Jungle planks
Quartz -> Bone
Tall Grass -> Dead bush
8 Saplings -> Grass block
Coal -> Inc sac
			";
		}
		return $output;
	}

	public function __destruct(){	
	}
	
}
?>