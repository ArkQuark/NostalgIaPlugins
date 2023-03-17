<?php
namespace classes;

use Plugin, ServerAPI, Config, Vector3, Position, LiquidBlock, SpongeBlock, AirBlock;

class LBmain implements Plugin{

    public function __construct(ServerAPI $api, $server = false){
        $this->server = ServerAPI::request();
        $this->api = $api;
    }
    
    public function init(){
        $this->createConfig();
        $this->api->schedule(0, [$this, "scheduleSpawn"], [], false);
        $this->api->addHandler("player.block.break", [$this, "handle"]);
    }
    
    public function handle(&$data, $event){
        if(!$this->config["enablePlugin"]) return;
        $player = $data['player'];
        $target = $data['target'];
        if($target->getID() === SPONGE){
            if($player->getSlot($player->slot)->isHoe()) return true;
            
            $rand = (new LBRandom($this->api))->randomChoice();
            if($this->config["openAnnounce"]) $this->api->chat->broadcast(" - $player открыл LuckyBlock и ему выпало [".ucfirst($rand[1])."] ".$rand[0]." - ");
            $target->level->setBlock(new Vector3($target->x, $target->y, $target->z), new AirBlock(), true);
            $this->api->block->blockUpdate(new Position($target->x, $target->y, $target->z, $target->level));
            (new LBExecute($this->api))->executeChoice($rand[0], $data);
            return false;
        }
    }
    
    public function scheduleSpawn(){
        if($this->config["enableSpawn"]) $this->api->schedule($this->config["spawnTime"]*20, [$this, "spawnLuckyBlock"], [], true);
    }
    
    public function spawnLuckyBlock(){
        $o = $this->api->player->getAll();
        if(count($o) == 0) return;//Don't spawn if noplayers on server
        
        $randomX = mt_rand(1, 255);
        $randomZ = mt_rand(1, 255);
        $level = $this->api->level->getDefault();
        
        for($y = 127; $y > 0; --$y){//get highest block
            $block = $level->getBlock(new Vector3($randomX, $y, $randomZ));
            $blockID = $block->getID();
            if($blockID !== 0){
                if($blockID == 18 or $blockID == 78 or $blockID == 31){//Ignore Leaves, Snow Layer, Tall Grass
                    continue;
                }
                break;
            }
        }
        
        $block = $level->getBlock(new Vector3($randomX, $y, $randomZ));
        if($block instanceof LiquidBlock or $block->isFullBlock == false){//Don't spawn above liquid or nonfull Blocks
            $this->spawnLuckyBlock();
            return;
        }
        $y++;
        if($y >= 128) $y = 127;
        
        $level->setBlock(new Vector3($randomX, $y, $randomZ), new SpongeBlock());
        //console("LuckyBlock spawned in $randomX, $y, $randomZ");
        if($this->config["dropAnnounce"]){
            foreach($o as $player){
                $player->sendChat("LuckyBlock упал на карте, отыщите его!");
            }
        }
    }
    
    public function createConfig(){
        $path = join(DIRECTORY_SEPARATOR, [DATA_PATH."plugins", "configs", ""]);
        if(!file_exists($path)){
            mkdir($path, 0777);
        }
        $this->configFile = new Config($path."AIO.yml", CONFIG_YAML, [
            "info" => "All in one config",
            "LuckyBlock" => [
                "spawnTime" => 300,
                "enableSpawn" => true,
                "enablePlugin" => true,
                "dropAnnounce" => true,
                "openAnnounce" => true
            ]
        ]);
        $this->config = $this->configFile->get("LuckyBlock");
    }
}