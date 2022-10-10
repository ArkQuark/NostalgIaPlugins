<?php
/*
__PocketMine Plugin__
name=NetherQuick
description=NetherTeleporter
version=2.5.1
author=Glitchmaster_PE and wies
class=NQmain
apiversion=12.1
*/

class NQmain implements Plugin{

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->enable = true;
		$this->prefix = "[NetherQuick] ";
	}

	public function init(){
		$path = join(DIRECTORY_SEPARATOR, [DATA_PATH."worlds", "Nether", ""]);
		if(file_exists($path)){
			$this->api->level->loadLevel("Nether");
			$level = $this->api->level->get("Nether");
			$level->setTime(17940);
		}
		else{
			console(FORMAT_RED."Nether world not found".FORMAT_RESET);
			$this->enable = false;
			return;
		}
		$this->api->schedule(60, [$this, "changeTime"], [$level], true);
		$this->api->addHandler("player.block.touch", [$this, "touchHandler"]);
		$this->netherTeleporterIds = [57, 155];
		$this->netherTeleporterPattern = [[0, -1, 0], [0, -2, 0]];
		$this->api->console->register("return", "", [$this, "commandHandler"]);		
		$this->api->ban->cmdWhitelist("return");
		$this->playerTeleportedLevel = [];
    }
	
	public function changeTime($data){
		$data[0]->setTime(17940);
	}

	public function commandHandler($cmd, $params, $issuer, $alias){
		if($cmd == "return"){
			if(!($issuer instanceof Player)) return $this->prefix."Please run this command in-game";
			if(!isset($this->playerTeleportedLevel[$issuer->username])) {
				$level = $this->api->level->getDefault();
				$issuer->teleport($level->getSpawn());
			}
			else{
				$level = $this->playerTeleportedLevel[$issuer->username];
				$issuer->teleport($level->getSpawn());
			}
			$issuer->sendChat($this->prefix."Returned to overworld");
		}
	}

	public function touchHandler($data){
		if(!$this->enable) return;
		$target = $data["target"];
		if($target->getID() === 247){
		    $player = $data["player"];
			$x = $target->x;
			$y = $target->y;
			$z = $target->z;
			$level = $player->level;
			$blocks = [];
			foreach($this->netherTeleporterPattern as $val){
				$blocks[] = $level->getBlock(new Vector3($x + $val[0], $y + $val[1], $z + $val[2]))->getID();
			}
			if($this->netherTeleporterIds == $blocks){
				$safespawn = $this->getSafeZone($x, $y, $z, "Nether");
				$this->playerTeleportedLevel[$player->username] = $level;
				$player->teleport($safespawn);
				$player->sendChat($this->prefix."You have been teleported to Nether world");
				$player->sendChat($this->prefix."If you want return just /return");
			}
		}
		elseif($target->getID() == BED_BLOCK and $target->level->getName() == "Nether"){
			(new Explosion(new Position($target->x, $target->y, $target->z, $target->level), 4))->explode();
			return false;
		}
	}
	
	public function getSafeZone($xs, $ys, $zs, $lvl){
	//Code from PocketMine-MP/src/world/Level.php 
			$x = (int)round($xs);
			$y = (int)round($ys);
			$z = (int)round($zs);
			$lvl = (string)$lvl;
			
		$world = $this->api->level->get($lvl);
		if($world != false){
			for(; $y > 0; --$y){
				$v = new Vector3($x, $y, $z);
				$b = $world->getBlock($v);
				if($b === false){
					return new Position($xs, $ys, $zs, $world);
				}elseif(!($b instanceof AirBlock)){
					break;
				}
			}
			for(; $y < 128; ++$y){
				$v = new Vector3($x, $y, $z);
				if($world->getBlock($v) instanceof AirBlock){
					return new Position($x, $y, $z, $world);
				}else{
					++$y;
				}
			}
			return new Position($x, $y, $z, $world);
		}else{
			return false;
		}
	}

	public function __destruct(){}
}