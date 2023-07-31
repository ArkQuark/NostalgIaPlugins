<?php

namespace de\tvorok\minigames\games;

use ServerAPI;
use Vector3;

class ObstacleRace extends MGdummyGame{
    public function __construct(ServerAPI $api, $gameName = "ObstacleRace"){
        parent::__construct($api, $gameName);
    }
    
    public function entityHealthChange($data, $hData){
        //if 0 hp run this:
        //$this->mgPlayer->teleportTo("spawnpoint", $hData["user"], $this->config, $hData["field"]->getName());
        return false;
    }
    
    public function playerMove($data, $hData){
        $downBlock = $data->level->getBlock(new Vector3($data->x, $data->y-1, $data->z));
        if($downBlock->getID() === WOOL and $downBlock->getMetadata() === 14){//finish line is red wool
            $this->finish([$hData["user"], $hData["field"]]);
        }
    }
}