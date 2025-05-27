<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=NEW Crafting system by using iron block!
version=2.3
author=DartMiner43
class=IWmain
apiversion=12.2
*/

class IWmain implements Plugin{
	//Special thx to SkilasticYT

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}

	public function init(){
		$this->api->addHandler("player.block.touch", [$this, "eventHandler"]);
		$this->api->console->register("crafts", "", [$this, "commandHandler"]);
		$this->api->ban->cmdWhitelist("crafts");
	}
	
	
	public function eventHandler($data, $event){
		$player = $data["player"];
		$target = $data["target"];
		$targetID = $target->getID();

if($targetID !== IRON_BLOCK) return;
				
		$itemheld = $player->getSlot($player->slot);
		$itemheldID = $itemheld->getID();
		$itemheldMeta = $itemheld->getMetadata();
		$itemheldCount = $itemheld->count;
				
		$pos = new Position($target->x, $target->y, $target->z, $target->level);
		$dropPos = new Position($target->x+0.5, $target->y+1, $target->z+0.5, $target->level);
				
		if($player->getGamemode() !== "survival" and $player->getGamemode !== "adventure") return;
		if($itemheldCount === 0) return;
				
		if($itemheldID === FLINT){//Flint -> Gunpowder
			$player->removeItem(FLINT, 0, 1, false);
			$item = BlockAPI::getItem(GUNPOWDER, 0, 1);
			$this->api->entity->drop($dropPos, $item);
		}
		elseif($itemheldID === TRUNK and $itemheldMeta === 3){//Jungle wood -> 4 Jungle planks
			$player->removeItem(TRUNK, 3, 1, false);
			$item = BlockAPI::getItem(PLANKS, 3, 4);
			$this->api->entity->drop($dropPos, $item, 3);
			if($data['type'] === 'place') return false;
		}
		elseif($itemheldID === BONE){//Bone -> Quartz
			$player->removeItem(Bone, 0, 1, false);
			$item = BlockAPI::getItem(Quartz, 0, 1);
			$this->api->entity->drop($dropPos, $item);
		}
		elseif($itemheldID === TALL_GRASS){ //Grass -> Dead bush
			$player->removeItem(TALL_GRASS, $itemheldMeta, 1, false);
			$item = BlockAPI::getItem(DEAD_BUSH, 0, 1);
			$this->api->entity->drop($dropPos, $item);
		}
		elseif($itemheldID === SAPLING and $itemheldCount >== 8){//8 Saplings -> Grass block
			$player->removeItem(SAPLING, $itemheldMeta, 8, false);
			$item = BlockAPI::getItem(GRASS, 0, 1);
			$this->api->entity->drop($dropPos, $item);
			if($data['type'] === 'place') return false;
		}
		elseif($itemheldID === COAL){//Coal -> Inc sac
			$player->removeItem(COAL, $itemheldMeta, 1, false);
			$item = BlockAPI::getItem(DYE, 0, 1);
			$this->api->entity->drop($dropPos, $item);
		}
	}

	public function commandHandler($cmd, $params, $issuer, $alias){
		//wip add pages and works with yml "info"
		$output = "Crafts with IronWorkbench:\n";
		$output .= "Flint -> Gunpowder\n";
		$output .= "Jungle Wood -> 4 Jungle planks\n";
		$output .= "Bone -> Quartz\n";
		$output .= "Tall Grass/Fern -> Dead bush\n";
		$output .= "8 Saplings -> Grass block\n";
		$output .= "Coal -> Inc sac";
		return $output;
	}
	
	public function createCraftsFile(){
		//wip soon
		//console(FORMAT_GREEN."Making yml file for IronWorkbench crafts".FORMAT_RESET);
		//new Config($this->api->plugin->configPath($this)."\IronWorkbench\crafts.yml", CONFIG_YAML, [
		//0 => 
		//	"material" => 
		//		"needle" => "name of item" string, "count" int,
		//		"crafted" => "name of item" string, "count" int;
		//	"info" => string, //uses for command
		//	"needleIsBlock" = false/true
		//]);
	}
	
	public function parseCrafts(){
		//wip
	}

	public function __destruct(){}
}