<?php

/*
__PocketMine Plugin__
name=LuckyRace
description=Plugin for LuckyRace
version=1.1.0
author=ArkQuark 
class=LRmain
apiversion=12.1
*/


class LRmain implements Plugin{
	
    public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->status = "play";
		$this->prefix = "[LuckyRace] ";
		$this->players = [];
    }

    public function init(){
		$path = join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "configs", ""]);
		if(!file_exists($path)){
			mkdir($path, 0777);
		}
		$this->configfile = new Config($path."AIO.yml", CONFIG_YAML, [
			"info" => "All in one config",
			"LuckyRace" => [
				"x" => 127.5,
				"y" => 70,
				"z" => 176.5,
				"level" => "race",
				"prize" => DIAMOND_BLOCK
			]
		]);
		$this->config = $this->configfile->get("LuckyRace");
		
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
				$dataSign = $tile->getText();
				if($dataSign[0] === "tp @a Arena"){
					$this->status = "invincible";
					$data['player']->addItem($this->config["prize"], 0, 1);
					$players = $this->api->player->getAll();
					$level = $this->api->level->get($this->config["level"]);
					$this->api->schedule(10*20, [$this, "pvp"], [], false);
					$this->api->chat->broadcast($this->prefix."Битва начнется через 10 секунд..\n".$this->prefix."Разбежитесь по углам!");
					foreach($players as $p){
						$p->teleport(new Position($this->config["x"], $this->config["y"], $this->config["z"], $level));
					}
				}
				break;
			case "player.death":
				if($this->status == "pvp"){
					unset($this->players[$data['player']->username]);
					$this->api->chat->broadcast($this->prefix.array_shift($this->players)." выграл эту лаки гонку!");
					$this->status = "play";
				}
				break;
		}
	}
	
	public function pvp(){
		$players = $this->api->player->getAll();
		foreach($players as $p){
			$this->players[$p->username] = $p->username;
		}
		//console(var_dump($this->players));
		$this->api->chat->broadcast($this->prefix."Пвп началось!");
		$this->status = "pvp";
	}
}