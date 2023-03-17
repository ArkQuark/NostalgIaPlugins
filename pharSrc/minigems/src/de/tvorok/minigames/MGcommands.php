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
        $subcmd = $args[0];
        switch($subcmd){
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
        $subcmd = $args[0];
        if(isset($args[1]) and $args[1] !== ""){
            $fieldName = $args[1];
        }
        else{
            return "/$cmd $subcmd <fieldName>";
        }
        
        switch($subcmd){
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
        }
    }
}
