<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=NEW Crafting system by using iron block!
version=2.1
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
		$this->api->ban->cmdWhitelist("crafts");
	}
	
	
	public function eventHandle($data, $event){

		switch ($event){
			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				$targetID = $target->getID();
				
				$itemheld = $player->getSlot($player->slot);
				$itemheldID = $itemheld->getID();
				$itemheldMeta = $itemheld->getMetadata();
				$itemheldCount = $itemheld->count;
				$itemheldReflection = new ReflectionClass('Item');
				$itemheldReflectionCount = $itemheldReflection->getProperty('count');
				
				$pos = new Position($target->x, $target->y, $target->z, $target->level);
				$dropPos = new Position($target->x+0.5, $target->y+1, $target->z+0.5, $target->level);
				
				if($player->getGamemode() != "survival") break;
				if($targetID != 42) break;
				if($itemheldCount = 0) break;
				
				if($itemheldID == 318){ //Flint -> Gunpowder
					if($itemheldCount = 1) $player->removeItem(318, 0, 1);
					else $itemheldReflectionCount->setValue($itemheld, --$itemheldCount);
					$item = $this->api->block->fromString("GUNPOWDER");
					$this->api->entity->drop($dropPos, $item);
					break;
				}
				elseif($itemheldID == 17 and $itemheldMeta == 3){ //Jungle wood -> 4 Jungle planks
					if($itemheldCount = 1) $player->removeItem(17, 3, 1);
					else $itemheldReflectionCount->setValue($itemheld, --$itemheldCount);
					$item = $this->api->block->fromString("PLANKS:3");
					for($i = 4; $i > 0; $i--) $this->api->entity->drop($dropPos, $item, 3);
					if($data['type'] === 'place') return false;
					break;
				}
				elseif($itemheldID == 406){ //Quartz -> Bone
					if($itemheldCount = 1) $player->removeItem(406, 0, 1);
					else $itemheldReflectionCount->setValue($itemheld, --$itemheldCount);
					$item = $this->api->block->fromString("BONE");
					$this->api->entity->drop($dropPos, $item);
					break;
				}
				elseif($itemheldID == 31 and ($itemheldMeta == 1 or $itemheldMeta == 2)){ //Grass -> Dead bush
					if($itemheldCount = 1) $player->removeItem(31, $itemheldMeta, 1);
					else $itemheldReflectionCount->setValue($itemheld, --$itemheldCount);
					$item = $this->api->block->fromString("DEAD_BUSH");
					$this->api->entity->drop($dropPos, $item);
					break;
				}
				elseif($itemheldID == 6 and $itemheld->count >= 8) { //8 Saplings -> Grass block
					if($itemheldCount = 1) $player->removeItem(6, $itemheldMeta, 1);
					else $itemheldReflectionCount->setValue($itemheld, --$itemheldCount);
					$item = $this->api->block->fromString("GRASS");
					$this->api->entity->drop($dropPos, $item);
					if($data['type'] === 'place') return false;
				}
				elseif($itemheldID == 263){ //Coal -> Inc sac
					if($itemheldCount = 1) $player->removeItem(263, $itemheldMeta, 1);
					else $itemheldReflectionCount->setValue($itemheld, --$itemheldCount);
					$item = $this->api->block->fromString("DYE:0");
					$this->api->entity->drop($dropPos, $item);
					break;
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
Tall Grass/Fern -> Dead bush
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