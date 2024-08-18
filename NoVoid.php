<?php

/*
 __PocketMine Plugin__
name=NoVoid
description=Plugin for instakill in void
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
		$this->api->addHandler("player.move", [$this, "handle"], 1000);
	}
	
	public function handle(&$data, $event){
		if($data->y <= 0){
			$this->api->entity->harm($data->eid, PHP_INT_MAX, "void", true);
			$data->player->blocked = true;
		}
	}
	
	public function __destruct(){}
}