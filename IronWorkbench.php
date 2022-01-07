<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=NEW Crafting system by using iron block!
version=1.11
author=DartMiner43
class=Iron
apiversion=11
*/

class Iron implements Plugin{
	
	//Special thx to SkilasticYT
	
	private $api;
	private $openBlock;

	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
		$this->openBlock = array();
	}

	public function init()
	{
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 50);
	}
	
	/*private function getSideIron($x, $y, $z){
		$item = $this->api->level->getDefault()->getBlock(new Vector3($x + 1, $y, $z));
		if ($item->getID() === IRON_BLOCK) return $item;
		$item = $this->api->level->getDefault()->getBlock(new Vector3($x - 1, $y, $z));
		if ($item->getID() === IRON_BLOCK) return $item;
		$item = $this->api->level->getDefault()->getBlock(new Vector3($x, $y, $z + 1));
		if ($item->getID() === IRON_BLOCK) return $item;
		$item = $this->api->level->getDefault()->getBlock(new Vector3($x, $y, $z - 1));
		if ($item->getID() === IRON_BLOCK) return $item;
		return false;
	}*/
	
	public function eventHandle($data, $event) {

		switch ($event) {

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				//$block = $data["block"];
				$itemheld = $player->getSlot($player->slot);
				//$pos = new Position($target->x, $target->y, $target->z, $target->level);
				
				if(($target->getID() == 42) and ($itemheld->getID() == 318)){
					$player->addItem(289, 0, 1);  //Flint -> Gunpowder
					$player->removeItem(318, 0, 1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 17) and ($itemheld->getMetadata() == 3)){
					$player->addItem(5, 3, 4);  //Jungle wood -> 4 Jungle planks
					$player->removeItem(17, 3, 1);
					//$this->api->block->playerBlockAction($player, $pos, $this->getSideIron($block->x, $block->y, $block->z), $block->getX, $block->getY, $block->getZ);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 406)){
					$player->addItem(352, 0, 1);  //Quartz -> Bone
					$player->removeItem(406, 0, 1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 31) and ($itemheld->getMetadata() == 0)){
					$player->addItem(32, 1, 1);  //Grass -> Dead bush
					$player->removeItem(31, 0, 1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 259)){
					$player->addItem(51, 0, 4);  //Flint and steel -> 4 Fire blocks
					$player->removeItem(259, 0, 1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 6) and ($itemheld->count >= 8)) {
					$metadata = $itemheld->getMetadata();
					$player->addItem(2, 0, 1);  //8 Saplings -> Grass block
					$player->removeItem(6, $metadata, 8);
					//$this->api->block->playerBlockAction();
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 263)){
					$metadata = $itemheld->getMetadata();
					$player->addItem(351, 0, 1);  //Coal -> Ink sack
					$player->removeItem(263, $metadata, 1);
					break;
				}
		}
	}

	public function __destruct(){
		
	}
	
}
?>
