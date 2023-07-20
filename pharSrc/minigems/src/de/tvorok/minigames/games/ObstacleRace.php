<?php

namespace de\tvorok\minigames\games;

//use de\tvorok\minigames\MGconfig;
use de\tvorok\minigames\MGmain;
//use de\tvorok\minigames\MGplayer;
use de\tvorok\minigames\gameSession;
use Player;
//use ReflectionClass;
use ServerAPI;
use Vector3;

class ObstacleRace extends MGdummyGame{
    public function __construct(ServerAPI $api, $server = false){
        parent::__construct($api);
        $this->gameName = "ObstacleRace";
        $this->createConfig();
    }
    
    public function playerDeath($data, $hData){
        $this->mgPlayer->teleportAll("spawnpoint", [$hData["user"]], $this->config, $hData["field"]->getName());
        $hData["user"]->setHealth(20, "heal");
        return false;
    }
    
    public function playerMove($data, $hData){
        $downBlock = $data->level->getBlock(new Vector3($data->x, $data->y-1, $data->z));
        if($downBlock->getID() === WOOL and $downBlock->getMetadata() === 14){//finish line is red wool
            $this->finish([$hData["user"], $hData["field"]]);
        }
    }
    
    public function loserProcess($data, $event, $fieldName){
        $field = $this->sessions[$fieldName];
        $user = $data->username;
        $field->removePlayer($user);
        $this->updateField($field);
        $this->checkForWin($field);
    }
}