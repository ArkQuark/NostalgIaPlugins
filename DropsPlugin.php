<?php

/*
__PocketMine Plugin__
name=DropsPlugin
description=New drops from blocks
version=1.4.0
author=ArkQuark
class=DPMain
apiversion=11,12,12.1
*/

class DPMain implements Plugin{
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->blockIdAccessor = (new ReflectionClass("Block"))->getProperty("id");
		$this->blockIdAccessor->setAccessible(true);
	}
	
	public function init(){
		$this->api->addHandler("player.block.break", [$this, "eventHandler"]);
	}
	
	public function eventHandler($data, $event){
		$block = $data["target"];
		$player = $data["player"];
		$pos = new Position($block->x+.5, $block->y, $block->z+.5, $block->level);
			
		if(!($player->getGamemode() === "survival")) return;

		if(($block->getID() === SAND) or ($block->getID() === NETHERRACK)){
			if(mt_rand(1, 20) == 1){
				$pos->level->setBlock(new Vector3($pos->x, $pos->y, $pos->z), new AirBlock());
				$item = $this->api->block->fromString("QUARTZ");
				$this->api->entity->drop($pos, $item);
				$this->blockIdAccessor->setValue($data["target"], 0);
				return false;
			}
		}
				
		elseif(($block->getID() === LEAVES)){
			if(mt_rand(1, 10) == 1){
				$item = $this->api->block->fromString("STRING");
				$this->api->entity->drop($pos, $item);
			}
		}
	}
	
	public function __destruct(){
	}
}
