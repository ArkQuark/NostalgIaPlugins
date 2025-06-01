<?php

namespace de\tvorok\minigames;

use Config;
use Player;
use ServerAPI;

class MGconfig{
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
		$this->mainPath = join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "Minigems", "main.yml"]);
    }

    public function createConfig(){
        if(!file_exists(join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "Minigems", ""]))){
			mkdir(join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "Minigems", ""]), 0777);
		}
        $this->configFile = new Config($this->mainPath, CONFIG_YAML, [
            "MiniGames" => [
                "pluginEnable" => true,
                "spleefEnable" => true,
                "tntrunEnable" => true,
                "obstacleraceEnable" => true
                /*"hub" => [
                    "x" => $x,
                    "y" => $y,
                    "z" => $z,
                    "level" => $level
                ]*/
            ]
        ]); 
    }
    
    public function addToMainConfig(Array $array = ["SkywarsEnable" => true, "KingOfHillEnable" => true]){
        $cfg = $this->api->plugin->readYAML($this->mainPath);
        $cfg["MiniGames"] += $array;
        $this->api->plugin->writeYAML($this->mainPath, $cfg);
    }

    public function getMainConfig(){
        return $this->api->plugin->readYAML($this->mainPath)["MiniGames"];
    }

    public function createDataConfig(){
        $dataPath = $this->getDataPath();
        $dataConfig = new Config($dataPath, CONFIG_YAML, [
            "SpleefWins" => [
            ],
            "TntRunWins" => [
            ],
            "ObstacleRaceWins" => [
                
            ]
        ]);
        return $dataConfig;
    }

    public function addHub($array){
        $cfg = $this->api->plugin->readYAML($this->mainPath);
        $cfg["MiniGames"]["hub"] = $array;
        $this->api->plugin->writeYAML($this->mainPath, $cfg);
    }

    public function getDataPath(){
        return join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "Minigems", "data.yml"]);
    }

    public function posIntoConfig(Player $player, String $fieldName, String $need, $path){
        if(!isset($fieldName) or $fieldName === ""){
            return "/Unknown name of field!";
        }
        $cfg = $this->api->plugin->readYAML($path);
        if(!isset($cfg["fields"][$fieldName])){
            return "/This field name is not exist!";
        }
        $cfg["fields"][$fieldName][$need] = (new MGplayer($this->api))->posCommand($player->entity);
        $this->api->plugin->writeYAML($path, $cfg);
        return true;
    }
        
    public function getDataConfig(){
        return $this->api->plugin->readYAML($this->getDataPath());
    }
    
    public function getGameConfig($path){
        return $this->api->plugin->readYAML($path);
    }

    public function setZeroWins(String $username, String $game){
        $cfg = $this->getDataConfig();
        if(isset($cfg[$game."Wins"][$username])){
            return;
        }
        $cfg[$game."Wins"][$username] = 0;
        $this->api->plugin->writeYAML($this->getDataPath(), $cfg);
    }

    public function fieldIntoConfig(String $path, String $fieldName, Array $array){
        $cfg = $this->api->plugin->readYAML($path);
        $cfg["fields"][$fieldName] = $array;
        $this->api->plugin->writeYAML($path, $cfg);
    }

    public function addWin(String $username, String $game){
        $cfg = $this->getDataConfig();
        $cfg[$game."Wins"][$username] = isset($cfg[$game."Wins"][$username]) ? $cfg[$game."Wins"][$username] + 1 : 0 + 1;
        $this->api->plugin->writeYAML($this->getDataPath(), $cfg);
    }
    
    public function getWins(String $username, String $game){
        $cfg = $this->getDataConfig();
        return isset($cfg[$game."Wins"][$username]) ? $cfg[$game."Wins"][$username] : 0;
    }

    public function createGameConfig($game){
        $path = join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "Minigems", $game.".yml"]);
        new Config($path, CONFIG_YAML, [
            "lobbyTime" => 300
        ]);
        return $path;
    }
}