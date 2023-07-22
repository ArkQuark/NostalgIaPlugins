<?php

namespace de\tvorok\minigames\games;

use de\tvorok\minigames\MGconfig;
use de\tvorok\minigames\MGmain;
use de\tvorok\minigames\MGplayer;
use de\tvorok\minigames\gameSession;
use AirBlock;
use Player;
use ServerAPI;
use Vector3;

class TNTRun extends MGdummyGame{
    public function __construct(ServerAPI $api, $gameName = "TNTRun"){
        parent::__construct($api, $gameName);
    }
    
    public function playerDeath($data, $hData){
        if($hData["status"] == "game"){
            $this->loserProcess($data, "player.death", $hData["field"]->getName());
            $this->mgPlayer->broadcastForWorld($hData["field"]->getLevelName(), $hData["user"]." dead.");
        }
    }
    
    public function playerMove($data, $hData){
        if($hData["status"] == "game"){
            $this->api->schedule(30, [$this, "destroyDownBlock"], [$data, $hData["field"]]);
        }
        /*if($data->y <= 10){//todo water
         $this->api->entity->harm($data->eid, PHP_INT_MAX, "void", true);
         $data->player->blocked = true;
         }*/
    }
    
    /*public function illegalDestroyOnGameStart($field){
        foreach($field->getPlayers() as $player){
            $this->api->schedule(TNT_DESTROY_DELAY, [$this, "destroyDownBlock"], [$this->api->player->get($player)->entity, $field]);
        }
    }*/
    
    public function destroyDownBlock($array){
        $entity = $array[0];
        $field = $array[1];
        if($field->getStatus() != "game"){
            return;
        }
        $downBlock = $entity->level->getBlock(new Vector3($entity->x, $entity->y-1, $entity->z));
        if($downBlock->getID() == TNT){
            $field->addBackup($downBlock);
            $entity->level->setBlockRaw($downBlock, new AirBlock());
        }
        elseif(in_array($downBlock->getID(), [SAND, GRAVEL])){
            $field->addBackup($downBlock);
            $entity->level->setBlockRaw($downBlock, new AirBlock());
            $downBlock2 = $entity->level->getBlock(new Vector3($entity->x, $entity->y-2, $entity->z));
            $field->addBackup($downBlock2); //wth
            $entity->level->setBlockRaw($downBlock2, new AirBlock());
        }
    }
}