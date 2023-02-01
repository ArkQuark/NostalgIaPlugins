<?php

/*
__PocketMine Plugin__
name=DropsPlugin
description=New drops from blocks
version=1.3b
author=ArkQuark
class=Quartz
apiversion=11,12,12.1
*/

class Quartz implements Plugin{
	private $api, $blockIdAccessor;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->blockIdAccessor = (new ReflectionClass("Block"))->getProperty("id");
		$this->blockIdAccessor->setAccessible(true);
	}
	
	public function init(){
		$this->api->addHandler("player.block.break", array($this, "eventHandler"), 0);
	}
	
	public function eventHandler($data, $event){
		switch ($event){
			case "player.block.break":
			
			$block = $data["target"];
			$player = $data["player"];
			$level = $block->level;
			$pos = new Position($block->x+.5, $block->y, $block->z+.5, $level);
			
			if(!($player->getGamemode() == "survival")){
				break;
			}
			if(($block->getID() == 12) or ($block->getID() == 87)){//Sand and Netherrack
				if (mt_rand(1, 20) == 1){
					$level->setBlock(new Vector3($block->x, $block->y, $block->z), new AirBlock());
					$item = $this->api->block->fromString("QUARTZ");
					$this->api->entity->drop($pos, $item);
					$this->blockIdAccessor->setValue($data["target"], 0);
					return false;
				}
			}
				
			elseif(($block->getID() == 18)){//Leaves
				if (mt_rand(1, 10) == 1){
					$item = $this->api->block->fromString("STRING");
					$this->api->entity->drop($pos, $item);
				}
			}
		}
	}
	
	public function __destruct(){
	}
}
