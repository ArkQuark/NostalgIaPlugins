<?php
/*
__PocketMine Plugin__
name=NetherQuick
description=NetherTeleporter
version=2.4.1Nostalgic
author=Glitchmaster_PE and wies
class=NetherQuick
apiversion=12.1
*/

class NetherQuick implements Plugin{
	private $api;
	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->enable = true;
		$this->prefix = "[NetherQuick] ";
	}

	public function init(){
		if(file_exists('./worlds/Nether/')) $this->api->level->loadLevel("Nether");
		else{
			console("Nether world not found");
			$this->enable = false;
		}
		$this->api->addHandler("player.block.touch", [$this, "touchHandler"]);
		//$this->config = new Config($this->api->plugin->configPath($this) . "config.yml", CONFIG_YAML,);
		//$this->ognetherReactorIds = [4,4,4,4,4,41,41,41,41,0,0,0,0,4,4,4,4,4,4,4,4,4,0,0,0,0];
		//$this->ognetherReactorPattern = [[0,-1,0], [1,-1,0], [-1,-1,0], [0,-1,1], [0,-1,-1], [1,-1,1], [1,-1,-1], [-1,-1,1], [-1,-1,-1], [1,0,0], [-1,0,0], [0,0,1], [0,0,-1], [1,0,1], [1,0,-1], [-1,0,1], [-1,0,-1], [0,1,0], [1,1,0], [-1,1,0], [0,1,1], [0,1,-1], [1,1,1], [1,1,-1], [-1,1,1], [-1,1,-1]];
		$this->netherTeleporterIds = [57, 155];
		$this->netherTeleporterPattern = [[0, -1, 0], [0, -2, 0]];
		$this->api->console->register("return", "", array($this, "commandHandler"));		
		$this->api->ban->cmdWhitelist("return");
		$this->playerTeleportedLevel = [];
    }

	public function commandHandler($cmd, $params, $issuer, $alias){
		if($cmd == "return"){
			if(!($issuer instanceof Player)) return $this->prefix."Please run this command in-game";
			//if(!isset($this->playerTeleportedLevel[$issuer->username])) return $this->prefix."Undefined teleport level";
			//else{
			$level = $this->api->level->getDefault();//$this->playerTeleportedLevel[$issuer->username];
			$issuer->teleport($level->getSpawn());
			$issuer->sendChat($this->prefix."Returned to overworld");
			//}
		}
	}

	public function touchHandler($data){
		if(!$this->enable) return;
		if($data["target"]->getID() === 247){
		    $player = $data["player"];
			$x = $data["target"]->x;
			$y = $data["target"]->y;
			$z = $data["target"]->z;
			$level = $player->level;
			//$blocks = [];
			$blocks2 = [];
			/*foreach($this->ognetherReactorPattern as $val){
				$blocks[] = $level->getBlock(new Vector3($x + $val[0], $y + $val[1], $z + $val[2]))->getID();
			}*/
			foreach($this->netherTeleporterPattern as $val){
				$blocks2[] = $level->getBlock(new Vector3($x + $val[0], $y + $val[1], $z + $val[2]))->getID();
			}
			if($this->netherTeleporterIds == $blocks2){
				$safespawn = $this->getSafeZone($x, $y, $z, "Nether");
				$this->playerTeleportedLevel[$player->username] = $level;
				$player->teleport($safespawn);
				$player->sendChat($this->prefix."You have been teleported to Nether world");
				$player->sendChat($this->prefix."If you want return just /return");
			}
			/*elseif($this->ognetherReactorIds == $blocks){
				return false;
			}*/
		}
	}
	
	public function getSafeZone($xs, $ys, $zs, $lvl){
	//Code from PocketMine-MP/src/world/Level.php 
			$x = (int)round($xs);
			$y = (int)round($ys);
			$z = (int)round($zs);
			$lvl = (string)$lvl;
			
		$world = $this->api->level->get($lvl);
		if ($world != false){
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
?>

