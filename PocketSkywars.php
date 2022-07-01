<?php

/*
 __PocketMine Plugin__
name=PocketSkywars
description=Simple Skywars plugin.
version=0.8.1 r1 build2
apiversion=12.1
author=Omattyao | ArkQuark
class=PocketSkywars
*/

define("CONVERT_COEFFICIENT", 20);
define("DEFAULT_COIN", 10);
define("LINE_BREAK", 45);

class PocketSkywars implements Plugin{
	protected $api, $path, $config, $kit, $db, $players, $score, $field;
	
	private $backup = array("world" => array(), "chest" => array());
	private $switch = array();
	private $schedule = array();
	private $count_id = 0;
	private $s_id = array(); //schedule_id
	protected $status = false;

	public function __construct(ServerAPI $api, $server = false){
		$this->server = ServerAPI::request();
		$this->api = $api;
		$this->select = array();
	}

	public function init(){
		$this->createConfig();
		$this->loadDB();
		$this->readyKit();
		$this->resetParams();
		
		$event = ["tile.update" => true, 
		"player.join" => true, 
		"player.quit" => true, 
		"player.death" => true,
		"console.command.stop" => true, 
		"player.move" => false,  
		"player.spawn" => false,  
		"entity.explosion" => false, 
		"player.offline.get" => false, 
		"player.block.break" => false, 
		"player.block.place" => false, 
		"player.block.touch" => false, 
		"player.container.slot" => false,
		"entity.health.change" => false,
		"console.command.spawn" => false];
		$this->addEventHandler($event);
		
		$this->api->console->register("sky", "PocketSkywars command.", array($this, "command"));
		$this->api->console->register("kit", "PocketSkywars command.", array($this, "command"));
		$this->api->console->register("refill", "Turn on or off chest refill", array($this, "command"));
		$this->api->ban->cmdWhitelist("kit");
		$this->api->ban->cmdWhitelist("sky");
	}

	public function addEventHandler($array = array()){
		foreach($array as $handle => $event){
			if($event === true) $this->api->event($handle, array($this, "handle"));
			else $this->api->addHandler($handle, array($this, "handle"), 5);
		}
	}

	public function getSurvival(){
		$players = $this->players;
		$surv = 0;
		if(count($players) == 0) return 0;
		foreach($players as $player){
			if($player->gamemode === 0){
				$surv++;
			}
		}
		return $surv;
	}

	public function handle(&$data, $event){
		switch($event){
			case "entity.health.change":
				if($this->status == "invincible"){
					if(isset($this->needKill[$data['entity']->eid])){
						unset($this->needKill[$data['entity']->eid]);
						return true;
					}
				return false;
				}
				break;
			case "player.move":
				if($data->y <= 5){
					if($this->status == false){
						$this->api->entity->harm($data->eid, PHP_INT_MAX, "void", true);
						break;
					}
					$player = $data->player;
					$player->blocked = true;
					$player->teleport($this->getLobby());
					$player->sendChat("You fall into void. You will be kicked!");
					$this->schedule(5, "kick", array($player, "void"));
				}
				break;
			case "player.join":
				if($this->getAccount($data->username) === false){
					$this->createAccount($data->username);
				}
				break;
			case "player.spawn":
				if(!$this->switch["server.gate"]){
					$data->sendChat("xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
					$data->sendChat("[Skywars] Now the tournament is going on.");
					$data->sendChat("[Skywars] You will spectator of this game.");
					$data->sendChat("xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
				}
				if($this->switch["first.spawn"] instanceof Position){
					$data->setSpawn($this->switch["first.spawn"]);
				}
				if($this->status != false){
					$this->showAccountInfo($data);
				}
				else{
					$data->sendChat("[Skywars] You can run the game /sky run");
				}
				break;
			case "player.container.slot":
				if($this->switch["chest.lock"]) return false;
				break;
			/*case "player.equipment.change":
				if(substr($data['item']->getName(), 0, 5) == 'Fire '){
					$data['player']->sendChat('[Skywars] You got a Fire Aspect I on '.$data['item']->getName());
				}
				break;*/
			case "player.offline.get":
				if($this->status == "lobby" or $this->status == "finish"){
					$data->set("gamemode", 0);
				}
				elseif($this->status == "play" or $this->status == "invincible"){
					$data->set("gamemode", 3);
				}
				$data->set("health", 20);
				if($this->switch["first.spawn"] instanceof Position){
					$p = $this->switch["first.spawn"];
					$data->set("position", array("x" => $p->x, "y" => $p->y, "z" => $p->z, "level" => $p->level->getName()));
				}
				break;
			case "player.death":
			case "player.quit":
				if($this->switch["death.count"]) $this->loserProcess($data, $event);
				break;
			case "player.block.place":
				if($this->switch["world.protect"] and $data["item"]->getID() !== SIGN){
					if($this->status == 'play' or $this->status == 'invincible') return true;
					if($this->status == false){
						if($this->api->ban->isOp($data['player']->username)) return true;
					}
					return false;
				}
				if($this->switch["game.world.protect"] and $data["item"]->getID() !== SIGN) return false;
				if($this->switch["backup"]) $this->backupLevel("place", $data);
				break;
			case "player.block.break":
				if($this->switch["game.world.protect"]) return false;
				if($this->switch["world.protect"]){
					if($this->status == 'play' or $this->status == 'invincible') return true;
					if($this->status == false){
						if($this->api->ban->isOp($data['player']->username)) return true;
					}
					$player = $data['player'];
					$target = $data['target'];
					//console(floor($player->entity->x) .'=='. $data['target']->x.', '.floor($player->entity->z) .'=='. $data['target']->z);
					
					if((floor($player->entity->x) == $target->x) and (floor($player->entity->z) == $target->z)){
						//console('jump');
						
						$x = $player->entity->x;
						$y = $player->entity->y;
						$z = $player->entity->z;
						$level = $player->level;
						if(floor($y) == ($target->y)+1){
							$y = ($target->y)+1.5;
						}
						elseif($level->getBlock(new Vector3($x, $y-1, $z))->getID() == AIR and floor($y) == ($target->y)+2){
							$y = ($target->y)+1.5;
						}
						else return false;
						$player->teleport(new Position($x, $y, $z, $level));
					}
					return false;
				}
				if($this->switch["backup"])	$this->backupLevel("break", $data);
				break;
			case "player.block.touch":
				if($this->status == 'play' or $this->status == 'invincible'){
					if($data['target']->y >= 119){
						$data['player']->sendChat("You can't build here! (Y > 120)");
						return false;
					}
					return true;
				}
				//$data['player']->sendChat($data['player']->getSlot($data['player']->slot)->getName());
				if(isset($this->select[$data["player"]->username])){
					if($data['target']->getID() === CHEST){
						$target = $data['target'];
						$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "chests.yml");
						$pos = array('x' => $target->x, 'y' => $target->y, 'z' => $target->z, 'level' => $target->level->getName());
						array_push($cfg, array("pos" => $pos, "rarity" => $this->select[$data["player"]->username]['rarity']));
						$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."chests.yml", $cfg);
						$data["player"]->sendChat("[Skywars] Chest position captured");
						unset($this->select[$data["player"]->username]);
						unset($this->select[$data["player"]->username]['rarity']);
					}
					else $data["player"]->sendChat("[Skywars] You need to tap a chest.");
				}
				break;
			case "tile.update":
				if($this->switch["world.protect"]) return true;
				if($this->switch["game.world.protect"]) return true;
				if($data instanceof Tile and $data->class === TILE_SIGN){
					$data->level->setBlockRaw($data, new AirBlock());
					$this->api->tile->remove($data->id);
				}
				break;
			case "entity.explosion":
				return false;
			case "console.command.spawn":
				$data["issuer"]->sendChat("/spawn was disabled for this server!");
				return false;
				break;
			case "console.command.stop": //need core fix
				if($data['issuer'] instanceof Player) break;
				if($this->status !== false){
					$this->gameStop();
				}
				break;
		}
	}

	public function loserProcess($data, $event){
		switch($event){
			case "player.death":
				$dead = $data["player"]->username;
				if(!isset($this->players[$dead])) break;
				unset($this->players[$dead]);
				if(is_numeric($data["cause"])){
					$entity = $this->api->entity->get($data["cause"]);
					if($entity instanceof Entity and $entity->class === ENTITY_PLAYER){
						$killer = $entity->name;
						if(!isset($this->players[$killer])){
							$this->kick($killer, "unknown", false, false);
							break;
						}
						$this->score[$dead]["cause"] = $killer;
						$this->score[$killer]["kill"][] = $dead;
						$reason = "killed by $killer!";
						$this->giveEXP($killer, $this->config["exp"]["kill"]);
						//$coin = $this->kit->grantPocketCash($dead);
						//$this->kit->grantPocketCash($killer, $coin);
					}else{
						$reason = "killed";
						$this->score[$dead]["cause"] = "killed";
					}
				}else{
					$reason = "accident";
					$this->score[$dead]["cause"] = $data["cause"];
				}
				$this->schedule(5, "kick", array($dead, $reason));
				$surv = $this->getSurvival();
				if($surv > 1){
					$this->broadcast("[Skywars] ".$surv." players remaining.");
				}
				elseif($surv = 0){
					$this->schedule(1, "gameFinish", false);
				}
				else{
					$winner = array_shift($this->players);
					$this->schedule(1, "gameFinish", $winner->username);
				}
				break;
			case "player.quit":
				$user = $data->username;
				if(!isset($this->players[$user])) break;
				unset($this->players[$user]);
				$this->score[$user]["cause"] = "disconnect";
				$surv = $this->getSurvival();
				if($surv > 1){
					$this->broadcast("[Skywars] ".$surv." players remaining.");
				}
				elseif($surv = 0){
					$this->schedule(1, "gameFinish", false);
				}
				else{
					$winner = array_shift($this->players);
					$this->schedule(1, "gameFinish", $winner->username);
				}
				break;
		}
	}

	public function command($cmd, $params, $issuer, $alias){
		if($issuer instanceof Player) $output = $this->playerCommand($cmd, $params, $issuer, $alias);
		else $output = $this->consoleCommand($cmd, $params, $issuer, $alias);
		return $output;
	}

	public function consoleCommand($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "refill":
				$output .= "Please run this command in game.";
				break;
			case "sky":
				$mode = array_shift($params);
				switch($mode){
					case "run":
						if($this->status !== false){
							$output .= "The game has already begun!\n";
							break;
						}
						
						//console(var_dump($this->api->plugin->readYAML($this->api->plugin->configPath($this). "chests.yml")));
						//console(var_dump($this->chestRandLoot())); 									//test for code problems
						//$this->chestRefill();
						
						$players = $this->api->player->getAll();
						$this->api->chat->broadcast("[Skywars] Skywars server has been running!");
						$field = array_shift($params);
						if(empty($field)) $field = false;
						$this->gameReady($field);
						foreach($players as $player){
							if($player->gamemode !== 0){
								$player->sendChat("[Skywars] Changing gamemode to survival.");
								$this->schedule(5, "kick", array($player, "gamemode change"));
							}
						}
						break;
					case "start":
						if($this->status !== "lobby"){
							$output .= "[Skywars] This command is unavailable now\n";
							break;
						}
						$this->cancelAllSchedules();
						$this->gameLobby(-($this->config["times"]["lobby"] - 10), true);
						break;
					case "stop":
						if($this->status === false){
							$output .= FORMAT_YELLOW."[Skywars] Game is not opened!\n";
							break;
						}
						$this->gameStop();
						break;
					case "marker":
						$bool = array_shift($params);
						if(!$this->formatBool($bool)){
							$output .= "Usage: /sky marker <on | off>\n";
							break;
						}
						if($bool){
							$this->placePointMarker();
							$output .= "[Skywars] placed markers on the world!";
						}else{
							$this->breakPointMarker();
							$output .= "[Skywars] breaked markers on the world!";
						}
						break;
					case "state":
					case "status":
						if($this->status !== "play"){
							$output .= "[Skywars] This command is unavailable now.\n";
							break;
						}
						$this->showState($output);
						break;
					case "record":
					case "records":
						$this->showRecords($output);
						break;
					case "settime":
						$mode =(string) array_shift($params);
						$time =(int) array_shift($params);
						if(empty($time) or $time <= 0 or !in_array($mode, array("lobby", "invincible", "play"))){
							$output .= "Usage: /sky settime <status> <time(sec)>\n<status> ... \"ready\" or \"invincible\" or \"play\"\n";
							break;
						}
						$this->config["times"][$mode] = $time;
						$this->writeConfig();
						$this->formatTime($time);
						$output .= "\"".$mode."\" time is seted to ".$time.".\n";
						break;
					case "setday":
					case "locktime":
						$mode = array_shift($params);
						if(in_array($mode, array("day", "night", "sunset", "sunrise"))){
							$this->config["lock-time"] = $mode;
							$this->writeConfig();
							$output .= "[Skywars] Setted lock-time to \"$mode\".\n";
						}else{
							$output .= "[Skywars] Failed to set lock-time.\n";
						}
						break;
					case "setprize":
						$amount =(int) array_shift($params);
						if($amount = "" or $amount < 0){
							$output .= "Usage: /sky setprize <amount>\n";
							break;
						}
						$this->config["prize"] = $amount;
						$this->writeConfig();
						$output .= "prize is setted to \"".$amount."\".\n";
						break;
					case "worldprotect":
					case "protection":
					case "protect":
						$bool = array_shift($params);
						if(!$this->formatBool($bool)){
							$output .= "[Skywars] Usage: /sky protect <on | off>\n";
							break;
						}
						if($bool){
							$output .= "[Skywars] Turned on world protection for tournaments.\n";
							$this->config["game-world-protect"] = true;
						}else{
							$output .= "[Skywars] Turned off the protection for tournaments.\n";
							$this->config["game-world-protect"] = false;
						}
						if($this->status == "invincible" or $this->status == "play"){
							$this->tool("game.world.protect", $bool);
						}
						break;
					case "addfield":
						$field =(string) array_shift($params);
						$levelname =(string) array_shift($params);
						if(empty($field)){
							$output .= "[Skywars] Usage: /sky addfield <field name>\n";
							break;
						}
						if($this->isAlnum($field) === false){
							$output .= FORMAT_YELLOW."[Skywars] You need to use English for field name.";
							break;
						}
						if(empty($levelname)){
							$levelname = false;
						}
						if($this->fieldExists($field)){
							$output .= FORMAT_YELLOW."[Skywars] \"".$field."\" already exists!\n";
							break;
						}
						$this->config["field"][$field] = array("lobby" => array(), "start" => array(), "level" => $levelname);
						$this->writeConfig();
						$output .= FORMAT_AQUA."[Skywars] Adding \"".$field."\" field succeeded! Next, you must set a lobby point and start points of the field.\n";
						$output .= "[Skywars] Usage: /sky setlobby <field name> <x> <y> <z>\n";
						$output .= "[Skywars] Usage: /sky addpoint <field name> <x> <y> <z>\n";
						$output .= "[Skywars] Usage: /sky rmpoint <field name> <number>\n";
						break;
					case "rmfield":
						$field =(string) array_shift($params);
						if(!$this->fieldExists($field)){
							$output .= FORMAT_YELLOW."[Skywars] \"".$field."\" doesn't exist!\n";
							break;
						}
						unset($this->config["field"][$field]);
						$output .= FORMAT_AQUA."[Skywars] Removing \"".$field."\" field succeeded!\n";
						$this->writeConfig();
						break;
					case "fieldinfo":
						$field =(string) array_shift($params);
						if(!$this->fieldExists($field)){
							$output .= FORMAT_YELLOW."[Skywars] \"".$field."\" doesn't exist!\n";
							break;
						}
						$this->showFieldInfo($field);
						break;
					case "fieldlist":
						foreach($this->config["field"] as $field => $data){
							$output .= "FIELD: \"".FORMAT_YELLOW.$field.FORMAT_RESET."\"\n";
						}
						break;
					case "setlobby":
					case "addpoint":
					case "rmpoint":
						if($this->status !== false){
							$output .= "[Skywars] This command is unavailable now.\n";
							break;
						}
						$this->editField($mode, $params, $output);
						break;
					case "debug":
						var_dump($this->switch);
						break;
					default:
						$output .= "Usage: /sky run ...run Skywars\n";
						$output .= "Usage: /sky start ...start a game\n";
						$output .= "Usage: /sky stop ...suspend the game\n";
						$output .= "Usage: /sky marker <on | off>\n";
						$output .= "Usage: /sky addfield <field name>\n";
						$output .= "Usage: /sky rmfield <field name>\n";
						$output .= "Usage: /sky fieldinfo <field name>\n";
						$output .= "Usage: /sky setlobby <field name> <x> <y> <z>\n";
						$output .= "Usage: /sky setprize <amount>\n";
						$output .= "Usage: /sky protect <on | off>\n";
						$output .= "Usage: /sky addpoint <field name> <x> <y> <z>\n";
						$output .= "Usage: /sky rmpoint <field name> <number>\n";
				}
				break;
			case "kit":
				$mode = array_shift($params);
				switch($mode){
					case "addkit":
					case "add":
						$name =(String) array_shift($params);
						$price = array_shift($params);
						$level = array_shift($params);
						if($this->kit->add($name, $price, $level) === true){
							$output .= FORMAT_DARK_AQUA."[Skywars] Added $name kit!\n";
						}else{
							$output .= FORMAT_YELLOW."[Skywars] Failed to add $name !\n";
						}
						break;
					case "removekit":
					case "remove":
					case "rmkit":
					case "rm":
						$kitname = trim(array_shift($params));
						if($this->isAlnum($kitname) === false){
							$output .= FORMAT_YELLOW."[Skywars] You need to use English for kit name.";
							break;
						}
						if($this->kit->remove($kitname)){
							$output .= FORMAT_DARK_AQUA."[Skywars] Removed \"$kitname\"!\n";
						}else{
							$output .= FORMAT_YELLOW."[Skywars] Failed to remove \"$kitname\"!\n";
						}
						break;
					case "list":
						$this->kit->showList($output);
						break;
					case "info":
						$kitname = array_shift($params);
						$this->kit->showKitInfo($kitname);
						break;
					case "additem":
						$kitname = array_shift($params);
						$id = array_shift($params);
						$meta = array_shift($params);
						$count = array_shift($params);
						if(empty($kitname) or $id === null or !is_numeric($id)){
							$output .= "Usage: /kit additem <kit> <id>(meta)(count)\n";
							break;
						}
						if($this->kit->get($kitname) === false){
							$output .= FORMAT_YELLOW."[Skywars] The kit \"$kitname\" doesn't exist.\n";
							break;
						}
						if(!isset(Block::$class[$id]) and !isset(Item::$class[$id])){
							$output .= FORMAT_YELLOW."[Skywars]NOTICE: The item id \"$id\" could be incorrect.\n";
						}
						if($meta === null){
							$meta = 0;
						}
						if($count === null){
							$count = 1;
						}
						$sets = array("id" => $id, "meta" => $meta, "count" => $count);
						if($this->kit->editItem("add", $kitname, $sets)){
							$output .= FORMAT_DARK_AQUA."[Skywars] Added items to \"$kitname\"!\n";
						}else{
							$output .= FORMAT_YELLOW."[Skywars] Failed to add items to \"$kitname\".\n";
						}
						$this->kit->showKitInfo($kitname);
						break;
					case debug:
						var_dump($this->kit->getAll());
						break;
					default:
						$output .= "Usage: /kit list\n";
						$output .= "Usage: /kit add <kit name>\n";
						$output .= "Usage: /kit additem <kit name> <id>(meta)(count)\n";
						$output .= "Usage: /kit rm <kit name>\n";
				}
				break;
		}
		return $output;
	}

	public function playerCommand($cmd, $params, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "refill":
				if(!isset($params[0])){
					$output .= "[Skywars] You need set rarity for chest!\n";
					$output .= "[Skywars] /$cmd <middle, pre-middle, spawn>";
					break;
				}
				$this->select[$issuer->username] = array();
				$rarity = (int) str_replace(array('middle', 'pre-middle', 'spawn'), array(0, 1, 2), $params[0]);
				$this->select[$issuer->username]['rarity'] = $rarity;
				$output .= "[Skywars] Touch a chest to refresh refill";
				break;
			case "kit":
				$kitname = trim(array_shift($params));
				switch($kitname){
					case "":
						$this->kit->showAccountInfo($issuer);
					case "help":
						$output .= "Usage: /kit <kitname> ......... buy kit\n";
						$output .= "Usage: /kit list ......... show a kit list\n";
						break;
					case "list":
					case "ls":
						$this->kit->showList($output);
						$output .= "way to buy: /kit <kit name>\n";
						break;
					default:
						$rec = $this->getAccount($issuer->username);
						$output .= $this->kit->buy($issuer->username, $rec["level"], $kitname);
						if($this->status === "invincible" or $this->status === "play"){
							$this->kit->equip($issuer->username);
						}
				}
				break;
			case "sky":
				if($params[0] === 'run'){
					if($this->status !== false){
						$output .= "The game has already begun!\n";
						break;
					}
					$players = $this->api->player->getAll();
					$this->api->chat->broadcast("[Skywars] Skywars server has been running!");
					$field = $params[1];
					if(empty($field)) $field = false;
					$this->gameReady($field);
					foreach($players as $player){
						if($player->gamemode !== 0){
							$player->sendChat("[Skywars] Changing gamemode to survival.");
							$this->schedule(5, "kick", array($player, "gamemode change"));
						}
					}
					break;
				}
				elseif($params[0] === 'stop'){
					if(!$this->api->ban->isOp($issuer->username)) break;
					if($this->status === false){
						$output .= FORMAT_YELLOW."[Skywars] Game is not opened!\n";
						break;
					}
					$this->gameStop();
					break;
				}
				elseif($params[0] === 'start'){
					if(!$this->api->ban->isOp($issuer->username)) break;
					if($this->status !== "lobby"){
						$output .= "[Skywars] This command is unavailable now\n";
						break;
					}
					$this->cancelAllSchedules();
					$this->gameLobby(-($this->config["times"]["lobby"] - 10), true);
					break;
				}
				else $this->showAccountInfo($issuer);
				break;
		}
		return $output;
	}

	public function editField($mode, $params, &$output){
		$field = array_shift($params);
		if(!$this->fieldExists($field)){
			$output .= FORMAT_YELLOW."[Skywars] The field doesn't exist!".FORMAT_RESET.": \"".FORMAT_GREEN."".$field.FORMAT_RESET."\"\n";
			$output .= "[Skywars] Usage: /sky setlobby <field name> <x> <y> <z> <world>\n";
			$output .= "[Skywars] Usage: /sky addpoint <field name> <x> <y> <z> <world>\n";
			$output .= "[Skywars] Usage: /sky rmpoint <field name> <number>\n";
			return;
		}
		switch($mode){
			case "setlobby":
				$x = array_shift($params);
				$y = array_shift($params);
				$z = array_shift($params);
				if(!is_numeric($x) or !is_numeric($y) or !is_numeric($z)){
					$output .= "[Skywars] Usage: /sky setlobby <field name> <x> <y> <z>\n";
					break;
				}
				$x = (float) $x;
				$y = (float) $y;
				$z = (float) $z;
				$this->config["field"][$field]["lobby"] = array($x, $y, $z);
				$this->writeConfig();
				$output .= FORMAT_AQUA."[Skywars] Setted!\n";
				$this->showFieldInfo($field);
				break;
			case "addpoint":
				$x = array_shift($params);
				$y = array_shift($params);
				$z = array_shift($params);
				if(!is_numeric($x) or !is_numeric($y) or !is_numeric($z)){
					$output .= "[Skywars] Usage: /sky addlobby <field name> <x> <y> <z>\n";
					break;
				}
				$x =(float) $x;
				$y =(float) $y;
				$z =(float) $z;
				$this->config["field"][$field]["start"][] = array($x, $y, $z);
				$this->writeConfig();
				$output .= FORMAT_AQUA."[Skywars] Added!\n";
				$this->showFieldInfo($field);
				break;
			case "rmpoint":
				$number = array_shift($params);
				if($number == ""){
					$output .= "[Skywars] Usage: /sky rmpoint <field name> <number>\n";
					break;
				}
				$number =(int) $number;
				if(!isset($this->config["field"][$field]["start"][$number])){
					$output .= FORMAT_YELLOW."[Skywars] No.".$number." has not been setted!\n";
					break;
				}
				unset($this->config["field"][$field]["start"][$number]);
				$this->config["field"][$field]["start"] = array_values($this->config["field"][$field]["start"]);
				$this->writeConfig();
				$output .= FORMAT_AQUA."[Skywars] Removed No.\"".$number."\" point!\n";
				$this->showFieldInfo($field);
				break;
		}
	}

	public function chestLootList(){
		$chestLoot = $this->config["chest-loot"];
		$lootList = array();
		foreach($chestLoot as $id => $array){
			$array["rarity"] = (int) str_replace(array('middle', 'pre-middle', 'spawn'), array(0, 1, 2), $array["rarity"]);
			$lootList[$id] = $array;
		}
		return $lootList;
	}
	
	public function chestRandLoot(){
		$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "chests.yml");
		if(!isset($cfg)) return;
		$loot = $this->chestLootList();
		$chests = array();
		
		foreach($cfg as $chestID => $chestArray){
			foreach($loot as $id => $lootArray){
				//itemrarity spawn(2) < chestrarity middle(0)
				//if($lootArray['rarity'] < $chestArray['rarity']) break 1;
				if(Utils::chance($lootArray['chance']) and !($lootArray['rarity'] < $chestArray['rarity'])){
					$slots = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26];
					$chests[$chestID][$id]['id'] = $id;
					$chests[$chestID][$id]['meta'] = $lootArray['meta'];
					$chests[$chestID][$id]['count'] = mt_rand($lootArray['min-count'], $lootArray['max-count']);
					foreach($slots as $key){
						$tempSlot = mt_rand(0, 26);
						if($tempSlot == $slots[$key]){
							$random = mt_rand(0, 1);
							if($random === true){
								$tempSlot = mt_rand($tempSlot+1, 26);
								array_push($slots, $tempSlot);
							}
							else{
								$tempSlot = mt_rand(0, $tempSlot-1);
								array_push($slots, $tempSlot);
							}
						}
						else{
							array_push($slots, $tempSlot);
						}
						$chests[$chestID][$id]['slot'] = $tempSlot;
					}
				}
			}
		}
		//console(var_dump($chests));
		return $chests;
	}
	
	public function chestRefill(){
		$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "chests.yml");
		if(!isset($cfg)) return;
		console(FORMAT_AQUA.'[Skywars] Generating loot for chests'.FORMAT_RESET);
		foreach($cfg as $cfgChestID => $array){
			$pos = new Position($array['pos']['x'], $array['pos']['y'], $array['pos']['z'], $this->api->level->get($array['pos']['level']));
			$tile = $this->api->tile->get($pos);
			if($tile == false){
				$level = $this->api->level->get($array['pos']['level']);
				$data = array();
				$this->api->tile->add($level, TILE_CHEST, $array['pos']['x'], $array['pos']['y'], $array['pos']['z'], $data);
				$tile = $this->api->tile->get($pos);
			}
			elseif($tile->class !== TILE_CHEST) break;
			$item = $this->api->block->getItem(0, 0, 0);
			for($slot = 0; $slot <= 26; $slot++){
				$tile->setSlot($slot, $item);
			}
			
			$loot = $this->chestRandLoot();
			//console('generating loot for chest with id '.$cfgChestID);
			foreach($loot[$cfgChestID] as $itemID => $array){
				if(substr($itemID, 0, 5) == 'fire_'){
					//console('Spawning fire sword');
					$itemID = str_replace('fire_', '', $itemID);
					$id = constant(strtoupper($itemID));
					$item = $this->api->block->getItem($id, $array['meta'], $array['count']);
					//$itemReflection = new ReflectionClass('Item');
					//$itemName = $itemReflection->getProperty('name');
					//$itemName->setAccessible(true);
					//$itemName->setValue($item, 'Fire Diamond Sword');
				}
				else{
					$id = constant(strtoupper($itemID));
					$item = $this->api->block->getItem($id, $array['meta'], $array['count']);
				}
				$tile->setSlot($array['slot'], $item);
				//console('id: '.$itemID.' meta:'.$array['meta'].' count: '.$array['count'].' slot: '.$array['slot']);
			}
			//console('');
		}
	}

	public function gameReady($field = false){
		$this->status = "ready";
		$this->resetParams();
		if(!$this->setField($field)){
			$this->gameStop();
			return;
		}
		$spawnPoints = count($this->getStartPoints());
		if($spawnPoints > 1){
			$this->server->api->setProperty("max-players", $spawnPoints);
		}
		$this->cleanDropedItems();
		$this->confiscateItems();
		$this->gameLobby();
	}

	public function gameLobby($fix = 0, $start = false){
		$this->status = "lobby";
		$position = $this->getLobby();
		$this->tool("first.spawn", $position);
		$this->tool("lock.time", true);
		$this->tool("chest.lock", true);
		if(!$start) $this->teleportAllPlayers("lobby");
		$this->setGamesSchedule($fix);
		$this->s_id["lobby_info"] = $this->schedule(33, "lobbyAnnounce", false, true);
		$this->countdown($this->config["times"]["lobby"] + $fix);
	}

	public function gameInvincible(){
		if($this->setPlayers() === false){
			$this->broadcast("[Skywars] Failed to start a tournament!");
			$this->broadcast("[Skywars] It requires 2 or more people.");
			$this->gameSuspend();
			return;
		}
		$this->status = "invincible";
		$time = $this->config["times"]["invincible"];
		$this->readyScore();
		$this->tool("server.gate", false);
		$this->tool("backup", true);
		$this->tool("world.protect", false);
		$this->tool("game.world.protect", $this->config["game-world-protect"]);
		$this->tool("chest.lock", false);
		$this->tool("death.count", true);
		$this->teleportAllPlayers("field");
		$this->confiscateItems();
		$this->cleanDropedItems();
		$this->chestRefill();
		array_map(function($user){
			$this->kit->equip($user);
		}, array_keys($this->players));
		$this->countdown($time);
		$this->formatTime($time);
		$surv = $this->getSurvival();
		$this->broadcast("================================================\n"."The game has begun!\n"."There are ".$surv." players participating.\n"."Good Luck!\n"."================================================");
		$this->healAllPlayers();
		$this->cancelSchedule($this->s_id["lobby_info"]);
	}

	public function gamePlay(){
		$this->status = "play";
		$this->healAllPlayers();
		$this->tool("pvp", true);
		$this->broadcast("[Skywars] You are no longer invincible.");
		$this->countdown($this->config["times"]["play"]);
		//$time = $this->formatTime($this->config["times"]["chest-refill"]);
		$this->broadcast("Chest will be refilled in ".$this->config["times"]["chest-refill"]." seconds.");
		$this->schedule($this->config["times"]["chest-refill"], "chestRefillInGame", false, false);
	}

	public function gameFinish($winner = false){
		$this->status = "finish";
		$this->tool("death.count", false);
		$this->tool("world.backup", false);
		$this->tool("world.protect", $this->config["world-protect"]);
		$this->teleportAllPlayers("lobby");
		$this->cancelSchedule($this->s_id["finish"]);
		$this->cancelCountSchedule();
		if($winner !== false){
			if($this->givePrize($winner)){
				$msg = "\"".$winner."\" won a prize of \"".$this->config["prize"]."\"PM for the tournament!\n";
			}else{
				$msg = "\"".$winner."\" won the tournament!\n";
			}
			$this->broadcast("================================================\n"."The game has been finished!\n".$msg."================================================");
			$this->giveEXP($winner, $this->config["exp"]["win-tournament"]);
		}else{
			$message= "";
			foreach($this->players as $player){
				if($player->gamemode === 0){
					$message .= $player->username.", ";
				}
			}
			if($message === null){
				$this->broadcast("[SkyWars] All players left from the game");
			}
			else{
				$this->broadcast("================================================\n"."[Skywars] This game is suspended!\n"."[Skywars] Remnants list:\n".$message."\n================================================");
			}
		}
		$this->schedule(10, "gameReady", $this->field["stage"]);
		$this->record($winner, $this->score);
		$this->confiscateItems();
		$players = $this->api->player->getAll();
		if(count($players) == 0) return;
		foreach($players as $player){
			if($player->gamemode !== 0){
				$player->sendChat("[Skywars] Changing gamemode to survival.");
				$this->schedule(5, "kick", array($player, "game finished"));
			}
			else $this->confiscateItems();
		}
	}

	public function gameSuspend(){
		$this->status = "finish";
		if(count($this->players) === 0){
			$this->schedule(10, "gameStop", array("[Skywars] Countdown stopped due to no online."));
		}
		else{
			$this->cancelAllSchedules();
			$this->schedule(10, "gameLobby", array());
			return;
		}
	}

	public function gameStop($msg = "[Skywars] Game stopped working!"){
		if($this->status == "lobby" and $msg == "[Skywars] Game stopped working!") $msg = "[Skywars] Countdown stopped!";
		$this->broadcast($msg);
		$this->cancelAllSchedules();
		$this->restoreWorld();
		$this->resetParams();
	}

	public function resetParams(){
		$this->tool("server.gate", true);
		$this->tool("pvp", false);
		$this->tool("game.world.protect", false);
		$this->tool("world.protect", $this->config["world-protect"]);
		$this->tool("chest.lock", false);
		$this->tool("death.count", false);
		$this->tool("backup", false);
		$this->tool("lock.time", false);
		$this->tool("first.spawn", false);
		$this->cancelAllSchedules();
		$this->players = array();
		$this->score = array();
		$this->field = false;
		$this->status = false;
		$this->kit->resetParams();
	}

	public function tool($tool, $params){
		switch($tool){
			case "server.gate":
				$bool = $params;
				$this->formatBool($bool);
				$this->switch["server.gate"] = (bool) $bool;
				break;
			case "pvp":
				$bool = $params;
				$this->formatBool($bool);
				$this->api->setProperty("pvp", $bool);
				break;
			case "first.spawn":
				$position = $params;
				$this->switch["first.spawn"] = $position;
				break;
			case "world.protect":
				$bool = $params;
				$this->formatBool($bool);
				$this->switch["world.protect"] = (bool) $bool;
				break;
			case "game.world.protect":
				$bool = $params;
				$this->formatBool($bool);
				$this->switch["game.world.protect"] = (bool) $bool;
				break;
			case "chest.lock":
				$bool = $params;
				$this->formatBool($bool);
				$this->switch["chest.lock"] = (bool) $bool;
				break;
			case "death.count":
				$bool = $params;
				$this->formatBool($bool);
				$this->switch["death.count"] = (bool) $bool;
				break;
			case "freeze.players":
				$bool = $params;
				$this->formatBool($bool);
				$players = $this->api->player->getAll();
				if(count($players) == 0) return;
				foreach($players as $player){
					$player->blocked = (bool) $bool;
				}
				break;
			case "backup":
				$bool = $params;
				$this->formatBool($bool);
				$this->switch["backup"] = $bool;
				if($bool){
					console("[Skywars] The world data is backuped.");
					$this->backupChest();
				}else{
					$this->restoreWorld();
				}
				break;
			case "lock.time":
				$time = $params;
				@$this->cancelSchedule($this->s_id["time"]);
				if($time === false){
					$this->setTime(1000);
				}else{
					$this->setTime($this->config["lock-time"]);
					$this->s_id["time"] = $this->schedule(300, "setTime", $this->config["lock-time"], true);
				}
				break;
		}
	}

	public function setGamesSchedule($fix = 0){
		$cfg = $this->config["times"];
		$lobby = $cfg["lobby"] + $fix;
		$invincible = $lobby + $cfg["invincible"];
		$play = $invincible + $cfg["play"];
		$finish = $play + $cfg["finish"];
		$this->s_id["invincible"] = $this->schedule($lobby, "gameInvincible", array());
		$this->s_id["play"] = $this->schedule($invincible, "gamePlay", array());
		$this->s_id["finish"] = $this->schedule($play, "gameFinish", array());
	}

	public function setPlayers(){
		$this->players = array();
		$players = $this->api->player->getAll();
		if(count($players) !== 0){
			foreach($players as $player){
				if($player->gamemode === 0){ //survival*
					$this->players[$player->username] = $player;
				}
			}
		}
		if(count($this->players) <= 1){
			return false;
		}
	}

	public function setField($field = false){
		if(count($this->config["field"]) == 0){
			console(FORMAT_YELLOW."[Skywars] There is no field data! You have to add a field at first.");
			console("[Skywars] Usage: /sky addfield <field name>");
			return false;
		}
		if($field === false){
			$field = $this->setFieldAutomatically();
			$this->field = $this->config["field"][$field];
			$this->field["stage"] = false;
			if($field === false){
				console(FORMAT_YELLOW."[Skywars] There is no proper field data! You have to add a field at first.");
				console("[Skywars] Usage: /sky addfield <field name>");
				return false;
			}
		}elseif($this->fieldExists($field)){
			$this->field = $this->config["field"][$field];
			$this->field["stage"] = $field;
		}else{
			console(FORMAT_YELLOW."[Skywars] \"".$field."\" doesn't exist!");
			return false;
		}
		if(!$this->testField($field)){
			console(FORMAT_YELLOW."[Skywars] \"".$field."\" has some incomplete parts!");
			return false;
		}
		$this->broadcast("[Skywars] Next stage is selected to \"".$field."\"!");
		return true;
	}

	public function setFieldAutomatically(){
		$fields = $this->config["field"];
		while(true){
			$field = array_rand($fields);
			if($this->testField($field)){
				break;
			}
			unset($fields[$field]);
			if(count($fields) < 1){
				return false;
			}
		}
		return $field;
	}

	public function testField($field){
		$map = $this->config["field"][$field];
		if(is_numeric($map["lobby"][0]) and is_numeric($map["lobby"][1]) and is_numeric($map["lobby"][2])){
			if(count($map["start"] > 0)){
				foreach($map["start"] as $c){
					if(!is_numeric($c[0]) or !is_numeric($c[1]) or !is_numeric($c[2])){
						return false;
					}
				}
				return true;
			}
		}
		return true;
	}

	public function readyScore(){
		$this->score = array();
		if(count($this->players) === 0)	return;
		foreach($this->players as $player){
			$this->score[$player->username] = array("kill" => array(), "cause" => " - ", "exp" =>(int) 0);
		}
	}

	public function getLobby(){
		$p = $this->field["lobby"];
		$level = $this->getFieldLevel();
		$lobby = new Position($p[0], $p[1], $p[2], $level);
		return $lobby;
	}

	public function getStartPoints(){
		$return = array();
		$level = $this->getFieldLevel();
		foreach($this->field["start"] as $p){
			$return[] = new Position($p[0], $p[1], $p[2], $level);
		}
		return $return;
	}

	public function getFieldLevel(){
		if(empty($this->field))	return false;
		if($this->field["level"] === false)	return $this->api->level->getDefault();
		$level = $this->api->level->get($this->field["level"]);
		if($level === false){
			console(FORMAT_YELLOW."[Skywars] level: \"".$field."\" doesn't exist!");
			$this->gameStop();
		}
		return $level;
	}

	public function teleportAllPlayers($point){
		$level = $this->getFieldLevel();
		$allPlayers = $this->api->player->getAll();
		$players = array();
		foreach($allPlayers as $player){
			array_push($players, $player);
		}
		$maxPlayers = $this->server->api->getProperty("max-players");
		if(count($players) == 0) return false;
		if($level === false) return false;
		switch($point){
			case "field":
				//console(count($this->getStartPoints()).'!=='.$maxPlayers);
				if(count($this->getStartPoints()) !== $maxPlayers){
					foreach($players as $player){
						$randPoint = array_rand($this->field["start"]);
						//console($player->username.' spawned in '.$randPoint);
						$s = $this->field["start"][$randPoint];
						$position = new Position($s[0], $s[1], $s[2], $level);
						$player->teleport($position);
					}
				}
				else{
					for($i = 0; $i <= $maxPlayers; $i++){
						$player = $players[$i];
						if(!isset($player)) break;
						//console($player->username.' spawned in '.$i);
						$s = $this->field["start"][$i];
						$position = new Position($s[0], $s[1], $s[2], $level);
						$player->teleport($position);
					}
				}
				break;
			case "lobby":
				$s = $this->field["lobby"];
				$position = new Position($s[0], $s[1], $s[2], $level);
				foreach($players as $player){
					$player->teleport($position);
					$player->setSpawn($position);
				}
				break;
		}
	}

	public function backupLevel($type, $data){
		switch($type){
			case "break":
				$block = $data["target"];
				break;
			case "place":
				$block = new AirBlock();
				$block->position(new Position($data["block"]->x, $data["block"]->y, $data["block"]->z, $data["block"]->level));
				break;
		}
		$this->backup["world"][] = $block;
	}

	public function backupChest(){
		if(count($this->api->tile->getAll()) == 0) break;
		foreach($this->api->tile->getAll() as $tile){
			if($tile->class === TILE_CHEST){
				$pos = new Position($tile->x, $tile->y, $tile->z, $tile->level);
				$this->backup["chest"][] = array("pos" => $pos);
			}
		}
	}

	public function restoreWorld(){
		if(count($this->backup["world"]) !== 0){
			$blocks = array_reverse($this->backup["world"]);
			foreach($blocks as $block){
				$block->level->setBlockRaw($block, $block);
			}
		}
		if(count($this->backup["chest"]) !== 0){
			foreach($this->backup["chest"] as $chest){
				$tile = $this->api->tile->get($chest["pos"]);
				if($tile === false){
					$tile = $this->api->tile->add($chest["pos"]->level, TILE_CHEST, $chest["pos"]->x, $chest["pos"]->y, $chest["pos"]->z);
				}
				if(($tile instanceof Tile) and $tile->class === TILE_CHEST){
				}
			}
		}
		$this->backup = array("world" => array(), "chest" => array());
	}

	public function countdown($int){
		$time = $int;
		$counts = array();
		switch($time){
			case($time >= 60):
				$counts = array_merge($counts, $this->getMultiple($time, 60));
				$time = (int) 59;
			case($time >= 10):
				$counts = array_merge($counts, $this->getMultiple($time, 10));
				$time = (int) 9;
			case($time >= 5):
				$counts = array_merge($counts, $this->getMultiple($time, 5));
				$time = (int) 4;
			case($time >= 1):
				$counts = array_merge($counts, $this->getMultiple($time, 1));
				break;
			default:
				return;
		}
		asort($counts);
		if(!in_array($int, $counts)){
			$this->showTimelimit($int);
		}
		$this->cancelCountSchedule();
		foreach($counts as $cnt){
			$this->s_id["count"][] = $this->schedule(($int - $cnt), "showTimelimit", $cnt);
		}
	}

	public function chestRefillInGame(){
		$this->chestRefill();
		$this->broadcast("[Skywars] All chests are refilled!");
	}

	public function showTimelimit($time){
		if(!$this->formatTime($time)){
			return;
		}
		$players = $this->api->player->getAll();
		switch($this->status){
			case "lobby":
				foreach($players as $player){
					$player->sendChat("Next tournament will start in ".$time.".");
				}
				break;
			case "invincible":
				foreach($players as $player){
					$player->sendChat("Invincibility wears off in ".$time.".");
				}
				break;
			default:
				foreach($players as $player){
					$player->sendChat($time." remaining.");
				}
		}
	}

	public function getMultiple($int, $mlt){
		$arg =(int) $mlt;
		$return = array();
		while($arg <= $int){
			if(($arg % $mlt) == 0){
				$return[] = $arg;
			}
			++$arg;
		}
		return $return;
	}

	public function showState(&$output){
		$output .= FORMAT_AQUA."[Skywars] The tournament's state:".FORMAT_RESET."   [Remnants: ".FORMAT_GREEN."".count($this->players).FORMAT_RESET."/".FORMAT_GREEN."".count($this->score).FORMAT_RESET." players]\n";
		foreach($this->score as $user => $score){
			if(isset($this->players[$user])){
				$output .= FORMAT_GREEN." ";
			}else{
				$output .= FORMAT_RED." ";
			}
			$kill = count($score["kill"]);
			$output .= $user."   ".FORMAT_RESET."[kill: ".$kill." death: ".$score["cause"]."]\n";
		}
	}

	public function showRecords(&$output){
		$records = $this->getRecords();
		foreach($records as $rec){
			$kd = $this->kdFormula($rec["kill"], $rec["death"]);
			$name = substr($rec["username"], 0, 15);
			$name = $name . str_repeat(" ", 15 - strlen($name));
			$output .= $name."| level:".FORMAT_AQUA.$rec["level"].FORMAT_RESET." exp:".FORMAT_LIGHT_PURPLE.$rec["exp"].FORMAT_RESET." playing:".FORMAT_GREEN.$rec["times"].FORMAT_RESET." win:".FORMAT_DARK_AQUA.$rec["win"].FORMAT_RESET." kill:".FORMAT_YELLOW.$rec["kill"].FORMAT_RESET." death:".FORMAT_RED.$rec["death"].FORMAT_RESET." k/d:".FORMAT_BLUE.$kd.FORMAT_RESET."\n";
		}
	}

	public function showAccountInfo(Player $player){
		if($player->gamemode !== 0) return;
		$username = $player->username;
		$rec = $this->getAccount($username);
		$server = $this->api->getProperty("server-name");
		$kd = $this->kdFormula($rec["kill"], $rec["death"]);
		$quota = $this->levelFormula($rec["level"] + 1);
		//$coin = (string) $this->kit->grantPocketCash($username);
		$player->sendChat("xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
		$player->sendChat("PocketSkywars server:");
		$player->sendChat("             ".$server);
		$player->sendChat("name: ".$username."   level: ".$rec["level"]."  exp: ".$rec["exp"]."/".$quota);
		$player->sendChat("win: ".$rec["win"]."  kill: ".$rec["kill"]."  death: ".$rec["death"]."  k/d: ".$kd."  coin: /money");
		$player->sendChat("xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
	}

	public function showFieldInfo($field){
		if(!$this->fieldExists($field)){
			return false;
		}
		if($this->config["field"][$field]["level"] === false){
			$levelname = "==========";
		}else{
			$levelname = "LEVEL: \"".$this->config["field"][$field]["level"]."\" ";
		}
		$map = $this->config["field"][$field];
		if(!isset($map["lobby"][0])){
			$map["lobby"] = array(0 => "-", 1 => "-", 2 => "-");
		}
		if(count($map["start"]) == 0){
		}else{
			foreach($map["start"] as $i => $point){
				if(!isset($point[0])){
					$point = array(0 => "-", 1 => "-", 2 => "-");
				}
				console(FORMAT_YELLOW."  No.".$i.FORMAT_RESET.": (x ".$point[0].",y ".$point[1].",z ".$point[2].")");
			}
		}
	}

	public function fieldExists($field){
		if(!isset($this->config["field"][$field])){
			return false;
		}
		return true;
	}

	public function schedule($ticks, $method, $args = array(), $repeat = false){
		$id = $this->count_id;
		$ticks = $ticks * CONVERT_COEFFICIENT;
		$this->schedule[$id] = array($ticks, $method, $args, $repeat);
		$this->api->schedule($ticks, array($this, "callback"), array($method, $args), $repeat, $id);
		$this->count_id++;
		return $id;
	}

	public function cancelSchedule($id){
		unset($this->schedule[$id]);
	}

	public function cancelCountSchedule(){
		if(count($this->s_id["count"]) == 0) return false;
		foreach($this->s_id["count"] as $id){
			$this->cancelSchedule($id);
		}
		$this->s_id["count"] = array();
	}

	public function cancelAllSchedules(){
		$this->schedule = array();
	}

	public function getSchedule($id){
		if(!isset($this->schedule[$id]))return false;
		return $this->schedule[$id];
	}

	public function callback($args, $id){
		$schedule = $this->getSchedule($id);
		if($schedule === false) return false;
		$method = $args[0];
		$params =(Array) $args[1];
		@call_user_func_array(array($this, $method), $params);
		if($schedule[3] === false){
			unset($this->schedule[$id]);
		}
	}

	public function placePointMarker(){
		$sign = new SignPostBlock();
		$line2 = "";
		$line3 = "START POINT";
		foreach($this->config["field"] as $field => $data){
			if($data["level"] === false){
				$level = $this->api->level->getDefault();
			}else{
				$level = $this->api->level->get($data["level"]);
				if($level === false){
					console(FORMAT_YELLOW."[Skywars] ".$field."'s level doesn't exist!".FORMAT_RESET."");
					continue;
				}
			}
			$line1 = "Field: ".$field;
			foreach($data["start"] as $no => $p){
				$line4 = "No.".$no;
				$level->setBlock(new Vector3($p[0], $p[1], $p[2]), $sign, false, true, true);
				$this->api->tile->addSign($level, $p[0], $p[1], $p[2], array($line1, $line2, $line3, $line4));
			}
			$p = $data["lobby"];
			$level->setBlock(new Vector3($p[0], $p[1], $p[2]), $sign, false, true, true);
			$this->api->tile->addSign($level, $p[0], $p[1], $p[2], array($line1, $line2, "LOBBY", ""));
		}
	}

	public function breakPointMarker(){
		$air = new AirBlock();
		foreach($this->config["field"] as $field => $data){
			if($data["level"] === false){
				$level = $this->api->level->getDefault();
			}else{
				$level = $this->api->level->get($data["level"]);
				if($level === false){
					continue;
				}
			}
			foreach($data["start"] as $no => $p){
				$vector = new Vector3($p[0], $p[1], $p[2]);
				if($level->getBlock($vector)->getID() === SIGN_POST){
					$level->setBlockRaw($vector, $air);
					$this->api->tile->remove($this->api->tile->get(new Position($vector, false, false, $level))->id);
				}

			}
			$p = $data["lobby"];
			$vector = new Vector3($p[0], $p[1], $p[2]);
			if($level->getBlock($vector)->getID() === SIGN_POST){
				$level->setBlockRaw($vector, $air);
				$this->api->tile->remove($this->api->tile->get(new Position($vector, false, false, $level))->id);
			}
		}
	}

	public function healAllPlayers(){
		$players = $this->api->player->getAll();
		if(count($players) == 0) return;
		foreach($players as $player){
			if($player->entity instanceof Entity and $player->entity->class === ENTITY_PLAYER){
				$player->entity->heal(20);
			}
		}
	}

	public function cleanDropedItems(){
		$entities = $this->api->entity->getAll();
		if(count($entities) == 0) return;
		foreach($entities as $e){
			if($e->class === ENTITY_ITEM){
				$e->close();
			}
		}
	}

	public function confiscateItems(){
		$players = $this->api->player->getAll();
		if(count($players) == 0) return;
		$air = BlockAPI::getItem(AIR, 0, 0);
		foreach($players as $player){
			foreach($player->inventory as $s => $item){
				if($item->getID() !== Air){
					$player->inventory[$s] = $air;
				}
			}
			$player->armor = array($air, $air, $air, $air);
			$player->sendInventorySlot();
			$player->sendArmor($player);
		}
	}

	public function lobbyAnnounce(){
		$players = $this->api->player->getAll();
		if(count($players) == 0) return;
		else{
			$msg = $this->config["announce"];
			if(count($msg) === 0) return;
			$no = rand(0, count($msg) - 1);
			foreach($players as $player){
				$player->sendChat("[TIPS] ".$msg[$no]);
			}
		}
	}

	public function givePrize($username){
		$amount = (int) $this->config["prize"];
		$data = array(
			"issuer" => "PocketSkywars",
			"username" => $username,
			"method" => "grant",
			"amount" => $amount,
		);
		if($this->api->dhandle("money.handle", $data) === true)	return true;
		return false;
	}

	public function giveEXP($user, $point){
		$this->score[$user]["exp"] += $point;
		$this->api->chat->sendTo(false, "You got $point exp!", $user);
	}

	public function setTime($time){
		foreach($this->config["field"] as $field => $data){
			if($data["level"] === false){
				$level = $this->api->level->getDefault();
			}else{
				$level = $this->api->level->get($data["level"]);
			}
			$this->api->time->set($time, $level);
		}
	}

	public function broadcast($message, $whitelist = false, $linebreak = false){
		if($linebreak === true){
			$message = $this->lineBreak($message);
		}
		$this->api->chat->broadcast($message);
	}

	public function kick($player, $reason = "died", $msg1 = true, $msg2 = false){
		if(!($player instanceof Player)){
			$player = $this->api->player->get($player);
			if(!($player instanceof Player)) return false;
		}
		$player->close($reason, $msg2);
		if($msg1 == true) $this->broadcast($player->username." has been kicked: ".$reason);
	}

	public function createConfig(){
		$config = array(
				"prize" => 100,
				"game-world-protect" => false,
				"lock-time" => "day",
				"world-protect" => false,
				"times" => array(
						"lobby" => 120,
						"play" => 600,
						"chest-refill" => 300,
						"invincible" => 30,
						"finish" => 30,
				),
				"exp" => array(
						"kill" => 20,
						"win-tournament" => 50,
				),
				"announce" => array(
						"Your inventory will be emptied when game begins.",
						"Original developer is @omattyao_yk",
						"You should not carry any items in the game.",
						"This game is not the team system. All the others are enemies.",
						"Who survived at the very end will be the winner.",
						"Plugin fixed by ArkQuark",
						"Coin has been provided when you joined.",
				),
				"chest-loot" => array(
					"stone" => array(
						"meta" => 0,
						"min-count" => 2,
						"max-count" => 16,
						"rarity" => 'spawn',
						"chance" => 40,
						),
					"wooden_planks" => array(
						"meta" => 0,
						"min-count" => 4,
						"max-count" => 16,
						"rarity" => 'spawn',
						"chance" => 40,
					),
				),
				"field" => array(),
		);
		$this->path = $this->api->plugin->createConfig($this, $config);
		$this->config = $this->api->plugin->readYAML($this->path."config.yml");
		$this->chestConfig = new Config($this->api->plugin->configPath($this)."chests.yml", CONFIG_YAML, array());
	}

	public function writeConfig(){
		$this->api->plugin->writeYAML($this->path."config.yml", $this->config);
	}

	private function formatBool(&$bool){
		$bool = strtoupper($bool);
		switch($bool){
			case "TRUE":
			case "ON":
			case "1":
				$bool = (boolean) true;
				break;
			case "FALSE":
			case "OFF":
			case "0":
				$bool = (boolean) false;
				break;
			default:
				return false;
		}
		return true;
	}

	private function formatTime(&$s){
		$time = "";
		if($s == 0){
			$time = "0 second";
			return false;
		}
		$ms = array(floor($s / 60), $s - floor($s / 60) * 60);
		$hm = array(floor($ms[0] / 60), $ms[0] - floor($ms[0] / 60) * 60);
		if($hm[0] >= 2){
			$time .= "$hm[0] hours ";
		}elseif($hm[0] == 1){
			$time .= "$hm[0] hour ";
		}
		if($hm[1] >= 2){
			$time .= "$hm[1] minutes ";
		}elseif($hm[1] == 1){
			$time .= "$hm[1] minute ";
		}
		if($ms[1] >= 2){
			$time .= "$ms[1] seconds ";
		}elseif($ms[1] == 1){
			$time .= "$ms[1] second ";
		}
		$s = trim($time);
		return true;
	}

	private function isAlnum($text){
		if(preg_match("/^[a-zA-Z0-9]+$/",$text)){
			return true;
		}else{
			return false;
		}
	}

	private function lineBreak($str, $length = LINE_BREAK){
		$result = implode("\n", str_split($str, $length));
		return $result;
	}

	private function kdFormula($kill, $death){
		if($kill === 0 and $death === 0){
			$kd = " - ";
		}elseif($kill === 0){
			$kd =(String) "0.00";
		}else{
			$kd = bcdiv($kill, $kill + $death, 2);
		}
		return $kd;
	}

	private function levelFormula($level){
		$quota  = round((4 *(pow(1.4, $level) - 1.4) / 0.7) * 10);
		return $quota;
	}

	private function levelCheck($level, $exp){
		$quota = $this->levelFormula($level + 1);
		while($exp >= $quota){
			++$level;
			$exp -= $quota;
			$quota = $this->levelFormula($level + 1);
		}
		return array("level" => $level, "exp" => $exp, "quota" => $quota);
	}

	private function loadDB(){
		$this->db = new SQLite3($this->api->plugin->configPath($this) . "record.sqlite3");
		$this->db->exec(
				"CREATE TABLE IF NOT EXISTS records(
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				username TEXT NOT NULL,
				lastjoin TEXT,
				times INTEGER NOT NULL DEFAULT '0',
				win INTEGER NOT NULL DEFAULT '0',
				kill INTEGER NOT NULL DEFAULT '0',
				death INTEGER NOT NULL DEFAULT '0',
				lose INTEGER NOT NULL DEFAULT '0',
				exp INTEGER NOT NULL DEFAULT '0',
				level INTEGER NOT NULL DEFAULT '1'
		)"
		);
	}

	private function record($winner, $scores){
		if($winner !== false){
			$this->db->exec("UPDATE records SET win = win + 1, lose = lose - 1, death = death - 1 WHERE username = '" . $winner . "';");
		}
		$stmt = $this->db->prepare("UPDATE records SET times = times + 1, exp = exp + :exp, kill = kill + :kill, lose = lose + 1, death = death + 1, lastjoin = datetime('now', 'localtime') WHERE username = :username");
		foreach($scores as $username => $score){
			$stmt->clear();
			$kill = count($score["kill"]);
			$stmt->bindValue(":exp", $score["exp"]);
			$stmt->bindValue(":kill", $kill);
			$stmt->bindValue(":username", $username);
			$stmt->execute();
			$rec = $this->getAccount($username);
			$result = $this->levelCheck($rec["level"], $rec["exp"]);
			$this->db->exec("UPDATE records SET level = '".$result["level"]."', exp = '".$result["exp"]."' WHERE username = '".$username."';");
		}
		$stmt->close();
		foreach($scores as $username => $score){
			$rec = $this->getAccount($username);
		}
	}

	private function getRecords(){
		$records = array();
		$result = $this->db->query("SELECT * FROM records");
		while($res = $result->fetchArray(SQLITE3_ASSOC)){
			$records[] = $res;
		}
		return $records;
	}

	private function createAccount($username){
		$this->db->exec("INSERT INTO records(username) VALUES('" . $username . "')");
	}

	private function getAccount($username){
		$result = $this->db->query("SELECT * FROM records WHERE username = '" . $username . "';")->fetchArray(SQLITE3_ASSOC);
		if($result === false)	return false;
		return $result;
	}

	public function readyKit(){
		$this->kit = new KitManager($this->api);
		$this->kit->DB($this->path);
	}

	public function __destruct(){
		if($this->status !== false){
			$this->gameStop();
		}
		//$this->db->close();
	}

}

class KitManager{
	private $api, $config, $kitdb, $coin, $players;

	public function __construct(ServerAPI $api, $server = false){
		$this->api = $api;
		$this->coin = array();
		$this->players = array();
		$this->config = array(
				"max-kit" => 5,
				"max-item" => 5,
				"max-skill" => 5,
		);
	}

	public function buy($user, $level, $kitname){
		$output = "";
		if($user instanceof Player){
			$user = $user->username;
		}
		$coin = $this->grantPocketCash($user);
		$kit = $this->get($kitname);
		if($kit === false){
			$output .= "The kit \"$kitname\" doesn't exist!\n";
		}else if($coin < $kit["price"]){
			$output .= "You are short of coin to buy this kit!\n";
			$output .= "coin: $coin,  price: ".$kit["price"]."\n";
		}elseif($level < $kit["level"]){
			$output .= "You are short of level to buy this kit!\n";
			$output .= "your level: $level,  kit's level ".$kit["level"]."\n";
		}else{
			if($this->setEquipment($user, $kit["name"]) !== false){
				$this->grantPocketCash($user, -$kit["price"]);
				$output .= "You bought \"$kitname\" !\n";
			}
		}
		return $output;
	}

	public function equip($user){
		$player = $this->api->player->get($user);
		$eq = $this->getEquipment($user);
		foreach($eq as $kitname){
			if($this->players[$user][$kitname] === false)	continue;
			$kit = $this->get($kitname);
			for($i = 0; $i <= 4; $i++){
				if(!empty($kit["id".$i])){
					$player->addItem($kit["id".$i], $kit["meta".$i], $kit["count".$i], true);
				}
				/*
				 if(!empty($kit["skill".$i])){
				}
				*/
				$this->players[$user][$kitname] = false;
			}
		}
	}

	public function setEquipment($user, $kitname){
		$eq = $this->getEquipment($user);
		if(count($eq) >= $this->config["max-kit"]){
			$this->api->chat->sendTo(false, "You cannot add the kits anymore.", $user);
			return false;
		}
		if(in_array($kitname, $this->players[$user])){
			$this->api->chat->sendTo(false, "You cannot buy the same kit.", $user);
			return false;
		}
		$this->players[$user][$kitname] = true;
		return true;
	}

	public function getEquipment($user){
		if(empty($user))	return false;
		if(!isset($this->players[$user])){
			$this->players[$user] = array();
		}
		return array_keys($this->players[$user]);
	}

	public function add($name, $price, $level){
		$kit = $this->get($name);
		if($kit !== false){
			return false;
		}
		$level =(Int) max(1, $level);
		$price =(Int) max(0, $price);
		$this->kitdb->exec("INSERT INTO kits(name, price, level) VALUES('".$name."', '".$price."', '".$level."');");
		/*
		 for($i = 0; $i <= 4; $i++){
		if(isset($sets["item"][$i]["id"]) and isset(Item::$class[$sets["items"][$i]["id"]])){
		$k = $sets["item"][$i];
		$this->kitdb->exec("UPDATE kits SET id'".$i."' = '".$k["id"]."', meta'".$i."' = '".$k["meta"]."', count'".$i."' = '".$k["count"]."' WHERE name = '".$name."';");
		}
		if(isset($sets["skill"][$i]) and $this->getskill($sets["skill"][$i]) === false){
		$this->kitdb->exec("UPDATE kits SET skill'".$i."' = '".$sets["skill"][$i]."' WHERE name = '".$name."';");
		}
		}
		*/
		return true;
	}

	public function editItem($mode, $param, $sets){
		switch($mode){
			case "add":
				$kit = $this->get($param);
				for($i = 0; $i <= 4; $i++){
					if(empty($kit["id".$i])){
						$this->kitdb->exec("UPDATE kits SET id".$i." = '".$sets["id"]."',  meta".$i." = '".$sets["meta"]."', count".$i." = '".$sets["count"]."' WHERE name = '".$kit["name"]."';");
						return true;
					}
				}
				console(FORMAT_RED."[Skywars] cannot add items anymore.");
				return false;
			case "remove":
				$slot =(Int) $param;
				break;
		}
	}

	public function remove($name){
		if($this->get($name) === false)	return false;
		$this->kitdb->exec("DELETE FROM kits WHERE LOWER(name) = LOWER('".$name."');");
		return true;
	}

	public function get($name){
		$kit = $this->kitdb->querySingle("SELECT * FROM kits WHERE LOWER(name) = LOWER('".$name."');", true);
		if(empty($kit))	return false;
		return $kit;
	}

	public function getAll(){
		$kits = array();
		$result = $this->kitdb->query("SELECT * FROM kits;");
		while($kit= $result->fetchArray(SQLITE3_ASSOC)){
			$kits[] = $kit;
		}
		return $kits;
	}

	public function grantPocketCash($user, $coin = 0){ //need fix
		if(!array_key_exists($user, $this->coin)){
			$this->coin[$user] = DEFAULT_COIN;
		}
		$this->coin[$user] += $coin;
		return $this->coin[$user];
	}

	public function showKitInfo($kitname){
		$kit = $this->get($kitname);
		if($kit === false){
			console(FORMAT_YELLOW."[Skywars] The kit \"$kitname\" doesn't exist!");
		}
		console(FORMAT_AQUA."===KIT: \"".$kitname."\"  =======================");
		console(FORMAT_GREEN."#ITEM");
		for($i = 0; $i <= 4; $i++){
			$info = FORMAT_YELLOW."  slot".$i.FORMAT_RESET.":  ";
			if($kit["id".$i] === null){
				$info .= "    -";
			}else{
				$info .= "(id ".$kit["id".$i].", meta ".$kit["meta".$i].", count ".$kit["count".$i].")";
			}
			console($info);
		}
		console(FORMAT_GREEN."#skill");
		for($i = 0; $i <= 4; $i++){
			$info = FORMAT_YELLOW."  slot".$i.FORMAT_RESET.":  ";
			if($kit["skill".$i] === null){
				$info .= "    -";
			}else{
				$info .= "\"".$kit["skill".$i]."\"";
			}
			console($info);
		}
	}

	public function showList(&$output){
		$kits = $this->kitdb->query("SELECT name, price, level FROM kits;");
		if(!empty($kits)){
			$output .= "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n";
			$output .= "Kits list:\n";
			$strings = "";
			while($kit = $kits->fetchArray(SQLITE3_ASSOC)){
				$strings .= $kit["name"].'($'.$kit["price"].':#'.$kit["level"].") ";
			}
			$output .= $this->lineBreak($strings);
			$output .= "\n \n";
		}else{
			$output .= "There is no kits.\n";
		}
	}

	public function showAccountInfo(Player $player){
		$user = $player->username;
		$eq = $this->getEquipment($user);
		$coin = $this->grantPocketCash($user);
		$player->sendChat("xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\n");
		$output = "coin: /money"."   kit: ";
		if(empty($eq)){
			$output .= "-";
		}else{
			foreach($eq as $kitname){
				$output .= $kitname." ";
			}
		}
		$output .= "\n \n";
		$player->sendChat($output);
	}

	public function resetParams(){
		$this->players = array();
		$this->coin = array();
	}

	public function DB($path){
		$this->kitdb = new SQLite3($path . "kit.sqlite3");
		$this->kitdb->exec(
				"CREATE TABLE IF NOT EXISTS kits(
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name TEXT NOT NULL,
				price INTEGER NOT NULL,
				level INTEGER NOT NULL DEFAULT '1',
				id0 INTEGER, id1 INTEGER, id2 INTEGER, id3 INTEGER, id4 INTEGER,
				meta0 INTEGER, meta1 INTEGER, meta2 INTEGER, meta3 INTEGER, meta4 INTEGER,
				count0 INTEGER, count1 INTEGER, count2 INTEGER, count3 INTEGER, count4 INTEGER,
				skill0 INTEGER, skill1 INTEGER, skill2 INTEGER, skill3 INTEGER, skill4 INTEGER
		)"
		);
	}

	private function lineBreak($str, $length = LINE_BREAK){
		$result = implode("\n", str_split($str, $length));
		return $result;
	}

	public function __destruct(){
		$this->kitdb->close();
	}
}
