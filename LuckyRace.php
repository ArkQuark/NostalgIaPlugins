<?php

/*
__PocketMine Plugin__
name=LuckyRace
description=Exclusive :shushing_face:, only with 2 players works
version=0.8.1
author=ArkQuark 
class=LuckyRace
apiversion=11,12,12.1
*/


class LuckyRace implements Plugin{

    public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->status = "play";
		$this->prefix = "[LuckyRace] ";
    }

    public function init(){
		$this->api->addHandler("entity.health.change", [$this, "eventHandler"]);
		$this->api->addHandler("player.block.touch", [$this, "eventHandler"]);
		$this->api->addHandler("player.death", [$this, "eventHandler"]);
    }

    public function __destruct(){}


    public function eventHandler(&$data, $event){
		switch ($event){
			case "entity.health.change":
				if($this->status == "invincible"){
					return false;
				}
				break;
			case "player.block.touch":
				if($data['target']->getID() != WALL_SIGN) break;
				$tile = $this->api->tile->get(new Position($data['target']->x, $data['target']->y, $data['target']->z, $data['target']->level));
				if($tile === false) break;
				$data = $tile->getText();
				if($data[0] === "tp @a Arena"){
					$this->status = "invincible";
					$players = $this->api->player->getAll();
					foreach($players as $p){
						$p->teleport(new Vector3(127.5, 70, 176.5));//точные корды арены
						$p->sendChat($this->prefix."Битва начнется через 10 секунд..\n".$this->prefix."Разбежитесь по углам!");
						$this->api->schedule(10*20, [$this, "pvp"], [$p], false);
					}
				}
				break;
			case "player.death":
				if($data["cause"] == "void" and $this->status == "pvp"){
					$this->api->chat->broadcast($this->prefix."Сумо не предусматривается...");
					$this->status = "play";
				}
				if(is_numeric($data["cause"])){
					$e = $this->api->entity->get($data["cause"]);
					if($e->class == ENTITY_PLAYER and $this->status == "pvp"){
						$this->api->chat->broadcast($this->prefix.$e->player->username." выграл эту лаки гонку!");
					}
					$this->status = "play";
				}
				
				break;
		}
	}
	
	public function pvp($data){
		$data[0]->sendChat($this->prefix."Пвп началось!");
		$this->status = "pvp";
	}
}