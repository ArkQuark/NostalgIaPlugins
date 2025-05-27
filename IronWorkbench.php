<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=Custom Crafting system using an Iron Block!
version=3.0
author=ArkQuark
class=IWmain
apiversion=12.2
*/

class IWmain implements Plugin{
	//Special thx to SkilasticYT and DartMiner43

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}

	public function init(){
		$this->createConfig();
		$this->parseCrafts();

		$this->api->addHandler("player.block.touch", [$this, "eventHandler"]);
		$this->api->console->register("crafts", "", [$this, "commandHandler"]);
		$this->api->ban->cmdWhitelist("crafts");
	}
	
	
	public function eventHandler($data, $event){
		$player = $data["player"];
		$target = $data["target"];
		$targetID = $target->getID();
		
		if($targetID !== IRON_BLOCK) return;
		if($player->getGamemode() !== "survival" and $player->getGamemode() !== "adventure") return;
				
		$itemheld = $player->getSlot($player->slot);
		$itemheldID = $itemheld->getID();
		$itemheldMeta = $itemheld->getMetadata();
		$itemheldCount = $itemheld->count;
				
		$pos = new Position($target->x, $target->y, $target->z, $target->level);
		$dropPos = new Position($target->x+0.5, $target->y+1, $target->z+0.5, $target->level);
				
		if($itemheldID === AIR) return; 
				
		foreach($this->crafts as $id => $array){
			$meta = $array["meta"];
			$count = $array["count"];
			if($itemheldID === $id and $itemheldMeta === $meta and $itemheldCount >= $count){
				$player->removeItem($id, $meta, $array["count"]);
				$item = BlockAPI::getItem($array["result"]["item"], $array["result"]["meta"], $array["result"]["count"]);
				$this->api->entity->drop($dropPos, $item);
				if($id < 256){
					return false;
				}
			}
		}	
	}

	public function commandHandler($cmd, $params, $issuer, $alias){
		$output = "";
		foreach($this->crafts as $id => $array){
			$itemName = BlockAPI::getItem($id, $array['meta'], $array['count'])->name;
			$result = $array["result"];
			$resultItemName = BlockAPI::getItem($result["item"], $result["meta"], $result['count'])->name;
			$output .= $array["count"] ." $itemName -> ". $result["count"] ." $resultItemName\n";
			//todo craft lists
		}
		return $output;
	}
	
	public function createConfig(){
		$path = $this->api->plugin->configPath($this);
		//console(FORMAT_GREEN."Making yml file for IronWorkbench crafts".FORMAT_RESET);
		//todo crafts for snowblocks, irondoor, clay
		new Config($path."crafts.yml", CONFIG_YAML, [
			"trunk" => [
				"meta" => 3,
				"count" => 1,
				"result" => [
					"item" => "planks",
					"meta" => 3,
					"count" => 4
				]
			],
			"feather" => [
				"meta" => 0,
				"count" => 1,
				"result" => [
					"item" => "quartz",
					"meta" => 0,
					"count" => 1
				]
			],
			"coal" => [
				"meta" => 0,
				"count" => 1,
				"result" => [
					"item" => "dye",
					"meta" => 0,
					"count" => 1
				]
			]
		]);
	}
	
	public function parseCrafts(){
		$yaml = $this->api->plugin->readYAML($this->api->plugin->configPath($this)."crafts.yml");
		$this->crafts = [];
		foreach($yaml as $id => $array){
			$itemID = constant(strtoupper($id));
			$this->crafts[$itemID] = [
				"meta" => $array["meta"],
				"count" => $array["count"],
				"result" => [
					"item" => constant(strtoupper($array["result"]["item"])),
					"meta" => $array["result"]["meta"],
					"count" => $array["result"]["count"]
				]
			];
		}
	}
}