<?php

namespace de\tvorok\minigames\games;

use Player;
use ServerAPI;
use de\tvorok\minigames\MGconfig;
use de\tvorok\minigames\MGmain;
use de\tvorok\minigames\MGplayer;
use de\tvorok\minigames\gameSession;
use de\tvorok\minigames\MGcommands;

class MGdummyGame extends MGcommands{
    public function __construct(ServerAPI $api, $gameName = "unknown"){
        $this->api = $api;
        $this->sessions = [];
        $this->gameName = $gameName;
        
        $this->mgConfig = new MGconfig($this->api);
        $this->mgPlayer = new MGplayer($this->api);
        
        $this->createConfig();
    }
    
    public function createConfig(){
        $this->path = $this->mgConfig->createGameConfig($this->gameName);
        $this->setFields();
    }
    
    public function setFields($data = []){
        $this->config = $this->mgConfig->getGameConfig($this->path);
        if(!isset($this->config["fields"])){
            $this->fields = false;
            return;
        }
        foreach($this->config["fields"] as $fieldName => $array){
            unset($array);
            $this->fields[$fieldName] = [
                "players" => [],
                "status" => false,
                "backup" => [],
                "name" => "$fieldName",
                "level" => $this->config["fields"][$fieldName]["level"],
                "maxPlayers" => $this->config["fields"][$fieldName]["maxPlayers"]
            ] + $data;
        }
    }
    
    public function updateField($field){
        $this->fields[$field->getName()] = $field->updateData();
    }
    
    public function playerMove($data, $hData){
        return;
    }
    
    public function playerBlockBreak($data, $hData){
        return false;
    }
    
    public function playerDeath($data, $hData){
        return;
    }
    
    public function playerQuit($data, $hData){
        $field = $hData["field"];
        $user = $hData["user"];
        $status = $hData["status"];
        
        if($status == "game"){
            $this->loserProcess($data, "player.quit", $field->getName());
            $this->mgPlayer->broadcastForField($field, "$user quit.");
        }
        if($status == "lobby" or $status == "start"){
            $field->removePlayer($user);
            $this->updateField($field);
        }
        return;
    }
    
    public function playerIntecart($data, $hData){
        return false;
    }
    
    public function playerBlockPlace($data, $hData){
        return false;
    }
    
    public function consoleCommand($data, $hData){
    	if($data["cmd"] === hub) return true;
    	return "/Cannot use command while in-game!";
   	}
    
    public function hubTeleport($data, $hData){
        $field = $hData["field"];
        $user = $hData["user"];
        $status = $hData["status"];
        
        $field->removePlayer($user);
        $this->updateField($field);
        //$this->mgPlayer->confiscateItems($this->api->player->get($user));
        if($status == "game"){
            $this->checkForWin($field);
        }
        /*$data["player"]->sendChat*/ return ("You left ".$this->gameName." game!");
    }
    
    public function entityHealthChange($data, $hData){
        return;
    }
    
    public function getUsername($data, $event){
        if($data instanceof Player){
            $user = $data->username;
        }
        if($event == "entity.health.change"){
            $player = $data["entity"]->player;
            if(!$player) return false;
            $user = $player->username;
        }
        if($event == "console.command"){//fix
            $user = $data["issuer"]->username;
        }
        if($event == "player.move"){
            $user = $data->player->username;
        }
        if(!isset($user)){
            $user = $data["player"]->username;
        }
        return $user;
    }
    
    public function handler($data, $event){
        if($this->fields == false){
            return;
        }
        
        if(!$this->getUsername($data, $event)){
            return;
        }
        $user = $this->getUsername($data, $event);
        
        $fieldName = MGmain::playerInField($user, $this->fields); //in field?
        if($fieldName == false){
            return;
        }
        $field = $this->sessions[$fieldName];
        $status = $field->getStatus();
        
        $hData = ["user" => $user, "field" => $field, "status" => $status];
        
        switch($event){
            case "player.move":
                return $this->playerMove($data, $hData);
            case "player.block.break":
                return $this->playerBlockBreak($data, $hData);
            case "player.death":
                return $this->playerDeath($data, $hData);
            case "player.quit":
                return $this->playerQuit($data, $hData);
            case "player.interact":
                return $this->playerIntecart($data, $hData);
            case "player.block.place":
                return $this->playerBlockPlace($data, $hData);
            case "hub.teleport":
                return $this->hubTeleport($data, $hData);
            case "console.command":
                return $this->hubTeleport($data, $hData);
            case "entity.health.change":
                return $this->entityHealthChange($data, $hData);
        }
    }
    
    //stages
    public function startField($fieldName = ""){
        if($fieldName == ""){
            $fieldName = array_rand(MGmain::getAvailableFields($this->fields));
        }
        $config = $this->config["fields"][$fieldName];
        if(!isset($config)){
            console("this field doesn't exist!");
            return false;
        }
        if(!isset($this->config["fields"][$fieldName]["lobby"])){
            console("$fieldName lobby not found!");
            return false;
        }
        if(!isset($this->config["fields"][$fieldName]["pos1"]) and !isset($this->config["fields"][$fieldName]["pos2"])){
            console("$fieldName where pos1 or pos2!!!");
            return false;
        }
        $field = new gameSession($this->api, $this->fields[$fieldName]);
        $this->sessions[$fieldName] = $field;
        $field->setStatus("start");
        $this->updateField($field);
        $this->lobby($field);
        return true;
    }
    
    public function lobby($field){
        $fieldName = $field->getName();
        $field->setStatus("lobby");
        $this->updateField($field);
        $this->api->time->set(0, $this->api->level->get($field->getLevelName())); //fix
        //tp to lobby???
        $this->api->chat->broadcast($this->gameName." \"$fieldName\" will start in ".MGmain::formatTime($this->config["lobbyTime"]));
        $field->timer($this->config["lobbyTime"], "The game starts in");
        $this->api->schedule($this->config["lobbyTime"] * 20, [$this, "game"], $field);
        return true;
    }
    
    public function game($field){
        $players = $field->getPlayers();
        if(count($players) < 1){
            $this->mgPlayer->broadcastForField($field, $this->gameName." cannot run, need 2 players!");
            $this->restoreField($field); //todo schedule
            return false;
        }
        else{
            $this->mgPlayer->teleportAll("spawnpoint", $players, $this->config, $field->getName());
            $field->setStatus("game");
            $this->updateField($field);
            $this->api->chat->broadcast($this->gameName." \"".$field->getName()."\" has been started!");
            return true;
        }
    }
    
    public function finish($field, $winner){
        $field->setStatus("finish");
        $this->mgConfig->addWin($winner, $this->gameName);
        $this->api->chat->broadcast("$winner win in ".$this->gameName." \"".$field->getName()."\"!");
        //$this->mgPlayer->confiscateItems($this->api->player->get($winner));
        $this->restoreField($field);
        $this->mgPlayer->broadcastForField($field, "You will teleported to hub!");
        $this->api->schedule(30, [$this, "end"], $field);
        return true;
    }
    
    public function restoreField($field){
        //console("was break ".count($field->getBackup())." blocks");
        if(count($field->getBackup()) > 0){
            $blocks = $field->getBackup();
            foreach($blocks as $block){
                $block->level->setBlockRaw($block, $block);
            }
        }
        $field->restoreData();
        $this->updateField($field);
        unset($this->sessions[$field->getName()]);
        unset($field);
        return true;
    }
    
    public function end($field){
        $players = $field->getPlayers();
        foreach($players as $player){
            $this->mgPlayer->teleportTo("hub", $player);
        }
        return true;
    }
    
    public function forceFinish($field){
        $field->setStatus("finish");
        $this->restoreField($field);
    }
    
    public function checkForWin($field){
        $players = $field->getPlayers();
        $surv = count($players);
        if($surv > 1){
            $this->mgPlayer->broadcastForField($field, "$surv players remaining.");
        }
        elseif($surv == 1){
            $winner = array_shift($players);
            $this->finish($field, $winner);
        }
        else{
            $this->forceFinish($field);
        }
    }
    
    public function loserProcess($data, $event, $fieldName){
        $field = $this->sessions[$fieldName];
        switch($event){
            case "player.death":
                $user = $data["player"]->username;
            case "player.quit":
                if(!isset($user)) $user = $data->username;
                $field->removePlayer($user);
                $this->updateField($field);
                $this->checkForWin($field);
                break;
        }
    }
}