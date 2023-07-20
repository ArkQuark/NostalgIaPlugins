<?php

namespace de\tvorok\minigames;

use ServerAPI;

class gameSession{
    public function __construct(ServerAPI $api, $field){
        $this->api = $api;
        $this->field = $field;
    }
    
    public function timer($sec, String $msg){
        $time = $sec;
        $counts = [];
        switch($time){
            case($time >= 60):
                $counts = array_merge($counts, $this->getMultiple($time, 60));
                $time = (int) 59;
            case($time >= 10):
                $counts = array_merge($counts, $this->getMultiple($time, 10));
                $time = (int) 9;
            case($time >= 5):
                $counts = array_merge($counts, $this->getMultiple($time, 5));
                $time = (int) 4;
            case($time >= 1):
                $counts = array_merge($counts, $this->getMultiple($time, 1));
                break;
            default:
                return;
        }
        asort($counts);
        foreach($counts as $cnt){
            $this->api->schedule(($sec - $cnt) * 20, [$this, "showTime"], [$cnt, $msg]);
        }
    }
    
    public function showTime($array){
        (new MGplayer($this->api))->broadcastForWorld($this->getLevelName(), $array[1]." ".MGmain::formatTime($array[0]));
    }
    
    public function getMultiple($int, $mlt){
        $arg = (int) $mlt;
        $return = [];
        while($arg <= $int){
            if(($arg % $mlt) == 0){
                $return[] = $arg;
            }
            ++$arg;
        }
        return $return;
    }
    
    public function getStatus(){
        return $this->field["status"];
    }
    
    public function getPlayers(){
        return $this->field["players"];
    }
    
    public function getLevelName(){
        return $this->field["level"];
    }
    
    public function getName(){
        return $this->field["name"];
    }
    
    public function getBackup(){
        return $this->field["backup"] !== null ? $this->field["backup"] : [];
    }
    
    public function getMaxPlayers(){
        return $this->field["maxPlayers"];
    }
    
    public function setStatus($status){
        $this->field["status"] = $status;
    }
    
    public function addPlayer(String $username){
        $this->field["players"][$username] = $username;
    }
    
    public function removePlayer(String $username){
        unset($this->field["players"][$username]);
    }
    
    public function addBackup($block){
        $this->field["backup"][] = $block;
    }
    
    public function restoreData(){
        $this->field["status"] = false;
        $this->field["players"] = [];
        $this->field["backup"] = [];
    }
    
    public function updateData(){
        return $this->field;
    }
}