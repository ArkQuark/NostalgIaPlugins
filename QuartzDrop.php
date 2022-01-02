<?php

/*
__PocketMine Plugin__
name=QuartzDrop
description=Quartz now drops from sand and netherrack.
version=1.01
author=ArkQuark
class=Quartz
apiversion=11
*/

class Quartz implements Plugin{
	private $api;
	
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		$this->api->addHandler("player.block.break", array($this, "eventHandler"), 100);
	}
	
	public function eventHandler($data, $event){
		switch ($event){

			case "player.block.break":
			
			$random = mt_rand(1, 20);
			$block = $data["target"];
			$player = $data["player"];
			$level = $block->level;
			$item = $this->api->block->fromString("QUARTZ");
			$pos = new Position($block->x+.5, $block->y, $block->z+.5, $level);
			
			if($block->getID() == (12 or 87)){
				if ($random == 1){
					$this->api->entity->drop($pos, $item);
				}
				
			}
			
		}
	}
	
	public function __destruct(){
	}
}