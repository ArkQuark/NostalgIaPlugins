<?php

/*
__PocketMine Plugin__
name=LuckyRace
description=Works only with 2 players!
version=1.0
author=ArkQuark 
class=LuckyRace
apiversion=12.1
*/


class LuckyRace implements Plugin{

    public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->status = "play";
		$this->prefix = "[LuckyRace] ";
    }

    public function init(){
		$this->config = new Config($this->api->plugin->configPath("config")."AIO.yml", CONFIG_YAML, [
			"info" => "All in one config",
			"LuckyRace" => [
				"x" => 127.5,
				"y" => 70,
				"z" => 176.5,
				"level" => "race"
			]
		]);
		
		$this->api->addHandler("entity.health.change", [$this, "eventHandler"]);
		$this->api->addHandler("player.block.touch", [$this, "eventHandler"]);
		$this->api->addHandler("player.death", [$this, "eventHandler"]);
    }

    public function __destruct(){}

    public function eventHandler(&$data, $event){
		switch($event){
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
					$level = $this->api->level->get($this->config["LuckyRace"]["level"]);
					foreach($players as $p){
						$p->teleport(new Position($this->config["LuckyRace"]["x"], $this->config["LuckyRace"]["y"], $this->config["LuckyRace"]["z"], $level));
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