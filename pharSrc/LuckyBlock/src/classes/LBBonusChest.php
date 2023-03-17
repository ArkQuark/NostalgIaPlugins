<?php
namespace classes;

use Utils, ChestBlock, Position, BlockAPI;

class LBBonusChest{
    public $chestLoot = [
        "cobblestone" => [
            "min-count" => 2,
            "max-count" => 16,
            "chance" => 40
        ],
        "wooden_planks" => [
            "min-count" => 4,
            "max-count" => 16,
            "chance" => 40
        ],
        "apple" => [
            "max-count" => 3,
            "chance" => 59
        ],
        "bread" => [
            "max-count" => 3,
            "chance" => 59
        ],
        "iron_ingot" => [
            "max-count" => 5,
            "chance" => 45
        ],
        "iron_sword" => [
            "chance" => 20
        ],
        "sapling" => [
            "meta" => "random 0 2",
            "min-count" => 3,
            "max-count" => 7,
            "chance" => 25
        ],
        "gold_ingot" => [
            "max-count" => 3,
            "chance" => 25
        ],
        "bucket" => [
            "chance" => 19
        ],
        "clay" => [
            "chance" => 15
        ],
        "glowstone_dust" => [
            "max-count" => 5,
            "chance" => 15
        ],
        "dye" => [
            "max-count" => 4,
            "chance" => 30
        ],
        "cake" => [
            "chance" => 10
        ],
    ];
    
    public function chestLootList(){
        $lootList = [];
        foreach($this->chestLoot as $id => $array){
            $lootList[$id] = $array;
        }
        return $lootList;
    }
    
    public function parseMeta($meta){
        if(!is_numeric($meta)){
            $arrmeta = explode(" ", $meta);
            if($arrmeta[0] === "random"){
                $meta = mt_rand($arrmeta[1], $arrmeta[2]);
            }else{
                $meta = 0; //undefined
            }
        }
        return $meta;
    }
    
    public function chestRandLoot(){
        $loot = $this->chestLootList();
        $chest = [];
        
        foreach($loot as $id => $lootArray){
            if(Utils::chance($lootArray['chance'])){
                if(!isset($lootArray['meta'])) $chest[$id]['meta'] = 0;
                else{
                    $meta = $this->parseMeta($lootArray['meta']);
                    $chest[$id]['meta'] = $meta;
                }
                
                if(!isset($lootArray['min-count']) and !isset($lootArray['max-count'])) $chest[$id]['count'] = 1;
                elseif(!isset($lootArray['min-count']) and isset($lootArray['max-count'])) $chest[$id]['count'] = mt_rand(1, $lootArray['max-count']);
                else $chest[$id]['count'] = mt_rand($lootArray['min-count'], $lootArray['max-count']);
                
                $slots = range(0, 26);
                foreach($slots as $key){//random slot
                    $tempSlot = mt_rand(0, 26);
                    if($tempSlot == $slots[$key]){
                        $tempSlot == mt_rand(0, 26);
                    }
                    else{
                        array_push($slots, $tempSlot);
                    }
                    $chest[$id]['slot'] = $tempSlot;
                }
            }
        }
        //console(var_dump($chest));
        return $chest;
    }
    
    public function chestGenerate($target, $api){
        //console(FORMAT_AQUA.'Generating BonusChest'.FORMAT_RESET);
        $pos = new Position($target->x, $target->y, $target->z, $target->level);
        $target->level->setBlock($pos, new ChestBlock(), true);
        $tile = $api->tile->add($target->level, TILE_CHEST, $pos->x, $pos->y, $pos->z, [
            "Items" => [],
            "id" => TILE_CHEST,
            "x" => $pos->x,
            "y" => $pos->y,
            "z" => $pos->z
        ]);
        $item = BlockAPI::getItem(0, 0, 1);
        for($slot = 0; $slot <= 26; $slot++){
            $tile->setSlot($slot, $item);
        }
        
        $loot = $this->chestRandLoot();
        //console('generating loot for BonusChest);
        foreach($loot as $itemID => $array){
            $id = constant(strtoupper($itemID));
            $item = BlockAPI::getItem($id, $array['meta'], $array['count']);
            $tile->setSlot($array['slot'], $item);
            //console('id: '.$itemID.' meta:'.$array['meta'].' count: '.$array['count'].' slot: '.$array['slot']);
        }
    }
}