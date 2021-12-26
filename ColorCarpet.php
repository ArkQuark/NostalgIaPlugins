<?php

/*
__PocketMine Plugin__
name=ColorCarpet
description=Click on carpet with dye and his now dyed
version=1.01
author=ArkQuark
class=Carpet
apiversion=11
*/

class Carpet implements Plugin{
	private $api;

public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
}

public function init(){
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 50);
	}
	
	public function getColor($itemheld){
		
		$color = $itemheld->getMetadata();
		
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
				$itemheld = $player->getSlot($player->slot);
				$color = $this->getColor($itemheld);
				$dyeColor = $itemheld->getMetadata();
			
			if(($target->getID() == 35) and ($itemheld->getID() == 351)) {
					
					if ($itemheld->getID() == 351) {
						$block = BlockAPI::get(35, $color);
				        $target->level->setBlock($pos, $block); 
						$player->removeItem(351, $dyeColor , 1);
					}
					
			}
			elseif(($target->getID() == 171) and ($itemheld->getID() == 351)) {
					
					if ($itemheld->getID() == 351) {
						$metadata = $itemheld->getMetadata();
						$block = BlockAPI::get(171, $color);
				        $target->level->setBlock($pos, $block); 
						$player->removeItem(351, $dyeColor, 1);
				    }
				}
		}
}

public function __destruct()
{
}
}
?>