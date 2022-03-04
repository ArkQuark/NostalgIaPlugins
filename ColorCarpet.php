<?php

/*
__PocketMine Plugin__
name=ColorCarpet
description=Click on carpet with dye and his now dyed
version=1.2
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
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 50);
	}

	public function eventHandle($data, $event){

		switch ($event){

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				
				$pos = new Vector3($target->x,$target->y,$target->z,$target->level);
				$item = $player->getSlot($player->slot);
				$dyeColor = $item->getMetadata();
				if($dyeColor <= 15){
					$color = $this->woolColor[$dyeColor];
				}
			
			if(($target->getID() == 35) and ($item->getID() == 351) and ($target->getMetadata() <= 15) and ($dyeColor <= 15)){
				if($this->woolColor[$target->getMetadata()] !== $dyeColor){
				
					$block = BlockAPI::get(35, $color);
					$target->level->setBlock($pos, $block); 
					if($player->getGamemode() == "survival"){
						$player->removeItem(351, $dyeColor , 1);
					}
				}
			}
			elseif(($target->getID() == 171) and ($item->getID() == 351) and ($target->getMetadata() <= 15) and ($dyeColor <= 15)){
				if($this->woolColor[$target->getMetadata()] !== $dyeColor){
					$metadata = $item->getMetadata();
					$block = BlockAPI::get(171, $color);
					$target->level->setBlock($pos, $block); 
					if($player->getGamemode() == "survival"){
						$player->removeItem(351, $dyeColor, 1);
					}
				}
			}
		}
	}

	public function __destruct() {
	}
	
}
?>