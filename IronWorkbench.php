<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=NEW Crafting system by using iron block!
version=1.05
author=DartMiner43
class=Iron
apiversion=10,11,12
*/

class Iron implements Plugin{
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
	public function eventHandle($data, $event) {

		switch ($event) {

			case "player.block.touch":

				$player = $data["player"];
				$target = $data["target"];
				$tile = $this->api->tile->get(new Position($target->x, $target->y, $target->z, $target->level));
				$itemheld = $player->getSlot($player->slot);
				
				//Крафты
				if(($target->getID() == 42) and ($itemheld->getID() == 318)) {
					$player->addItem (289,0,1);  //Кремний->порох
					$player->removeItem(318,0,1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 17) and ($itemheld->getMetadata() == 3)) {
					$player->addItem (5,3,4);  //Тропическое бревно->Тропические доски
					$player->removeItem(17,3,1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 406)) {
					$player->addItem (352,0,1);  //Кварц->кость
					$player->removeItem(406,0,1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 31) and ($itemheld->getMetadata() == 0)) {
					$player->addItem (32,1,1);  //Трава->Мертвый куст
					$player->removeItem(31,0,1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 259)) {
					$player->addItem (51,0,4);  //Зажигалка->Огонь
					$player->removeItem(259,0,1);
					break;
				}
				elseif(($target->getID() == 42) and ($itemheld->getID() == 6) and ($itemheld->count >=16)) {
					$player->addItem (3,0,1);  //16 Саженцев->Земля
					$player->removeItem(6,0,16);
					break;
				}
				
		}
	}

public function __destruct()
{
}
}
?>
