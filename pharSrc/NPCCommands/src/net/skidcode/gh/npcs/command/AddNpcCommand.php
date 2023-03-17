<?php

namespace net\skidcode\gh\npcs\command;

use Player, net\skidcode\gh\npcs\NPCEntity;

class AddNpcCommand{
    public function __construct($api){
        $this->api = $api;
    }

    public function spawnNPC($cmd, $args, $issuer, $alias){
        if(!$issuer instanceof Player){
            return "Please use this command in-game!";
        }
        $e = $this->api->entity->add($issuer->level, ENTITY_MOB, NPCEntity::TYPE, [
            "x" => $issuer->entity->x,
            "y" => $issuer->entity->y,
            "z" => $issuer->entity->z,
            "command" => implode(" ", $args),
            "modifyByNpcCommands" => true
        ]);
        //$magikClass = new ReflectionClass("Player");
        //$this->magikProperty = $magikClass->getProperty("username");
        //$this->magikProperty->setAccessible(true);
        //$this->magikProperty->setValue($issuer, (mt_rand(0, 1) == 1) ? "RED_".$issuer->username : "BLUE_".$issuer->username);	
        console($e);
        $this->api->entity->spawnToAll($e);
        return "You spawned ".implode(" ", $args);
    }
}