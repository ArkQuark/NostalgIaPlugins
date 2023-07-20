<?php

namespace de\tvorok\minigames\games;

use Player;
use ServerAPI;
use Vector3;
use de\tvorok\minigames\MGconfig;
use de\tvorok\minigames\MGmain;
use de\tvorok\minigames\MGplayer;
use de\tvorok\minigames\gameSession;

class MGdummyGame{
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
        $this->sessions = [];
        $this->gameName = "unknown";
        
        $this->mgConfig = new MGconfig($this->api);
        $this->mgPlayer = new MGplayer($this->api);
        
        $this->path = $this->mgConfig->createGameConfig($this->gameName);
        $this->setFields();
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
    
    public function playerQuit($data, $hData){
        $field = $hData["field"];
        $user = $hData["user"];
        $status = $hData["status"];
        
        if($status == "game"){
            $this->loserProcess($data, "player.quit", $field->getName());
            $this->mgPlayer->broadcastForWorld($field->getLevelName(), "$user quit.");
        }
        if($status == "lobby" or $status == "start"){
            $field->removePlayer($user);
            $this->updateField($field);
        }
    }
    
    public function playerIntecart($data, $hData){
        return false;
    }
    
    public function playerBlockPlace($data, $hData){
        return false;
    }
    
    public function hubTeleport($data, $hData){
        $field = $hData["field"];
        $user = $hData["user"];
        $status = $hData["status"];
        
        $field->removePlayer($user);
        $this->updateField($field);
        $this->mgPlayer->confiscateItems($this->api->player->get($user));
        if($status == "game"){
            $this->checkForWin($field);
        }
        $data["player"]->sendChat("You leave ".$this->gameName." game!");
    }
    
    public function handler($data, $event){
        if($this->fields == false){
            return;
        }
        
        if($data instanceof Player){
            $user = $data->username;
        }
        if($event == "player.move"){
            $user = $data->player->username;
        }
        if(!isset($user)){
            $user = $data["player"]->username;
        }
        
        $fieldName = MGmain::playerInField($user, $this->fields); //in field?
        if($fieldName == false){
            return;
        }
        $field = $this->sessions[$fieldName];
        $status = $field->getStatus();
        
        $hData = ["user" => $user, "field" => $field, "status" => $status];
        
        switch($event){
            case "player.move":
                $this->playerMove($data, $hData);
                break;
            case "player.block.break":
                $this->playerBlockBreak($data, $hData);
                break;
            case "player.quit":
                $this->playerQuit($data, $hData);
                break;
            case "player.interact":
                $this->playerIntecart($data, $hData);
                break;
            case "player.block.place":
                $this->playerBlockPlace($data, $hData);
                break;
            case "hub.teleport":
                $this->hubTeleport($data, $hData);
                break;
        }
    }
    
    public function command($cmd, $args, $issuer, $alias){
        if(!($issuer instanceof Player)){
            return "Please run command in game.";
        }
        if(!isset($args[0]) or $args[0] === ""){
            if($this->api->ban->isOp($issuer->username)){
                return "/$cmd join <fieldName>\n/$cmd wins\n/$cmd setfield <fieldName> [maxPlayers]\n/$cmd setlobby <fieldName>\n/$cmd setpos1 <fieldName>\n/$cmd setpos2 <fieldName>";
            }
            return "/$cmd join <fieldName>\n/$cmd wins";
        }
        $output = "";
        switch($args[0]){
            case "wins":
                return $this->mgConfig->getWins($issuer->username, $this->gameName)." wins";
            case "join":
                if(!isset($args[1]) or $args[1] === ""){
                    return "/$cmd join <field>";
                }
                $fieldName = $args[1];
                if(!isset($this->config["fields"][$fieldName])){
                    return "/this field doesn't exist!";
                }
                if($issuer->level->getName() != $this->mgConfig->getMainConfig()["hub"]["level"]){
                    return "/you need to be in hub to join!";
                }
                if(MGmain::playerInField($issuer->username, $this->fields) != false){
                    return "/you already in field!";
                }
                if(!isset($this->sessions[$fieldName])){ //start code
                    $this->startField($fieldName);
                    //$output .= "/starting field \"$fieldName\"\n";
                }
                $msg = $this->mgPlayer->joinField($this->sessions[$fieldName], $issuer, $this->config["fields"][$fieldName], $this->gameName);
                $this->updateField($this->sessions[$fieldName]);
                return $msg;
            default:
                if($this->api->ban->isOp($issuer->username)){
                    $output = $this->opCommand($cmd, $args, $issuer);
                }
        }
        return $output;
    }
    
    public function opCommand($cmd, $args, $issuer){
        if(isset($args[1]) and $args[1] !== ""){
            $fieldName = $args[1];
        }
        else{
            return "/$cmd ".$args[0]." <fieldName>";
        }
        switch($args[0]){
            case "setfield":
                if(!isset($args[2]) or $args[2] == ""){
                    $maxPlayers = 12;
                }
                else{
                    $maxPlayers = $args[2];
                }
                $this->mgConfig->fieldIntoConfig($this->path, $fieldName, [
                    "level" => $issuer->entity->level->getName(),
                    "maxPlayers" => $maxPlayers
                ]);
                $this->setFields();
                return "/field $fieldName created";
                //todo delfield
            case "setpos1":
            case "setpos2":
            case "setlobby":
                $output = $this->mgConfig->posIntoConfig($issuer, $fieldName, substr($args[0], 3), $this->path);
                if($output){
                    $this->setFields();
                    return "/".$args[0]." seted";
                }
                else{
                    return $output;
                }
            case "start":
                return "/use /$cmd join <fieldName>";
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
        $this->api->chat->broadcast(ucfirst($this->gameName)." \"$fieldName\" will start in ".MGmain::formatTime($this->config["lobbyTime"]));
        $field->timer($this->config["lobbyTime"], "The game starts in");
        $this->api->schedule($this->config["lobbyTime"] * 20, [$this, "game"], $field);
        return true;
    }
    
    public function game($field){
        $players = $field->getPlayers();
        if(count($players) < 1){
            $this->mgPlayer->broadcastForWorld($field->getLevelName(), ucfirst($this->gameName)." cannot run, need 2 players!");
            $this->restoreField($field); //todo schedule
            return false;
        }
        else{
            $this->mgPlayer->teleportAll("spawnpoint", $players, $this->config, $field->getName());
            $field->setStatus("game");
            $this->updateField($field);
            $this->api->chat->broadcast(ucfirst($this->gameName)." \"".$field->getName()."\" has been started!");
            return true;
        }
    }
    
    public function finish($array){
        $winner = $array[0];
        $field = $array[1];
        $field->setStatus("finish");
        $this->mgConfig->addWin($winner, $this->gameName);
        $this->api->chat->broadcast("$winner win in ".$this->gameName." \"".$field->getName()."\"!");
        //$this->mgPlayer->confiscateItems($this->api->player->get($winner));
        $this->restoreField($field);
        return true;
    }
    
    public function end($level){
        $players = $this->api->player->getAll($this->api->level->get($level));
        foreach($players as $player){
            $this->mgPlayer->tpToHub($player->username);
        }
        return true;
    }
    
    public function restoreField($field){
        $this->mgPlayer->broadcastForWorld($field->getLevelName(), "You will teleported to hub!");
        $this->api->schedule(30, [$this, "end"], $field->getLevelName());
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
    
    public function checkForWin($field){
        $players = $field->getPlayers();
        $surv = count($players);
        if($surv > 1){
            $this->mgPlayer->broadcastForWorld($field->getLevelName(), "$surv players remaining.");
        }
        elseif($surv = 1){
            $winner = array_shift($players);
            $this->api->schedule(1, [$this, "finish"], [$winner, $field]);
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