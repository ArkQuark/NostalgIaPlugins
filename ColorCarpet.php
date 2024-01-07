<?php

/*
__PocketMine Plugin__
name=ColorCarpet
description=Coloring wool and carpet with dye
version=1.4.1
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
		$this->api->addHandler("player.block.touch", [$this, "eventHandle"], 1000);
	}

	public function eventHandle($data, $event){
		$player = $data["player"];
		$playerGamemode = $player->getGamemode();
		
		$target = $data["target"];
		$targetID = $target->getID();
		$targetMeta = $target->getMetadata();
		if(($targetID !== WOOL) && ($targetID !== CARPET)) return;
		
		$pos = new Vector3($target->x, $target->y, $target->z, $target->level);
		
		$itemHeld = $player->getSlot($player->slot);
		$itemHeldID = $itemHeld->getID();
		$itemHeldMeta = $itemHeld->getMetadata();
		
		if($itemHeldID == 351){//dye
			if(($targetMeta ^ 0xf) !== $itemHeldMeta){
				$block = BlockAPI::get($targetID, $itemHeldMeta ^ 0xf);
				$target->level->setBlock($pos, $block, true, false, true);
				if($playerGamemode === "survival"){
					$player->removeItem($itemHeldID, $itemHeldMeta, 1, true);
				}
			}
		}
		elseif($itemHeldID === COAL){
			if($targetMeta !== 15){
				$block = BlockAPI::get($targetID, 15);
				$target->level->setBlock($pos, $block, true, false, true);
				if($playerGamemode === "survival"){
					$player->removeItem($itemHeldID, $itemHeldMeta, 1, true);
				}
			}
		}
	}

	public function __destruct() {}
}