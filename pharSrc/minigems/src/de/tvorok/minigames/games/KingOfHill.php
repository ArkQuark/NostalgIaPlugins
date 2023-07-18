<?php

namespace de\tvorok\minigames\games;

use ServerAPI;

class KingOfHill extends MGdummyGame{
    public function __construct(ServerAPI $api, $server = false){
        parent::__construct($api);
        $this->gameName = "kingOfHill";
    }
}