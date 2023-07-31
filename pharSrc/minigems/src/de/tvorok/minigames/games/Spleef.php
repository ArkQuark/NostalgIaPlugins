<?php

namespace de\tvorok\minigames\games;

use de\tvorok\minigames\MGconfig;
use de\tvorok\minigames\MGmain;
use de\tvorok\minigames\MGplayer;
use de\tvorok\minigames\gameSession;
use Player;
use ReflectionClass;
use ServerAPI;

class Spleef extends MGdummyGame{
    public function __construct(ServerAPI $api, $gameName = "spleef"){
        parent::__construct($api, $gameName);
        
        $magikClass = new ReflectionClass("Block");
        $this->magikProperty = $magikClass->getProperty("id");
        $this->magikProperty->setAccessible(true);
    }
    
    public function playerBlockBreak($data, $hData){
        if($hData["status"] == "game"){ //fix!!! blocks to config 
            if(in_array($data["target"]->getID(), [SNOW_BLOCK, TNT, RESERVED6])){
                $hData["field"]->addBackup($data["target"]);
                $data["target"]->onBreak($data["item"], $data["player"]);
                $this->magikProperty->setValue($data["target"], 0);
                return true;
            }
            return false;
        }
        return false;
    }
    
    public function playerDeath($data, $hData){
        if($hData["status"] == "game"){
            $this->loserProcess($data, "player.death", $hData["field"]->getName());
            $this->mgPlayer->broadcastForField($hData["field"], $hData["user"]." dead.");
        }
    }
    
    public function game($field){
        $bool = parent::game($field);
        if($bool){
            foreach($field->getPlayers() as $username){
                $this->api->player->get($username)->addItem(DIAMOND_SHOVEL, 0, 1);
            }
        }
    }
    
    public function finish($field, $winner){
        $bool = parent::finish($field, $winner);
        if($bool){
            $this->mgPlayer->confiscateItem(DIAMOND_SHOVEL, $this->api->player->get($winner));
        }
    }
}