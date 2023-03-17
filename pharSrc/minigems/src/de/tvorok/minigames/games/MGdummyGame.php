<?php

namespace de\tvorok\minigames\games;

use ServerAPI;
use de\tvorok\minigames\MGconfig;
//use de\tvorok\minigames\MGmain;
use de\tvorok\minigames\MGplayer;
//use de\tvorok\minigames\gameSession;

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
}