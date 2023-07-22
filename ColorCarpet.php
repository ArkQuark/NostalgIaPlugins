<?php

/*
__PocketMine Plugin__
name=ColorCarpet
description=Coloring wool and carpet with dye
version=1.4.0
author=ArkQuark
class=Carpet
apiversion=11,12,12.1
*/

class Carpet implements Plugin{
	//Special thx to SkilasticYT

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}

	public function init(){
		$this->api->addHandler("player.block.touch", [$this, "eventHandle"]);
	}

	public function eventHandle($data, $event){
		$player = $data["player"];
		$playerGamemode = $player->getGamemode();
		
		$target = $data["target"];
		$targetID = $target->getID();
		$targetMeta = $target->getMetadata();
		
		if(($targetID !== WOOL) or ($targetID !== CARPET)) return;
		
		$pos = new Vector3($target->x, $target->y, $target->z, $target->level);
		
		$itemHeld = $player->getSlot($player->slot);
		$itemHeldID = $itemHeld->getID();
		
		if($itemHeldID == 351){//dye
			if(($targetMeta ^ 0xf) !== $itemHeldMeta){
				$block = BlockAPI::get($targetID, $targetMeta ^ 0xf);
				$target->level->setBlock($pos, $block);
				if($playerGamemode === "survival"){
					$player->removeItem($itemHeldID, $itemHeldMeta, 1, false);
				}
			}
		}
		elseif($itemHeldID === COAL){
			if($targetMeta !== 15){
				$block = BlockAPI::get($targetID, 15);
				$target->level->setBlock($pos, $block);
				if($playerGamemode === "survival"){
					$player->removeItem($itemHeldID, $itemHeldMeta, 1, false);
				}
			}
		}
	}

	public function __destruct() {}
}