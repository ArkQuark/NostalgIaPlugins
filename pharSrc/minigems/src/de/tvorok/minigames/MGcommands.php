<?php

namespace de\tvorok\minigames;

use Player;

class MGcommands{	
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
                return $this->commandWins($cmd, $args, $issuer);
            case "join":
                return $this->commandJoin($cmd, $args, $issuer);
            default:
                if($this->api->ban->isOp($issuer->username)){
                    $output = $this->opCommand($cmd, $args, $issuer);
                }
                else{
                    return "/Unknown argument";
                }
        }
        return $output;
    }
    
    public function opCommand($cmd, $args, $issuer){
        if(array_key_exists(1, $args) and $args[1] == ""){
            return "/$cmd ".$args[0]." <fieldName>";
        }
        switch($args[0]){
            case "setfield":
                return $this->commandSetField($cmd, $args, $issuer);
            case "setpos1":
            case "setpos2":
            case "setlobby":
                return $this->commandSetPosition($cmd, $args, $issuer);
            case "start":
                return $this->commandStart($cmd, $args, $issuer);
            default:
                return "/Unknown argument";
        }
    }
    
    public function commandWins($cmd, $args, $issuer){
        return $this->mgConfig->getWins($issuer->username, $this->gameName)." wins";
    }
    
    public function commandJoin($cmd, $args, $issuer){
        if(!isset($args[1]) or $args[1] === ""){
            return "/$cmd join <field>";
        }
        $fieldName = $args[1];
        if(!isset($this->config["fields"][$fieldName])){
            return "/This field doesn't exist!";
        }
        if($issuer->level->getName() != $this->mgConfig->getMainConfig()["hub"]["level"]){
            return "/You need to be in hub to join!";
        }
        if(MGmain::playerInField($issuer->username, $this->fields) != false){
            return "/You already in field!";
        }
        if(!isset($this->sessions[$fieldName])){//start code
            $this->startField($fieldName);
            //need fix
            //$output .= "/Starting field \"$fieldName\"\n";
        }
        $msg = $this->mgPlayer->joinField($this->sessions[$fieldName], $issuer, $this->config["fields"][$fieldName], $this->gameName);
        $this->updateField($this->sessions[$fieldName]);
        return $msg;
    }
    
    public function commandSetField($cmd, $args, $issuer){
        $maxPlayers = (isset($args[2]) && $args[2] !== "") ? $args[2] : 12;
        $this->mgConfig->fieldIntoConfig($this->path, $args[1], [
            "level" => $issuer->entity->level->getName(),
            "maxPlayers" => $maxPlayers
        ]);
        $this->setFields();
        return "/field ".$args[1]." created";
        //todo delfield
    }
    
    public function commandSetPosition($cmd, $args, $issuer){
        $output = $this->mgConfig->posIntoConfig($issuer, $args[1], substr($args[0], 3), $this->path);
        if($output){
            $this->setFields();
            return "/".$args[0]." seted";
        }
        else{
            return $output;
        }
    }
    
    public function commandStart($cmd, $args, $issuer){
        return "not implement";
    }
}
