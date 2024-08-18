<?php

/*
__PocketMine Plugin__
name=GrassPlant
description=Replant grass using any seeds.
version=1.4.2
author=onlypuppy7
class=Grass
apiversion=11,12,12.1
*/

class Grass implements Plugin{

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}

	public function init(){
		$this->api->addHandler("player.block.touch", [$this, "eventHandle"], 1000);
	}

	public function eventHandle($data, $event){
		switch($event){
			case "player.block.touch":
				$player = $data["player"];
				$pGamemode = $player->getGamemode();
				
				if($pGamemode !== "survival" and $pGamemode !== "creative") return; //player cannot interact in spectator and adventure
				
				$target = $data["target"];
				$tile = $this->api->tile->get(new Position($target->x, $target->y, $target->z, $target->level));
				$itemheld = $player->getSlot($player->slot);
				$item = $itemheld->getID();

				if(($target->getID() === DIRT) and ($item === SEEDS or $item === PUMPKIN_SEEDS or $item == MELON_SEEDS or $item == BEETROOT_SEEDS)){
					$block = BlockAPI::get(GRASS);
					$pos = new Vector3($target->x, $target->y, $target->z);
					if($target->getSide(1)->isTransparent === false){
						break;
					}
					else{
						$target->level->setBlock($pos, $block);
						if($pGamemode == "survival"){
							$player->removeItem($item, 0, 1);
						}
					}
				}
				break;
		}
	}

	public function __destruct(){}
}
