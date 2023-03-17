<?php
namespace classes;

use ServerAPI, Utils;

class LBRandom{
    
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
        $this->bad = ["Harm", "TNT", "FallingSand", "RomanticRose", "ObsidianTrap", "IronBarSandTrap", "CobwebTrap"]; //no todo
        $this->common = ["Tools", "LuckyAnimal", "LuckyMonster", "ChainArmor", "Seeds", "Food", "MobDrop", "WoodStuff", "StoneStuff"]; //no todo
        $this->uncommon = ["BonusChest", "Ingots", "IronArmor", "NetherStuff", "Carpet", "Cake"]; //"Spawner"
        $this->rare = ["DiamondPickaxe", "Diamonds", "GlowingObsidian", "OreStructure"]; //"WishingWell"
        $this->legendary = ["IllegalStuff", "RainbowPillar"]; //"LuckySword(no todo)"
    }
    
    public function randomChoice(){
        //return ["IllegalStuff", "Test"];
        $randRarity = $this->randomRarity();
        switch($randRarity){
            case "bad":
                return [array_rand(array_flip($this->bad)), "bad"];
            case "common":
                return [array_rand(array_flip($this->common)), "common"];
            case "uncommon":
                return [array_rand(array_flip($this->uncommon)), "uncommon"];
            case "rare":
                return [array_rand(array_flip($this->rare)), "rare"];
            case "legendary":
                return [array_rand(array_flip($this->legendary)), "legendary"];
        }
    }
    
    public function randomRarity(){
        //return "bad";
        $rand = Utils::randomFloat() * 100;
        if($rand <= 20) return "bad"; //20 = 20%
        elseif($rand <= 65) return "common";//65 = 45%
        elseif($rand <= 85) return "uncommon"; //85 = 20%
        elseif($rand <= 95) return "rare"; //95 = 10%
        else return "legendary"; // = 5%
    }
}