<?php

namespace de\tvorok\minigames;

use ServerAPI;
use Position;
use Entity;
use Player;
use BlockAPI;

class MGplayer{
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
    }
    
    public function tpToHub($username){
        $config = (new MGconfig($this->api))->getMainConfig();
        if(!isset($config["hub"])){
            return "/hub not exists!";
        }
        $hub = $config["hub"];
        $this->api->player->get($username)->teleport(new Position($hub[0], $hub[1], $hub[2], $this->api->level->get($hub["level"])));
    }
    
    public function posCommand(Entity $entity){
        $x = round($entity->x, 2);
        $y = ceil($entity->y);
        $z = round($entity->z, 2);
        return [$x, $y, $z];
    }
    
    public function setPlayers($levelName){
        $pList = [];
        $players = $this->api->player->getAll();
        if(count($players) !== 0){
            foreach($players as $player){
                if($player->gamemode === 0 and $player->entity->level->getName()){
                    $pList[$player->username] = $player;
                }
            }
        }
        if(count($pList) < 2){
            return false;
        }
        return $pList;
    }
    
    public function broadcastForPlayers(String $msg){
        $players = $this->api->player->getAll();
        foreach($players as $player){
            $player->sendChat($msg);
        }
    }
    
    public function broadcastForWorld(String $level, String $msg){
        $players = $this->api->player->getAll();
        foreach($players as $player){
            if($player->entity->level->getName() === $level){
                $player->sendChat($msg);
            }
        }
    }
    
    public function broadcastForField($field, String $msg){
        foreach($field->getPlayers() as $player){
            $player->sendChat($msg);
        }
    }
    
    public function teleportAll(String $point, Array $players, $config, String $fieldName){
        $cfg = $config["fields"][$fieldName];
        switch($point){
            case "lobby"://fix?
                $cfg = $cfg["lobby"];
                $pos = new Position($cfg[0], $cfg[1], $cfg[2], $this->api->level->get($cfg[3]));
                foreach($players as $player){
                    $player->teleport($pos);
                }
                break;
            case "spawnpoint":
                $level = $this->api->level->get($cfg["level"]);
                foreach($players as $username){
                    $player = $this->api->player->get($username);
                    $xz = MGmain::randPos([[$cfg["pos1"][0], $cfg["pos2"][0]], [$cfg["pos1"][2], $cfg["pos2"][2]]]);
                    $pos = new Position($xz[0]+.5, $cfg["pos1"][1]+.5, $xz[1]+.5, $level);
                    $player->teleport($pos);
                }
                break;
        }
    }
    
    public function confiscateItem(Int $id, Player $player){
        $air = BlockAPI::getItem(AIR, 0, 0);
        foreach($player->inventory as $s){
            if($player->inventory[$s]->getID() == $id){
                $player->inventory[$s] = $air;
            }
        }
    }
    
    public function confiscateItems(Player $player){
        $air = BlockAPI::getItem(AIR, 0, 0);
        foreach($player->inventory as $s){
            $player->inventory[$s] = $air;
        }
        $player->armor = [$air, $air, $air, $air];
        $player->sendInventory();
        $player->sendArmor($player);
    }
    
    public function joinField($field, Player $issuer, $cfg, $gameName){
        if($issuer->gamemode !== 0){
            return "/you need to be in survival mode to join!";
        }
        if($field->getStatus() == "game" or $field->getStatus() == "finish"){
            return "/this field started!";
        }
        if(count($field->getPlayers()) >= $field->getMaxPlayers()){
            return "/this game fulled!";
        }
        if(!$this->api->level->loadLevel($field->getLevelName())){
            $this->api->level->loadLevel($field->getLevelName());
        }
        $field->addPlayer($issuer->username);
        
        $level = $this->api->level->get($field->getLevelName());
        if($gameName == "spleef"){
            $pos = new Position($cfg["lobby"][0], $cfg["lobby"][1], $cfg["lobby"][2], $level);
        }
        else{
            $xz = MGmain::randPos([[$cfg["pos1"][0], $cfg["pos2"][0]], [$cfg["pos1"][2], $cfg["pos2"][2]]]);
            $pos = new Position($xz[0]+.5, $cfg["pos1"][1]+.5, $xz[1]+.5, $level);
        }
        $issuer->teleport($pos);
        //$issuer->setSpawn($pos);
        
        $this->broadcastForWorld($field->getLevelName(), $issuer->username." joined this $gameName field!");
        return "/you joined $gameName field \"".$field->getName()."\"";
    }
}