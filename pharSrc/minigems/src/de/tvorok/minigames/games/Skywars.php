<?php
//todo add refill chest, random chest, change commands, add kits and some money value(?), change gameconfig, lobby tips, player xp, k/d and e.t.c, spawnpoints for field and tp to them
namespace de\tvorok\minigames\games;

use ServerAPI;
use Vector3;

class Skywars extends MGdummyGame{
    public function __construct(ServerAPI $api, $gameName = "Skywars"){
        parent::__construct($api, $gameName);
        //config add gametime
    }
    
    public function playerDeath($data, $hData){
        $status = $hData["status"];
        if($status === "game" or $status === "deathmatch"){
            $this->loserProcess($data, "player.death", $hData["field"]->getName());
            $this->mgPlayer->broadcastForField($hData["field"], $hData["user"]." dead.");
            //add some souls(?) to killer and change msg
        }
    }
    
    public function playerMove($data, $hData){
        $status = $hData["status"];
        if($status === "game" or $status === "deathmatch"){
            $downBlock = $data->level->getBlock(new Vector3($data->x, $data->y-1, $data->z));
            if($downBlock->getID() === 95){//invisible bedrock
                //kill
            }
        }
    }
    
    public function entityHealthChange($data, $hData){
        $status = $hData["status"];
        if($status !== "game" or $status !== "deathmatch"){
            return false;
        }
        return true;
    }
    
    public function playerInteract($data, $hData){
        $status = $hData["status"];
        if($status === "game" or $status === "deathmatch"){
            return true;
        }
        return false;
    }
    
    public function playerBlockPlace($data, $hData){
        $status = $hData["status"];
        if($status === "invisible" or $status === "game" or $status === "deathmatch"){
            return true;
        }
        return false;
    }
    
    public function playerBlockBreak($data, $hData){
        $status = $hData["status"];
        if($status === "invisible" or $status === "game" or $status === "deathmatch"){
            return true;
        }
        return false;
    }
    
    //stages
    public function checkForStart($field){
        $players = $field->getPlayers();
        if(count($players) < 1){
            $this->mgPlayer->broadcastForField($field, $this->gameName." cannot run, need 2 players!");
            $this->restoreField($field); //todo schedule??
            return false;
        }
        else{
            $this->invincible($field);
            return true;
        }
    }
    
    public function invincible($field){
        $this->cleanDropItems($field->getLevel());
        $field->setStatus("invisible");
        $this->api->schedule(30 * 20, [$this, "game"], $field);
        $field->timer(30, "Invincibility wears off in");
    }
    
    public function deathMatch($field){
        $field->setStatus("deathmatch");
    }
    
    public function cleanDropItems($level){
        $entities = $this->api->entity->getAll($level);
        if(count($entities) == 0){
            return;
        }
        foreach($entities as $e){
            if($e->class === ENTITY_ITEM){
                $e->close();
            }
        }
    }
}