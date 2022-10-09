<?php

/*
__PocketMine Plugin__
name=LuckyBlock
description=New LuckyBlock plugin
version=1.1.0
author=ArkQuark
class=LBmain
apiversion=12.1
*/

class LBmain implements Plugin{
	
    public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
		$this->api = $api;
	}
	
	public function init(){
		$this->config = new Config($this->api->plugin->configPath($this)."config.yml", CONFIG_YAML, [
			"//in seconds",
			"spawnTime" => 300,
			"enableSpawn" => true,
			"enablePlugin" => true,
			"dropAnnounce" => true,
			"openAnnounce" => true
		]);
		$this->api->schedule(0, [$this, "scheduleSpawn"], [], false);
		$this->api->addHandler("player.block.break", array($this, "handle"), 5);
	}

	public function handle(&$data, $event){
		if(!$this->config->get("enablePlugin")) return;
		$player = $data['player'];
		$target = $data['target'];
		if($target->getID() === SPONGE){
			if($player->getSlot($player->slot)->isHoe()) return true;

			$rand = (new LBRandom($this->api))->randomChoice();
			if($this->config->get("openAnnounce")) $this->api->chat->broadcast(" - $player открыл LuckyBlock и ему выпало [".ucfirst($rand[1])."] ".$rand[0]." - ");
			$target->level->setBlock(new Vector3($target->x, $target->y, $target->z), new AirBlock(), true);
			$this->api->block->blockUpdate(new Position($target->x, $target->y, $target->z, $target->level));
			(new LBExecute($this->api))->executeChoice($rand[0], $data);
			return false;
		}
	}

	public function scheduleSpawn(){
		if($this->config->get("enableSpawn")) $this->api->schedule($this->config->get("spawnLuckyBlock")*20, [$this, "spawnLuckyBlock"], [], true);
	}
	
	public function spawnLuckyBlock(){
		$o = $this->api->player->getAll();
		if(count($o) == 0) return; //Don't spawn if noplayers on server

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
		if($this->config->get("dropAnnounce")){
			foreach($o as $player){
				$player->sendChat("LuckyBlock упал на карте, отыщите его!");
			}
		}
	}

	public function __destruct(){
    }
}

class LBExecute{

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
	}
	
	public function executeChoice($choice, $data){
		$target = $data["target"];
		$player = $data["player"];
		$level = $target->level;
		$x = $target->x+.5;
		$y = $target->y;
		$z = $target->z+.5;
		$pos = new Position($x, $y, $z, $level);
		$this->pos = $pos;

		switch($choice){
			//bad
			case "Harm":
				$player->entity->harm(8, "LuckyBlock", true);
				break;
			case "TNT":
				//console("boom");
				$entity = $this->api->entity->add($level, ENTITY_OBJECT, OBJECT_PRIMEDTNT, [
					"x" => $x,
					"y" => $y+1,
					"z" => $z,
					"power" => 3,
					"fuse" => 20
				]);
				$this->api->entity->spawnToAll($entity);
				break;
			case "FallingSand":
				(new LBStructure($this->api))->fallingSand(((int)$player->entity->x)+.5, $player->entity->y, ((int)$player->entity->z)+.5, $level);
				break;
			case "RomanticRose":
				$this->drop(ROSE);
				$player->sendChat("LuckyBlock whisper to you: This rose for you!~");
				break;
			case "ObsidianTrap":
				$x = ((int)$player->entity->x)+.5;
				$y = $player->entity->y;
				$z = ((int)$player->entity->z)+.5;
				(new LBStructure($this->api))->obsidianTrap($x, $y, $z, $level);
				$player->teleport(new Vector3($x, $y, $z), $player->entity->yaw, $player->entity->pitch);
				break;
			case "IronBarSandTrap":
				$x = ((int)$player->entity->x)+.5;
				$y = $player->entity->y;
				$z = ((int)$player->entity->z)+.5;
				(new LBStructure($this->api))->ironBarSandTrap($x, $y, $z, $level);
				$player->teleport(new Vector3($x, $y, $z), $player->entity->yaw, $player->entity->pitch);
				$this->api->schedule(20, [new LBStructure($this->api), "placeSand"], [$x, $y, $z, $level]);
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
				$hp = [10 => 4, 11 => 10, 12 => 10, 13 => 8, 32 => 20, 33 => 20, 34 => 20, 35 => 16, 36 => 20];
				$entity = $this->api->entity->add($level, ENTITY_MOB, $type, [
					"x" => $x,
					"y" => $y,
					"z" => $z,
					"Health" => $hp[$type]
				]);
				$this->api->entity->spawnToAll($entity);
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
				$this->drop(STICK, 0, mt_rand(2, 8));
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
			case "RainbowPillar":
				(new LBStructure($this->api))->rainbowPillar($x, $y, $z, $level);
				break;
			case "GlowingObsidian":
				$this->drop(GLOWING_OBSIDIAN, 0, mt_rand(2, 8));
				break;

			//legendary
			case "InfoUpdate":
				$this->drop(INFO_UPDATE, 0, mt_rand(1, 3));
				break;
			/*case "LuckySword":
				$player->sendChat("Sadly... But u cannot change a name of the item. Not WIP");
				$item = new GoldenSwordItem();
				$api->entity->drop($pos, $item);
				break;*/
			case "UnstableNetherReactor":
				$this->drop(247, 2, 1);
				break;
			case "SpawnEggs":
				$data = range(10, 13) + range(32, 36);
				foreach($data as $meta){
					$this->drop(SPAWN_EGG, $meta, mt_rand(1, 3), 40);
				}
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

class LBRandom{

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->bad = ["Harm", "TNT", "FallingSand", "RomanticRose", "ObsidianTrap", "IronBarSandTrap"]; //no todo
		$this->common = ["Tools", "LuckyAnimal", "LuckyMonster", "ChainArmor", "Seeds", "Food", "MobDrop", "WoodStuff", "StoneStuff"]; //no todo
		$this->uncommon = ["BonusChest", "Ingots", "IronArmor", "NetherStuff", "Carpet", "Cake"]; //"OreStructure"
		$this->rare = ["DiamondPickaxe", "Diamonds", "RainbowPillar", "GlowingObsidian"]; //"WishingWell"
		$this->legendary = ["InfoUpdate", "UnstableNetherReactor", "SpawnEggs"]; //"LuckySword(no todo)"
	}

	public function randomChoice(){
		//return ["IronBarSandTrap", "Test"];
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
		return false;
	}

	public function randomRarity(){
		//return "common";
		$rand = Utils::randomFloat() * 100;
		if($rand <= 20) return "bad"; //20 = 20%
		elseif($rand <= 65) return "common";//65 = 45%
		elseif($rand <= 85) return "uncommon"; //85 = 20%
		elseif($rand <= 95) return "rare"; //95 = 10%
		else return "legendary"; // = 5%
	}
}

class LBStructure{

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
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
			//$this->api->block->blockUpdate(new Position($block[0], $block[1], $block[2], $level));
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
		$entities = [];
		$data = [
		["x" => $x, "y" => $y+6, "z" => $z-1, "Tile" => SAND], ["x" => $x, "y" => $y+7, "z" => $z-1, "Tile" => SAND], ["x" => $x, "y" => $y+8, "z" => $z-1, "Tile" => SAND],
		["x" => $x-1, "y" => $y+6, "z" => $z-1, "Tile" => SAND], ["x" => $x-1, "y" => $y+7, "z" => $z-1, "Tile" => SAND], ["x" => $x-1, "y" => $y+8, "z" => $z-1, "Tile" => SAND],
		["x" => $x+1, "y" => $y+6, "z" => $z-1, "Tile" => SAND], ["x" => $x+1, "y" => $y+7, "z" => $z-1, "Tile" => SAND], ["x" => $x+1, "y" => $y+8, "z" => $z-1, "Tile" => SAND],
		["x" => $x, "y" => $y+6, "z" => $z, "Tile" => SAND], ["x" => $x, "y" => $y+7, "z" => $z, "Tile" => SAND], ["x" => $x, "y" => $y+8, "z" => $z, "Tile" => SAND],
		["x" => $x-1, "y" => $y+6, "z" => $z, "Tile" => SAND], ["x" => $x-1, "y" => $y+7, "z" => $z, "Tile" => SAND], ["x" => $x-1, "y" => $y+8, "z" => $z, "Tile" => SAND],
		["x" => $x+1, "y" => $y+6, "z" => $z, "Tile" => SAND], ["x" => $x+1, "y" => $y+7, "z" => $z, "Tile" => SAND], ["x" => $x+1, "y" => $y+8, "z" => $z, "Tile" => SAND],
		["x" => $x, "y" => $y+6, "z" => $z+1, "Tile" => SAND], ["x" => $x, "y" => $y+7, "z" => $z+1, "Tile" => SAND], ["x" => $x, "y" => $y+8, "z" => $z+1, "Tile" => SAND],
		["x" => $x-1, "y" => $y+6, "z" => $z+1, "Tile" => SAND], ["x" => $x-1, "y" => $y+7, "z" => $z+1, "Tile" => SAND], ["x" => $x-1, "y" => $y+8, "z" => $z+1, "Tile" => SAND],
		["x" => $x+1, "y" => $y+6, "z" => $z+1, "Tile" => SAND], ["x" => $x+1, "y" => $y+7, "z" => $z+1, "Tile" => SAND], ["x" => $x+1, "y" => $y+8, "z" => $z+1, "Tile" => SAND]
		];
		foreach($data as $d){
			array_push($entities, $this->api->entity->add($level, ENTITY_FALLING, FALLING_SAND, $d));
		}
		foreach($entities as $e){
			$this->api->entity->spawnToAll($e);
		}
		unset($entities);
	}

	public function placeSand($data){
		$x = $data[0];
		$y = $data[1];
		$z = $data[2];
		$level = $data[3];

		$entities = [];
		$data = [
		["x" => $x, "y" => $y+6, "z" => $z, "Tile" => SAND], ["x" => $x, "y" => $y+7, "z" => $z, "Tile" => SAND], ["x" => $x, "y" => $y+8, "z" => $z, "Tile" => SAND],
		];
		foreach($data as $d){
			array_push($entities, $this->api->entity->add($level, ENTITY_FALLING, FALLING_SAND, $d));
		}
		foreach($entities as $e){
			$this->api->entity->spawnToAll($e);
		}
		unset($entities);
	}

	public function fallingWool($data){
		//console(json_encode($data[1]));
		$level = $this->api->level->get($data[0]);
		$entity = $this->api->entity->add($level, ENTITY_FALLING, FALLING_SAND, $data[1]);
		$this->api->entity->spawnToAll($entity);
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
		$items = [];
		//console('generating loot for BonusChest);
		foreach($loot as $itemID => $array){
			$id = constant(strtoupper($itemID));
			$item = BlockAPI::getItem($id, $array['meta'], $array['count']);
			$tile->setSlot($array['slot'], $item);
			$items[$array['slot']] = $item;
			//console('id: '.$itemID.' meta:'.$array['meta'].' count: '.$array['count'].' slot: '.$array['slot']);
		}
	}
}