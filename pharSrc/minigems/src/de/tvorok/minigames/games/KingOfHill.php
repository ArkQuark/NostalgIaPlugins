<?php

namespace de\tvorok\minigames\games;

use ServerAPI;

class KingOfHill extends MGdummyGame{
    public function __construct(ServerAPI $api, $gameName = "KingOfHill"){
        parent::__construct($api, $gameName);
    }
}