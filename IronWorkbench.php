<?php

/*
__PocketMine Plugin__
name=IronWorkbench
description=Custom Crafting system using an Iron Block!
version=3.1
author=ArkQuark
class=IWmain
apiversion=12.2
*/

class IWmain implements Plugin{
    //Special thx to SkilasticYT, DartMiner43, Tema1d, GameHerobrine and MineDg

    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
        $this->playerTiles = [];
    }

    public function init(){
        $this->createConfig();
        $this->parseCrafts();

        $this->api->addHandler("player.block.touch", [$this, "touchHandler"]);
        $this->api->addHandler("player.container.slot", [$this, "containerSlotHandler"]);
        DataPacketReceiveEvent::register([$this, "packetListener"], EventPriority::NORMAL);

        $this->api->console->register("crafts", "", [$this, "commandHandler"]);
        $this->api->ban->cmdWhitelist("crafts");
    }

    public function packetListener(DataPacketReceiveEvent $event){
        $player = $event->getPlayer();
        $pk = $event->getPacket();
        if($pk->pid() === ProtocolInfo::CONTAINER_CLOSE_PACKET && isset($this->playerTiles[$player->iusername])){
            unset($this->playerTiles[$player->iusername]);
            if(!is_array($player->windows[$pk->windowid]) && $player->windows[$pk->windowid]->class === TILE_CHEST){
                $tile = $player->windows[$pk->windowid];
                //console(floor($player->entity->x).", ".floor($player->entity->y).", ".floor($player->entity->z)."\n");//needfix
                //console($tile->x.", ".$tile->y.", ".$tile->z);
                if(new Vector3(floor($player->entity->x), floor($player->entity->y), floor($player->entity->z)) == new Vector3($tile->x, $tile->y, $tile->z)){
                    $player->teleport(new Vector3($player->entity->x, $player->entity->y + 0.1, $player->entity->z));
                }
                $idmeta = $player->level->level->getBlock($tile->x, $tile->y, $tile->z);
                $pkk = new UpdateBlockPacket();
                $pkk->x = (int)$tile->x;
                $pkk->y = (int)$tile->y;
                $pkk->z = (int)$tile->z;
                $pkk->block = $idmeta[0];
                $pkk->meta = $idmeta[1];
                $player->dataPacket($pkk);
            }
        }
    }

    public function containerSlotHandler($data){
        $player = $data["player"];
        $slotData = $data["slotdata"];
        $slotID = $slotData->getID();
        $slotMeta = $slotData->getMetadata();
        $slotIDM = $slotID.":".$slotMeta;
        $slotCount = $slotData->count;

        if(isset($this->playerTiles[$player->iusername])){
            $inventory = [];
            for($i = 0; $i < 36; $i++){
                $slot = $player->getSlot($i);
                if($slot !== null && $slot->getID() !== 0){
                    $inventory[] = clone $slot;
                }
            }

            $matched = true;
            if(!isset($this->crafts[$slotIDM])){
                return;
            }

            foreach($this->crafts[$slotIDM]["ingridients"] as $ingridient){
                //console(var_dump($ingridient));
                $found = false;
                foreach($inventory as $slot){
                    if($slot->getID() == $ingridient["id"] &&
                       $slot->getMetadata() == $ingridient["meta"] &&
                       $slot->count >= $ingridient["count"]){
                        $found = true;
                        break;
                    }
                }
                if(!$found){
					$player->sendChat("[IronWorkbench] У вас недостаточно ингредиентов.");
					$this->closePlayerChest($player);
                    $matched = false;
                    break;
                }
            }

            if($matched){
                $resultItem = BlockAPI::getItem($slotID, $slotMeta, $slotCount);
                if(!$this->hasFreeSlot($player, $resultItem)){
                    $player->sendChat("[IronWorkbench] У вас недостаточно места в инвентаре.");
					$this->closePlayerChest($player);
                }
                else{
                    $player->addItem($slotID, $slotMeta, $slotCount, true);
                    foreach($this->crafts[$slotIDM]["ingridients"] as $ingridient){
                        $player->removeItem((int)$ingridient["id"], (int)$ingridient["meta"], $ingridient["count"]);
                    }
                }
            }

            $player->sendInventory();
            return false;
        }
    }
	
	private function closePlayerChest(Player $player){
		if(isset($this->playerTiles[$player->iusername])){
			$pk = new ContainerClosePacket();
			$pk->windowId = $this->playerTiles[$player->iusername]["windowId"];
			$player->dataPacket($pk);
		}
	}

    private function hasFreeSlot(Player $player, Item $item){
        foreach($player->inventory as $slot){
            if($slot->getID() === $item->getID() && $slot->getMetadata() === $item->getMetadata()){
                if($slot->count < $slot->getMaxStackSize()){
                    return true;
                }
            }
        }
        foreach($player->inventory as $slot){
            if($slot->getID() === AIR){
                return true;
            }
        }
        return false;
    }

    public function touchHandler($data){
        $player = $data["player"];
        $target = $data["target"];
        $targetID = $target->getID();

        if($targetID !== IRON_BLOCK) return;
        if($player->getGamemode() !== "survival" && $player->getGamemode() !== "adventure") return;

        //spawn fake chest for player
        $pk = new UpdateBlockPacket();
        $pk->x = $target->x;
        $pk->y = $target->y;
        $pk->z = $target->z;
        $pk->block = CHEST;
        $pk->meta = 0;
        $player->dataPacket($pk);

        $tile = new Tile($player->level, PHP_INT_MAX, TILE_CHEST, $pk->x, $pk->y, $pk->z, array(
            "Items" => $this->tileItems,
            "id" => TILE_CHEST,
            "x" => $pk->x,
            "y" => $pk->y,
            "z" => $pk->z
        ));
        $tile->spawn($player);
        $tile->openInventory($player);
		$windowId = null;
		foreach($player->windows as $id => $window){
			if($window === $tile){
				$windowId = $id;
				break;
			}
		}
		$this->playerTiles[$player->iusername] = ["tile" => $tile, "windowId" => $windowId];
        return false;
    }

    public function commandHandler($cmd, $params, $issuer, $alias){
        $output = "";
        //todo lists for crafts or fake double chest
        //console(var_dump($this->crafts));
        foreach($this->crafts as $resultIDM => $array){
            [$id, $meta] = explode(":", $resultIDM);
            $resultItemName = BlockAPI::getItem((int)$id, (int)$meta, 0)->name;
            foreach($array["ingridients"] as $ingridient){
                $name = BlockAPI::getItem($ingridient["id"], $ingridient["meta"], 0)->name;
                $output .= $ingridient["count"]." $name + ";
            }
            $output = substr($output, 0, -3);
            $output .= " -> ". $array["count"] ." $resultItemName\n";
        }
        return $output;
    }

    public function createConfig(){
        $path = $this->api->plugin->configPath($this);
        if(!file_exists($path."crafts.yml")){
            //todo "?" is any meta
            new Config($path."crafts.yml", CONFIG_YAML, [
                "gravel:0x1,sand:0x1=>clay:0x2",
                "string:0x3=>cobweb:0x1",
                "slab:0x2=>double_slab:7x1",
                "cobblestone:0x1,leaves:0x1=>moss_stone:0x1",
                "stone_brick:0x1,leaves:0x1=>stone_brick:1x1",
                "stone_brick:0x1=>stone_brick:2x1",
                "slab:5x2=>stone_bricks:3x1",
                "trunk:3x1=>planks:3x4",
                "planks:3x6=>jungle_wood_stairs:0x4",
                "planks:3x3=>wood_slab:3x6",
                "quartz_block:0x6=>quartz_stairs:0x4",
                "feather:0x1=>quartz:0x2",
                "iron_ingot:0x6=>iron_door:0x1"
            ]);
        }
    }

    public function parseCrafts(){
        $yaml = $this->api->plugin->readYAML($this->api->plugin->configPath($this)."crafts.yml");
        $this->crafts = [];
        $this->tileItems = [];

        $slot = 0;
        foreach($yaml as $string){
            if(strpos($string, "=>") === false){
                continue;
            }
            list($ingridients, $results) = explode("=>", $string);

            if(strpos($results, "x") !== false){
                list($resultIDM, $resultCount) = explode("x", $results);
            } else {
                $resultIDM = $results;
                $resultCount = 1;
            }

            if(strpos($resultIDM, ":") !== false){
                list($resultID, $resultMeta) = explode(":", $resultIDM);
            } else {
                $resultID = $resultIDM;
                $resultMeta = 0;
            }

            $resultID = constant(strtoupper($resultID));
            $resultMeta = (int)$resultMeta;
            $resultCount = (int)$resultCount;

            $this->tileItems[] = [
                "Count" => $resultCount,
                "Slot" => $slot++,
                "id" => $resultID,
                "Damage" => $resultMeta
            ];
            $this->crafts["$resultID:$resultMeta"] = [
                "ingridients" => [],
                "count" => $resultCount
            ];

            foreach(explode(",", $ingridients) as $ingridient){
                if(strpos($ingridient, "x") !== false){
                    list($idm, $cnt) = explode("x", $ingridient);
                } else {
                    $idm = $ingridient;
                    $cnt = 1;
                }
                if(strpos($idm, ":") !== false){
                    list($id, $meta) = explode(":", $idm);
                } else {
                    $id = $idm;
                    $meta = 0;
                }
                $id = constant(strtoupper($id));
                $meta = (int)$meta;
                $cnt = (int)$cnt;

                $this->crafts["$resultID:$resultMeta"]["ingridients"][] = ["id" => $id, "meta" => $meta, "count" => $cnt];
            }
        }
    }
}
