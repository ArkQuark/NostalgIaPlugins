<?php
  /*
__PocketMine Plugin__
name=FarmingPlus
description=Useful plugin for Farming using Hoe
version=1.2
author=ArkQuark
class=FarmingPlus
apiversion=12,12.1
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
				
				$itemHeld = $player->getSlot($player->slot);
				
				$dropPos = new Position($target->x+0.5, $target->y, $target->z+0.5, $target->level);
				$pos = new Vector3($target->x, $target->y, $target->z, $target->level);
				
				if($itemHeld->isHoe() === false or $targetMeta != 7 or $target->getSide(0)->getID() != 60) break;
				
				switch($targetID){
					case 59: //wheat
						$target->level->setBlock(new Vector3($target->x, $target->y, $target->z), new WheatBlock, true, false, true);
						
						$item = $this->api->block->fromString("WHEAT_SEEDS");
						for($i = mt_rand(0, 3); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
						
						$item = $this->api->block->fromString("WHEAT");
						$this->api->entity->drop($dropPos, $item);
						break;
					case 244: //beetroot
						$target->level->setBlock(new Vector3($target->x, $target->y, $target->z), new BeetrootBlock, true, false, true);
						
						$item = $this->api->block->fromString("BEETROOT_SEEDS");
						for($i = mt_rand(0, 3); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
					
						$item = $this->api->block->fromString("BEETROOT");
						$this->api->entity->drop($dropPos, $item);
						break;
					case 141: //carrot
						$target->level->setBlock(new Vector3($target->x, $target->y, $target->z), new CarrotBlock, true, false, true);
						
						$item = $this->api->block->fromString("CARROT");
						for($i = mt_rand(0, 2); $i > 0; $i--) $this->api->entity->drop($dropPos, $item);
						break;
					case 142: //potato
						$target->level->setBlock(new Vector3($target->x, $target->y, $target->z), new PotatoBlock, true, false, true);
						
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