<?php

/*
__PocketMine Plugin__
name=GrassPlant
description=Replant grass using wheat seeds.
version=1.2
author=onlypuppy7
class=Grass
apiversion=11
*/

class Grass implements Plugin
{
	private $api;
	private $openGrass;

	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;
		$this->openGrass = array();
	}

	public function init()
	{
		$this->api->addHandler("player.block.touch", array($this, "eventHandle"), 50);
	}


	public function eventHandle($data, $event) {

		switch ($event) {

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				$tile = $this->api->tile->get(new Position($target->x, $target->y, $target->z, $target->level));
				$itemheld = $player->getSlot($player->slot);
				$item = $itemheld->getID();


				if(($target->getID() == 3) and (($item == 295) or ($item == 361) or ($item == 362) or ($item == 458))){
					$block = BlockAPI::get(2, 0);
					$pos = new Vector3($target->x,$target->y,$target->z);
					$target->level->setBlock($pos, $block);
					$player->removeItem($item, 0, 1);
				}
				break;
		}
	}


	public function __destruct()
	{
	}
}
