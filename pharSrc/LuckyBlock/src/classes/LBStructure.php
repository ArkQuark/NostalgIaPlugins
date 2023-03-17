<?php
namespace classes;

use ServerAPI, Utils, Vector3, Position, BlockAPI;

class LBStructure{
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
    }
    
    public function oreStructure($x, $y, $z, $level){
        $data = [
            0 => [
                [$x-2, $z-2, 40], [$x-2, $z-1, 40], [$x-2, $z, 40], [$x-2, $z+1, 40], [$x-2, $z+2, 40],
                [$x-1, $z-2, 40], [$x-1, $z-1], [$x-1, $z], [$x-1, $z+1], [$x-1, $z+2, 40],
                [$x, $z-2, 40], [$x, $z-1], [$x, $z], [$x, $z+1], [$x, $z+2, 40],
                [$x+1, $z-2, 40], [$x+1, $z-1], [$x+1, $z], [$x+1, $z+1], [$x+1, $z+2, 40],
                [$x+2, $z-2, 40], [$x+2, $z-1, 40], [$x+2, $z, 40], [$x+2, $z+1, 40], [$x+2, $z+2, 40]
            ],
            1 => [
                [$x-2, $z-2, 15], [$x-2, $z-1, 15], [$x-2, $z, 15], [$x-2, $z+1, 15], [$x-2, $z+2, 15],
                [$x-1, $z-2, 15], [$x-1, $z-1], [$x-1, $z], [$x-1, $z+1], [$x-1, $z+2, 15],
                [$x, $z-2, 15], [$x, $z-1], [$x, $z], [$x, $z+1], [$x, $z+2, 15],
                [$x+1, $z-2, 15], [$x+1, $z-1], [$x+1, $z], [$x+1, $z+1], [$x+1, $z+2, 15],
                [$x+2, $z-2, 15], [$x+2, $z-1, 15], [$x+2, $z, 15], [$x+2, $z+1, 15], [$x+2, $z+2, 15]
            ],
            2 => [
                [$x-1, $z-1, 50], [$x-1, $z, 75], [$x-1, $z+1, 50],
                [$x, $z-1, 75], [$x, $z], [$x, $z+1, 75],
                [$x+1, $z-1, 50], [$x+1, $z, 75], [$x+1, $z+1, 50]
            ],
            3 => [
                [$x-1, $z-1, 40], [$x-1, $z, 50], [$x-1, $z+1, 40],
                [$x, $z-1, 50], [$x, $z], [$x, $z+1, 50],
                [$x+1, $z-1, 40], [$x+1, $z, 50], [$x+1, $z+1, 40]
            ],
            4 => [
                [$x-1, $z, 25],
                [$x, $z-1, 25], [$x, $z, 50], [$x, $z+1, 25],
                [$x+1, $z, 25]
            ],
            5 => [
                [$x, $z, 25]
            ]
        ];
        
        foreach($data as $addY => $array){
            $blockY = $y + $addY;
            foreach($array as $block){
                if(!isset($block[2])) $block[2] = 100;
                $ore = $this->randOre();
                if(Utils::chance($block[2])){
                    if($blockY == $y){
                        $level->setBlock(new Vector3($block[0], $blockY, $block[1]), BlockAPI::get($ore));
                        $this->api->block->blockUpdate(new Position($block[0], $blockY, $block[1], $level));
                    }
                    elseif($level->getBlock(new Vector3($block[0], $blockY-1, $block[1]))->getID() != AIR){
                        $level->setBlock(new Vector3($block[0], $blockY, $block[1]), BlockAPI::get($ore));
                        $this->api->block->blockUpdate(new Position($block[0], $blockY, $block[1], $level));
                    }
                }
            }
        }
    }
    
    public function randOre(){
        $rand = Utils::randomFloat() * 100;
        if($rand <= 10) return DIAMOND_ORE;
        elseif($rand <= 25) return LAPIS_ORE;
        elseif($rand <= 40) return REDSTONE_ORE;
        elseif($rand <= 60) return GOLD_ORE;
        elseif($rand <= 80) return IRON_ORE;
        else return COAL_ORE;
    }
    
    public function cobwebTrap($x, $y, $z, $level){
        $data = [
            [$x-1, $y, $z-1], [$x-1, $y, $z], [$x-1, $y, $z+1],
            [$x, $y, $z-1], [$x, $y, $z], [$x, $y, $z+1],
            [$x+1, $y, $z-1], [$x+1, $y, $z], [$x+1, $y, $z+1],
            
            [$x-1, $y+1, $z-1], [$x-1, $y+1, $z], [$x-1, $y+1, $z+1],
            [$x, $y+1, $z-1], [$x, $y+1, $z], [$x, $y+1, $z+1],
            [$x+1, $y+1, $z-1], [$x+1, $y+1, $z], [$x+1, $y+1, $z+1],
            
            [$x-1, $y+2, $z-1], [$x-1, $y+2, $z], [$x-1, $y+2, $z+1],
            [$x, $y+2, $z-1], [$x, $y+2, $z], [$x, $y+2, $z+1],
            [$x+1, $y+2, $z-1], [$x+1, $y+2, $z], [$x+1, $y+2, $z+1]
        ];
        
        foreach($data as $block){
            $level->setBlock(new Vector3($block[0], $block[1], $block[2]), BlockAPI::get(COBWEB), true);
        }
    }
    
    public function ironBarSandTrap($x, $y, $z, $level){
        $data = [
            [$x-1, $y-1, $z-1, STONE_BRICKS], [$x-1, $y-1, $z, STONE_BRICKS], [$x-1, $y-1, $z+1, STONE_BRICKS],
            [$x, $y-1, $z-1, STONE_BRICKS], [$x, $y-1, $z, STONE_BRICKS], [$x, $y-1, $z+1, STONE_BRICKS],
            [$x+1, $y-1, $z-1, STONE_BRICKS], [$x+1, $y-1, $z, STONE_BRICKS], [$x+1, $y-1, $z+1, STONE_BRICKS],
            
            [$x-1, $y, $z-1, IRON_BAR], [$x-1, $y, $z, IRON_BAR], [$x-1, $y, $z+1, IRON_BAR],
            [$x, $y, $z-1, IRON_BAR], [$x, $y, $z, AIR], [$x, $y, $z+1, IRON_BAR],
            [$x+1, $y, $z-1, IRON_BAR], [$x+1, $y, $z, IRON_BAR], [$x+1, $y, $z+1, IRON_BAR],
            
            [$x-1, $y+1, $z-1, IRON_BAR], [$x-1, $y+1, $z, IRON_BAR], [$x-1, $y+1, $z+1, IRON_BAR],
            [$x, $y+1, $z-1, IRON_BAR], [$x, $y+1, $z, AIR], [$x, $y+1, $z+1, IRON_BAR],
            [$x+1, $y+1, $z-1, IRON_BAR], [$x+1, $y+1, $z, IRON_BAR], [$x+1, $y+1, $z+1, IRON_BAR],
            
            [$x-1, $y+2, $z-1, IRON_BAR], [$x-1, $y+2, $z, IRON_BAR], [$x-1, $y+2, $z+1, IRON_BAR],
            [$x, $y+2, $z-1, IRON_BAR], [$x, $y+2, $z, AIR], [$x, $y+2, $z+1, IRON_BAR],
            [$x+1, $y+2, $z-1, IRON_BAR], [$x+1, $y+2, $z, IRON_BAR], [$x+1, $y+2, $z+1, IRON_BAR],
            
            [$x-1, $y+3, $z-1, IRON_BAR], [$x-1, $y+3, $z, IRON_BAR], [$x-1, $y+3, $z+1, IRON_BAR],
            [$x, $y+3, $z-1, IRON_BAR], [$x, $y+3, $z, AIR], [$x, $y+3, $z+1, IRON_BAR],
            [$x+1, $y+3, $z-1, IRON_BAR], [$x+1, $y+3, $z, IRON_BAR], [$x+1, $y+3, $z+1, IRON_BAR]
        ];
        
        foreach($data as $block){
            $level->setBlock(new Vector3($block[0], $block[1], $block[2]), BlockAPI::get($block[3]), true);
        }
        
    }
    
    public function obsidianTrap($x, $y, $z, $level){
        $data = [
            [$x-1, $y-1, $z-1, OBSIDIAN], [$x-1, $y-1, $z, OBSIDIAN], [$x-1, $y-1, $z+1, OBSIDIAN],
            [$x, $y-1, $z-1, OBSIDIAN], [$x, $y-1, $z, OBSIDIAN], [$x, $y-1, $z+1, OBSIDIAN],
            [$x+1, $y-1, $z-1, OBSIDIAN], [$x+1, $y-1, $z, OBSIDIAN], [$x+1, $y-1, $z+1, OBSIDIAN],
            
            [$x-1, $y, $z-1, OBSIDIAN], [$x-1, $y, $z, OBSIDIAN], [$x-1, $y, $z+1, OBSIDIAN],
            [$x, $y, $z-1, OBSIDIAN], [$x, $y, $z, WATER], [$x, $y, $z+1, OBSIDIAN],
            [$x+1, $y, $z-1, OBSIDIAN], [$x+1, $y, $z, OBSIDIAN], [$x+1, $y, $z+1, OBSIDIAN],
            
            [$x-1, $y+1, $z-1, OBSIDIAN], [$x-1, $y+1, $z, GLASS], [$x-1, $y+1, $z+1, OBSIDIAN],
            [$x, $y+1, $z-1, GLASS], [$x, $y+1, $z, WATER], [$x, $y+1, $z+1, GLASS],
            [$x+1, $y+1, $z-1, OBSIDIAN], [$x+1, $y+1, $z, GLASS], [$x+1, $y+1, $z+1, OBSIDIAN],
            
            [$x-1, $y+2, $z-1, OBSIDIAN], [$x-1, $y+2, $z, OBSIDIAN], [$x-1, $y+2, $z+1, OBSIDIAN],
            [$x, $y+2, $z-1, OBSIDIAN], [$x, $y+2, $z, OBSIDIAN], [$x, $y+2, $z+1, OBSIDIAN],
            [$x+1, $y+2, $z-1, OBSIDIAN], [$x+1, $y+2, $z, OBSIDIAN], [$x+1, $y+2, $z+1, OBSIDIAN]
        ];
        
        foreach($data as $block){
            $level->setBlock(new Vector3($block[0], $block[1], $block[2]), BlockAPI::get($block[3]), true);
        }
    }
    
    public function rainbowPillar($x, $y, $z, $level){
        //1,4,5,3,11,2,6,14,diamond,fire
        $data = [["x" => $x, "y" => $y+5, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+6, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+7, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+8, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+9, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+10, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+11, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+12, "z" => $z, "Tile" => 35], ["x" => $x, "y" => $y+13, "z" => $z, "Tile" => DIAMOND_BLOCK], ["x" => $x, "y" => $y+14, "z" => $z, "Tile" => FIRE]];
        $this->api->schedule(5, [$this, "fallingWool"], [$level->getName(), $data[0]]);
        $this->api->schedule(30, [$this, "dyeWool"], [$level, $x, $y, $z, 1]);
        $this->api->schedule(10, [$this, "fallingWool"], [$level->getName(), $data[1]]);
        $this->api->schedule(35, [$this, "dyeWool"], [$level, $x, $y+1, $z, 4]);
        $this->api->schedule(15, [$this, "fallingWool"], [$level->getName(), $data[2]]);
        $this->api->schedule(40, [$this, "dyeWool"], [$level, $x, $y+2, $z, 5]);
        $this->api->schedule(20, [$this, "fallingWool"], [$level->getName(), $data[3]]);
        $this->api->schedule(45, [$this, "dyeWool"], [$level, $x, $y+3, $z, 3]);
        $this->api->schedule(25, [$this, "fallingWool"], [$level->getName(), $data[4]]);
        $this->api->schedule(50, [$this, "dyeWool"], [$level, $x, $y+4, $z, 11]);
        $this->api->schedule(30, [$this, "fallingWool"], [$level->getName(), $data[5]]);
        $this->api->schedule(55, [$this, "dyeWool"], [$level, $x, $y+5, $z, 2]);
        $this->api->schedule(35, [$this, "fallingWool"], [$level->getName(), $data[6]]);
        $this->api->schedule(60, [$this, "dyeWool"], [$level, $x, $y+6, $z, 6]);
        //$this->api->schedule(40, [$this, "fallingWool"], [$level->getName(), $data[7]]);
        $this->api->schedule(65, [$this, "dyeWool"], [$level, $x, $y+7, $z, 14]);
        
        $this->api->schedule(45, [$this, "fallingWool"], [$level->getName(), $data[8]]);
        $this->api->schedule(50, [$this, "fallingWool"], [$level->getName(), $data[9]]);
    }
    
    public function fallingSand($x, $y, $z, $level){
        $data = [
            ["x" => $x, "y" => $y+6, "z" => $z-1], ["x" => $x, "y" => $y+7, "z" => $z-1], ["x" => $x, "y" => $y+8, "z" => $z-1],
            ["x" => $x-1, "y" => $y+6, "z" => $z-1], ["x" => $x-1, "y" => $y+7, "z" => $z-1], ["x" => $x-1, "y" => $y+8, "z" => $z-1],
            ["x" => $x+1, "y" => $y+6, "z" => $z-1], ["x" => $x+1, "y" => $y+7, "z" => $z-1], ["x" => $x+1, "y" => $y+8, "z" => $z-1],
            ["x" => $x, "y" => $y+6, "z" => $z], ["x" => $x, "y" => $y+7, "z" => $z], ["x" => $x, "y" => $y+8, "z" => $z],
            ["x" => $x-1, "y" => $y+6, "z" => $z], ["x" => $x-1, "y" => $y+7, "z" => $z], ["x" => $x-1, "y" => $y+8, "z" => $z],
            ["x" => $x+1, "y" => $y+6, "z" => $z], ["x" => $x+1, "y" => $y+7, "z" => $z], ["x" => $x+1, "y" => $y+8, "z" => $z],
            ["x" => $x, "y" => $y+6, "z" => $z+1], ["x" => $x, "y" => $y+7, "z" => $z+1], ["x" => $x, "y" => $y+8, "z" => $z+1],
            ["x" => $x-1, "y" => $y+6, "z" => $z+1], ["x" => $x-1, "y" => $y+7, "z" => $z+1], ["x" => $x-1, "y" => $y+8, "z" => $z+1],
            ["x" => $x+1, "y" => $y+6, "z" => $z+1], ["x" => $x+1, "y" => $y+7, "z" => $z+1], ["x" => $x+1, "y" => $y+8, "z" => $z+1]
        ];
        foreach($data as $d){
             $this->api->entity->summon(new Position($d["x"], $d["y"], $d["z"], $level), ENTITY_FALLING, FALLING_SAND, ["Tile" => SAND]);
        }
    }
    
    public function placeSand($data){
        $x = $data[0];
        $y = $data[1];
        $z = $data[2];
        $level = $data[3];
        $data = [
            ["x" => $x, "y" => $y+6, "z" => $z], ["x" => $x, "y" => $y+7, "z" => $z], ["x" => $x, "y" => $y+8, "z" => $z]
        ];
        foreach($data as $d){
            $this->api->entity->summon(new Position($d["x"], $d["y"], $d["z"], $level), ENTITY_FALLING, FALLING_SAND, ["Tile" => SAND]);
        }
    }
    
    public function fallingWool($data){
        $level = $this->api->level->get($data[0]);
        $array = $data[1];
        $this->api->entity->summon(new Position($array["x"], $array["y"], $array["z"], $level), ENTITY_FALLING, FALLING_SAND, ["Tile" => $array["Tile"]]);
    }
    
    public function dyeWool($data){
        $level = $data[0];
        $x = $data[1];
        $y = $data[2];
        $z = $data[3];
        $meta = $data[4];
        
        $level->setBlock(new Vector3($x, $y, $z), BlockAPI::get(WOOL, $meta), true);
        $this->api->block->blockUpdate(new Position($x, $y, $z, $level));
    }
}