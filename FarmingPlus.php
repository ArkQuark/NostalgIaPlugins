<?php
  /*
__PocketMine Plugin__
name=FarmingPlus
description=Useful plugin for Farming using Hoe
version=1.0
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
				$itemHeld = $player->getSlot($player->slot);
				$dropPos = new Position($target->x+0.5, $target->y, $target->z+0.5, $target->level);
				$pos = new Vector3($target->x, $target->y, $target->z, $target->level);

				if($target->getID() == 59 and $target->getMetadata() == 7 and $itemHeld->isHoe() == true){//Wheat
					$block = BlockAPI::get(59, 0);
					if($target->getSide(0)->getID() !== 60) break;
					else{
						$target->level->setBlock($pos, $block);
						$item = $this->api->block->fromString("WHEAT_SEEDS");
						
						for($i = mt_rand(0, 3); $i > 0; $i--){
							$this->api->entity->drop($dropPos, $item);
						}
						$this->api->entity->drop($dropPos, $this->api->block->fromString("WHEAT"));
					}
					break;
				}
				
				elseif($target->getID() == 244 and $target->getMetadata() == 7 and $itemHeld->isHoe() == true){//Beetroot
					$block = BlockAPI::get(244, 0);
					if($target->getSide(0)->getID() !== 60) break;
					else{
						$target->level->setBlock($pos, $block);
						$item = $this->api->block->fromString("BEETROOT_SEEDS");
						
						for($i = mt_rand(0, 3); $i > 0; $i--){
							$this->api->entity->drop($dropPos, $item);
						}
						$this->api->entity->drop($dropPos, $this->api->block->fromString("BEETROOT"));
					}
					break;
				}
				
				elseif($target->getID() == 141 and $target->getMetadata() == 7 and $itemHeld->isHoe() == true){//Carrot
					$block = BlockAPI::get(141, 0);
					if($target->getSide(0)->getID() !== 60) break;
					else{
						$target->level->setBlock($pos, $block);
						$item = $this->api->block->fromString("CARROT");
						
						for($i = mt_rand(0, 2); $i > 0; $i--){
							$this->api->entity->drop($dropPos, $item);
						}
					}
					break;
				}
				
				elseif($target->getID() == 142 and $target->getMetadata() == 7 and $itemHeld->isHoe() == true){//Potato
					$block = BlockAPI::get(142, 0);
					if($target->getSide(0)->getID() !== 60) break;
					else{
						$target->level->setBlock($pos, $block);
						$item = $this->api->block->fromString("POTATO");
						
						for($i = mt_rand(0, 2); $i > 0; $i--){
							$this->api->entity->drop($dropPos, $item);
						}
					}
					break;
				}
		}
	}
	
	public function __destruct(){
    }
}

?>