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
    }
    
    public function loserProcess($data, $fieldName, $event){
        $field = $this->sessions[$fieldName];
        $user = $data->username;
        $field->removePlayer($user);
        $this->updateField($field);
        $this->checkForWin($field);
    }
}