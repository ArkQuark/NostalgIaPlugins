<?php

/*
__PocketMine Plugin__
name=ColorCarpet
description=Click on carpet with dye and his now dyed
version=1.3.1
author=ArkQuark
class=Carpet
apiversion=11,12
*/

class Carpet implements Plugin{
	private $api;

	//Special thx to SkilasticYT

public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->woolColor = array(
		0 => 15,
		1 => 14,
		2 => 13,
		3 => 12,
		4 => 11,
		5 => 10,
		6 => 9,
		7 => 8,
		8 => 7,
		9 => 6,
		10 => 5,
		11 => 4,
		12 => 3,
		13 => 2,
		14 => 1,
		15 => 0,
		);
}

	public function init(){
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 667);
	}

	public function eventHandle($data, $event){
		switch ($event){
			case "player.block.touch":

				$player = $data["player"];
				$playerGamemode = $player->getGamemode();
				
				$target = $data["target"];
				$targetID = $target->getID();
				$targetMeta = $target->getMetadata();
				
				if(($targetID == 35) or ($targetID == 171)) $color = 0; //why
				else break;
				
				$pos = new Vector3($target->x, $target->y, $target->z, $target->level);
				
				$itemHeld = $player->getSlot($player->slot);
				$itemHeldID = $itemHeld->getID();
				$itemHeldCount = $itemHeld->count;
				$itemHeldReflection = new ReflectionClass('Item');
				$itemHeldReflectionCount = $itemHeldReflection->getProperty('count');
				
				$itemHeldMeta = $itemHeld->getMetadata();
				if($itemHeldMeta < 16) $color = $this->woolColor[$itemHeldMeta];
				else $color = 0;
				
				if($targetMeta > 15) break;
				
				if($itemHeldID == 351){//dye
					if($this->woolColor[$targetMeta] != $itemHeldMeta){
						$block = BlockAPI::get($targetID, $color);
						$target->level->setBlock($pos, $block);
						if($playerGamemode == "survival"){
							if($itemHeldCount = 1) $player->removeItem($itemHeldID, $itemHeldMeta , 1);
							else $itemHeldReflectionCount->setValue($itemHeld, --$itemHeldCount);
						}
					}
					break;
				}
				elseif($itemHeldID == 263){//coal
					if($targetMeta != 15){
						$block = BlockAPI::get($targetID, 15);
						$target->level->setBlock($pos, $block);
						if($playerGamemode == "survival"){
							if($itemHeldCount = 1) $player->removeItem($itemHeldID, $itemHeldMeta , 1);
							else $itemHeldReflectionCount->setValue($itemHeld, --$itemHeldCount);
						}
					}
					break;
				}
				
		}
	}

	public function __destruct() {
	}
	
}
?>