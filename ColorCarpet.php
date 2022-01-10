<?php

/*
__PocketMine Plugin__
name=ColorCarpet
description=Click on carpet with dye and his now dyed
version=1.03
author=ArkQuark
class=Carpet
apiversion=11,12
*/

class Carpet implements Plugin{
	private $api;

	//Special thx to SkilasticYT

public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
}

public function init(){
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 50);
	}
	
	public function getColor($item){
		
		$color = $item->getMetadata();
		
				switch($color){
					case 0:
				return 15;
					case 1:
				return 14;
					case 2:
				return 13;
					case 3:
				return 12;
					case 4:
				return 11;	
					case 5:
				return 10;
					case 6:
				return 9;
					case 7:
				return 8;
					case 8:
				return 7;
					case 9:
				return 6;
					case 10:
				return 5;
					case 11:
				return 4;
					case 12:
				return 3;
					case 13:
				return 2;
					case 14:
				return 1;
					default:
				return 0;
				}
	}	

	public function eventHandle($data, $event){

		switch ($event){

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				$pos = new Vector3($target->x,$target->y,$target->z);
				$item = $player->getSlot($player->slot);
				$color = $this->getColor($item);
				$dyeColor = $item->getMetadata();
			
			if(($target->getID() == 35) and ($item->getID() == 351)) {
				
				$block = BlockAPI::get(35, $color);
				$target->level->setBlock($pos, $block); 
				$player->removeItem(351, $dyeColor , 1);
				
			}
			elseif(($target->getID() == 171) and ($item->getID() == 351)) {
					
				$metadata = $item->getMetadata();
				$block = BlockAPI::get(171, $color);
				$target->level->setBlock($pos, $block); 
				$player->removeItem(351, $dyeColor, 1);

			}
		}
	}

	public function __destruct(){
	}
	
}
?>