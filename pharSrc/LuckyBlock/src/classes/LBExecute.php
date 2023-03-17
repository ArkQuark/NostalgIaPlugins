<?php
namespace classes;

use BlockAPI, Position, ServerAPI, Utils, Vector3;

class LBExecute{
    
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
    }
    
    public function executeChoice($choice, $data){
        $target = $data["target"];
        $player = $data["player"];
        $structure = new LBStructure($this->api);
        
        $level = $target->level;
        $x = $target->x+.5;
        $y = $target->y;
        $z = $target->z+.5;
        $pos = new Position($x, $y, $z, $level);
        $this->pos = $pos;
        $vector3 = new Vector3(((int)$player->entity->x)+.5, $player->entity->y, ((int)$player->entity->z)+.5);
        
        switch($choice){
                //bad
            case "Harm":
                $player->entity->harm(8, "LuckyBlock", true);
                break;
            case "TNT":
                //console("boom");
                $this->api->entity->summon(new Position($x, $y+1, $z, $level), ENTITY_OBJECT, OBJECT_PRIMEDTNT, ["power" => 3, "fuse => 20"]);
                break;
            case "FallingSand":
                $structure->fallingSand($vector3->x, $vector3->y, $vector3->z, $level);
                break;
            case "RomanticRose":
                $this->drop(ROSE);
                $player->sendChat("LuckyBlock whisper to you: This rose for you!~");
                break;
            case "ObsidianTrap":
                $structure->obsidianTrap($vector3->x, $vector3->y, $vector3->z, $level);
                $player->teleport($vector3, $player->entity->yaw, $player->entity->pitch);
                break;
            case "IronBarSandTrap":
                $structure->ironBarSandTrap($vector3->x, $vector3->y, $vector3->z, $level);
                $player->teleport($vector3, $player->entity->yaw, $player->entity->pitch);
                $this->api->schedule(20, [new LBStructure($this->api), "placeSand"], [$vector3->x, $vector3->y, $vector3->z, $level]);
                break;
            case "CobwebTrap":
                $structure->cobwebTrap($vector3->x, $vector3->y, $vector3->z, $level);
                break;
                
                //common
            case "Tools":
                foreach(range(272, 275) as $item){
                    $this->drop($item, mt_rand(2, 40)/*not a bug now*/, 1, 50);
                }
                break;
            case "LuckyAnimal":
                $type = mt_rand(10, 13);
            case "LuckyMonster":
                if(!isset($type)) $type = mt_rand(32, 36);
                $this->api->entity->summon($pos, ENTITY_MOB, $type, (Utils::chance(5) & $type < 14) ? ["IsBaby" => 1] : []);
                break;
            case "ChainArmor":
                foreach(range(302, 305) as $item){
                    $this->drop($item, 0, 1, 40);
                }
                break;
            case "Seeds":
                foreach([81, 295, 338, 361, 362, 391, 392, 458] as $item){
                    $this->drop($item, 0, mt_rand(1, 3), 25);
                }
                break;
            case "Food":
                foreach([260, 297, 320, 360, 364, 366, 393, 400] as $item){
                    $this->drop($item, 0, mt_rand(1, 3), 25);
                }
                break;
            case "MobDrop":
                foreach([287, 288, 289, 334, 352] as $item){
                    $this->drop($item, 0, mt_rand(2, 10), 30);
                }
                $this->drop(341, 0, 1, 5);
                break;
            case "WoodStuff":
                $this->drop(WOOD, mt_rand(0, 2), mt_rand(3, 10));
                $this->drop(PLANKS, mt_rand(0, 2), mt_rand(5, 19));
                $this->drop(STICK, 0, mt_rand(2, 8), 50);
                break;
            case "StoneStuff":
                $this->drop(STONE, 0, mt_rand(3, 15));
                $this->drop(COBBLESTONE, 0, mt_rand(5, 18));
                break;
                
                //uncommon
            case "BonusChest":
                (new LBBonusChest())->chestGenerate($target, $this->api);
                break;
            case "Ingots":
                $this->drop(IRON_INGOT, 0, mt_rand(1, 8));
                $this->drop(GOLD_INGOT, 0, mt_rand(1, 8));
                break;
            case "IronArmor":
                foreach(range(306, 309) as $item){
                    $this->drop($item, 0, 1, 40);
                }
                break;
            case "NetherStuff":
                $data = [39, 40, 89, 112, 155, 348, 405, 406];
                $this->drop(NETHERRACK, 0, mt_rand(4, 16));
                foreach($data as $item){
                    $this->drop($item, 0, mt_rand(1, 6), 30);
                }
                break;
            case "TropicalStuff":
                $this->drop(SAPLING, 3, mt_rand(1, 4), 40);
                $this->drop(WOOD, 3, mt_rand(3, 18), 40);
                $this->drop(LEAVES, 3, mt_rand(2, 10), 40);
                $this->drop(PLANKS, 3, mt_rand(6, 20), 40);
                $this->drop(JUNGLE_WOOD_STAIRS, 0, mt_rand(3, 10), 30);
                $this->drop(WOOD_SLAB, 3, mt_rand(4, 16), 30);
                break;
            case "Carpet":
                foreach(range(0, 15) as $meta){
                    $this->drop(CARPET, $meta, mt_rand(1, 6), 10);
                }
                break;
            case "Cake":
                $this->drop(CAKE_BLOCK);
                break;
                
                //rare
            case "DiamondPickaxe":
                $this->drop(DIAMOND_PICKAXE);
                break;
            case "Diamonds":
                $this->drop(DIAMOND, 0, mt_rand(0, 5));
                break;
            case "GlowingObsidian":
                $this->drop(GLOWING_OBSIDIAN, 0, mt_rand(2, 8));
                break;
            case "OreStructure":
                $structure->oreStructure($x, $y, $z, $level);
                break;
                
                //legendary
            case "IllegalStuff":
                $drop = 0;
                if(Utils::chance(40)){
                    $this->drop(INFO_UPDATE, 0, mt_rand(1, 3));
                    ++$drop;
                }
                if(Utils::chance(40)){
                    $this->drop(247, 2, 1);
                    ++$drop;
                }
                if(Utils::chance(40)){
                    $data = range(10, 13) + range(32, 36);
                    foreach($data as $meta){
                        $this->drop(SPAWN_EGG, $meta, mt_rand(1, 3), 40);
                    }
                    ++$drop;
                }
                if($drop === 0) $this->executeChoice("IllegalStuff", $data);
                break;
                /*case "LuckySword":
                 $player->sendChat("Sadly... But u cannot change a name of the item. Not WIP");
                 $item = new GoldenSwordItem();
                 $api->entity->drop($pos, $item);
                 break;*/
            case "RainbowPillar":
                $structure->rainbowPillar($x, $y, $z, $level);
                break;
                
            default:
                console("Undefined choice: $choice!");
                break;
        }
    }
    
    public function drop($id, $meta = 0, $count = 1, $chance = 100){
        if(Utils::chance($chance)) $this->api->entity->drop($this->pos, BlockAPI::getItem($id, $meta, $count));
    }
}