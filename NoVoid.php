<?php

/*
 __PocketMine Plugin__
name=NoVoid
description=Just for LuckyRace
version=0.8.1
apiversion=12.1
author=ArkQuark
class=NVmain
*/

class NVmain implements Plugin{
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function init(){
		$this->api->addHandler("player.move", array($this, "handle"), 5);
	}
	
	public function handle(&$data, $event){
		if($data->y <= 1){
			$this->api->entity->harm($data->eid, PHP_INT_MAX, "void", true);
			$data->player->blocked = true;
		}
	}
	
	public function __destruct(){}
}