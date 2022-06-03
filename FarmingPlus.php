<?php
  /*
__PocketMine Plugin__
name=FarmingPlus
description=Useful plugin for Farming using Hoe
version=1.1
author=ArkQuark
class=FarmingPlus
apiversion=11,12
*/

class FarmingPlus implements Plugin{
	
	public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
	}
	
	public function init(){
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 100);
	}
	
	public function eventHandle($data, $event){

		switch ($event){

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				$targetID = $target->getID();
				$targetMeta = $target->getMetadata();
				$targetReflection = new ReflectionClass('Block');
				$targetReflectionMeta = $targetReflection->getProperty('meta');
				$targetReflectionMeta->setAccessible(true);
				
				$itemHeld = $player->getSlot($player->slot);
				
				$dropPos = new Position($target->x+0.5, $target->y, $target->z+0.5, $target->level);
				$pos = new Vector3($target->x, $target->y, $target->z, $target->level);
				
				if($itemHeld->isHoe() != true) break;
				if($targetMeta != 7) break;
				if($target->getSide(0)->getID() != 60) break;
				
				switch($targetID){
					case 59: //wheat
						$targetReflectionMeta->setValue($target, 0);
						$target->level->setBlock($target, $target);
						
						$item = $this->api->block->fromString("WHEAT_SEEDS");
						for($i = mt_rand(0, 3); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
						
						$item = $this->api->block->fromString("WHEAT");
						$this->api->entity->drop($dropPos, $item);
						break;
						
					case 244: //beetroot
						$targetReflectionMeta->setValue($target, 0);
						$target->level->setBlock($target, $target);
						
						$item = $this->api->block->fromString("BEETROOT_SEEDS");
						for($i = mt_rand(0, 3); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
					
						$item = $this->api->block->fromString("BEETROOT");
						$this->api->entity->drop($dropPos, $item);
						break;
						
					case 141: //carrot
						$targetReflectionMeta->setValue($target, 0);
						$target->level->setBlock($target, $target);
						
						$item = $this->api->block->fromString("CARROT");
						for($i = mt_rand(0, 2); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
						break;
						
					case 142: //potato
						$targetReflectionMeta->setValue($target, 0);
						$target->level->setBlock($target, $target);
						
						$item = $this->api->block->fromString("POTATO");
						for($i = mt_rand(0, 2); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
						break;
				}
		}
	}
	
	public function __destruct(){
    }
}

?>