<?php

namespace de\tvorok\minigames;

use de\tvorok\minigames\games\Spleef;
use de\tvorok\minigames\games\TNTRun;
use de\tvorok\minigames\games\ObstacleRace;
use Player;
use Plugin;
use Position;
use ServerAPI;

class MGmain implements Plugin{
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
    }

    public function init(){
        $this->mgConfig = new MGconfig($this->api);
        $this->mgConfig->createConfig();
        $this->config = $this->mgConfig->getMainConfig();
        $this->mgConfig->createDataConfig();

        $this->api->addHandler("player.join", [$this, "handler"]);
        $this->api->addHandler("player.block.break", [$this, "handler"]);
        $this->api->addHandler("player.death", [$this, "handler"]);
        //$this->api->addHandler("player.touch", [$this, "handler"]);
        $this->api->addHandler("player.quit", [$this, "handler"]);
        //$this->api->addHandler("player.respawn", [$this, "handler"]);
        $this->api->addHandler("player.interact", [$this, "handler"]);
        $this->api->addHandler("player.block.place", [$this, "handler"]);
        $this->api->addHandler("player.offline.get", [$this, "handler"]);
        $this->api->addHandler("player.move", [$this, "handler"]);
        $this->api->addHandler("entity.health.change", [$this, "handler"]);
        //$this->api->addHandler("console.command", [$this, "handler"]);

        $this->api->addHandler("hub.teleport", [$this, "handler"]);

        $this->api->console->register("hub", "", [$this, "commandHub"]);
        $this->api->ban->cmdWhitelist("hub");
        $this->api->console->register("sethub", "", [$this, "commandSetHub"]);

        $this->games = [];
        if($this->config["pluginEnable"] and isset($this->config["hub"])){
            $this->runGames();
        }
        else{
            console(FORMAT_RED."You need a hub to run games!!".FORMAT_RESET);
        }
    }

    public function commandHub($cmd, $args, $issuer, $alias){
        if(!($issuer instanceof Player)){
            return "Please run this command in-game!";
        }
        $this->api->dhandle("hub.teleport", ["player" => $issuer]);
        return;
    }
    
    public function commandSetHub($cmd, $args, $issuer, $alias){
        $pos = (new MGplayer($this->api))->posCommand($issuer->entity);
        $this->mgConfig->addHub($pos + ["level" => $issuer->entity->level->getName()]);
        $this->runGames();
        return "/hub seted";
    }
        

    public function runGames(){
        if(count($this->games) == 0){
            $this->spleefGame();
            $this->tntrunGame();
            $this->obstacleraceGame();
        }
    }

    public function spleefGame(){
        if($this->config["spleefEnable"]){
            $this->games["Spleef"] = new Spleef($this->api);

            $this->api->console->register("spleef", "", [$this->games["Spleef"], "command"]);
            $this->api->ban->cmdWhitelist("spleef");
            console("Spleef enabled");
        }
    }

    public function tntrunGame(){
        if($this->config["tntrunEnable"]){
            $this->games["TNTRun"] = new TNTRun($this->api);
    
            $this->api->console->register("tntrun", "", [$this->games["TNTRun"], "command"]);
            $this->api->ban->cmdWhitelist("tntrun");
            console("TNTRun enabled");
        }
    }
    
    public function obstacleraceGame(){
        if($this->config["obstacleraceEnable"]){
            $this->games["ObstacleRace"] = new ObstacleRace($this->api);
            
            $this->api->console->register("race", "", [$this->games["ObstacleRace"], "command"]);
            $this->api->ban->cmdWhitelist("race");
            console("ObstacleRace enabled");
        }
    }

    public function handler($data, $event){
        if(!$this->config["pluginEnable"] or count($this->games) < 1){
            return;
        }
      	if($event == "hub.teleport"){
      		$player = $data["player"];
      		if(!isset($this->config["hub"])){
      			return "/hub not exists!";
      		}
      		$hub = $this->config["hub"];
      		$issuer->teleport(new Position($hub[0], $hub[1], $hub[2], $this->api->level->get($hub["level"])));
      		return "You've been teleported to hub!";
      	}
        if($event == "player.join"){
            if(!isset($this->config["hub"])){
                return;
            }
            $hub = $this->config["hub"];
            $data->setSpawn(new Position($hub[0], $hub[1], $hub[2], $this->api->level->get($hub["level"])));
            return;
        }
        elseif($event == "player.offline.get"){
            if(isset($this->config["hub"])){
                $hub = $this->config["hub"];
                $data->set("position", [
                    "x" => $hub[0], 
                    "y" => $hub[1], 
                    "z" => $hub[2], 
                    "level" => $hub["level"]
                ]);
            }
            return;
        }
        elseif($event == "entity.health.change"){
            if($data["entity"]->isPlayer() and $data["cause"] == "fall"){
                return false;
            }
            return true;
        }
        foreach($this->games as $game){
            $bool = $game->handler($data, $event) ?? true;
        }
        return $bool;
    }

    public function __destruct(){
        //wow this is needable
    }

    public static function formatTime(int $sec){
        $h = floor($sec / 3600);
        $m = floor($sec / 60);
        $s = $sec % 60;
        $msg = "";

        if($h === 1) $msg .= "1 hour ";
        elseif($h > 1) $msg .= "$h hours ";

        if($m === 1) $msg .= "1 minute ";
        elseif($m > 1) $msg .= "$m minutes ";

        if($s === 1) $msg .= "1 second";
        elseif($s > 1) $msg .= "$s seconds";
        return trim($msg);
    }

    public static function randPos($array){
        $x = $array[0];
        $z = $array[1];
        sort($x);
        sort($z);
        return [mt_rand($x[0], $x[1]), mt_rand($z[0], $z[1])];
    }
    
    public static function getAvailableFields(Array $fields){
        $list = [];
        foreach($fields as $fieldName => $array){
            if($array["status"] === false){
                array_push($list, $fieldName);
            }
        }
        return $list;
    }
    
    public static function playerInField(String $username, $fields){
        if(!$fields){
            return false;
        }
        foreach($fields as $fieldName => $array){
            if(in_array($username, $fields[$fieldName]["players"])){
                return $fields[$fieldName]["name"];
            }
        }
        return false;
    }
}