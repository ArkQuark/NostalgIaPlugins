<?php

namespace de;

class ClassLoader implements \IClassLoader{
	public function loadAll($pharPath){
		$src = $pharPath."/src/";
		$mainSrc = $src."de/tvorok/minigames/";
		$mainFiles = ["MGconfig", "MGplayer", "MGcommands", "gameSession"];
		foreach($mainFiles as $file){
		    include($mainSrc."/".$file.".php");
		}
		
        $gamesSrc = $src."de/tvorok/minigames/games";
        $gamesFiles = ["MGdummyGame", "ObstacleRace", "Spleef", "TNTRun"];
        foreach($gamesFiles as $file){
            include($gamesSrc."/".$file.".php");
        }
	}
}