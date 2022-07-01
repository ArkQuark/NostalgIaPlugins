<?php
  /*
__PocketMine Plugin__
name=ProjectRubyINFO
version=0.3.8 [SkywarsUpdate]
author=ArkQuark
class=ProjectRuby
apiversion=12.1
*/

class ProjectRuby implements Plugin{
	
	public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
		$this->server = ServerAPI::request();
		$this->prefix = "[INFO] ";
		
		$this->langEN = array(
		"Server working on NostalgiaCore ".MAJOR_VERSION,
		"If you seen a bug just /bug",
		"Server ip saint.mcpehost.ru:20001",
		"Join to our discord server https://discord.gg/fzyBQCuwVj",
		"Vote server on monitoring",
		"Check your ingame time /mytime",
		"Ingame time top: /mytime top",
		);
		
		$this->langRU = array(
		"Сервер работает на NostalgiaCore ".MAJOR_VERSION,
		"Если вы заметили баг напишите /bug",
		"Айпи сервера saint.mcpehost.ru:20001",
		"Заходите на наш дискорд сервер https://discord.gg/fzyBQCuwVj",
		"Голосуйте за сервер на мониторинге",
		"Проверьте сколько вы наиграли на сервере /mytime",
		"Топ наигранного времени игроков: /mytime top",
		);
	}
	
	public function init(){
		$this->api->event("player.join", array($this, "event"));
		
		$this->lang = new Config($this->api->plugin->configPath($this)."lang.yml", CONFIG_YAML, array());
		$this->bugs = new Config($this->api->plugin->configPath($this)."bugs.yml", CONFIG_YAML, array());
		$this->api->schedule(5*60*20, array($this,"sayInfo"), array(), true);
		
		$this->api->console->register("lang", "<ru|en>", array($this, "command"));
		$this->api->console->register("bug", "<message>", array($this, "command"));
		$this->api->ban->cmdWhitelist("lang");
		$this->api->ban->cmdWhitelist("bug");
	}
	
	public function event(&$data, $event){
		switch($event){
			case 'player.join':
				$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
				$username = $data->username;
				if(!isset($lang[$username])){
					$this->api->chat->broadcast("$username joined first time!");
					$lang[$username] = 'en';
					$data->sendChat($this->prefix.'You change language for info messages.\nJust use /lang <en|ru>');
					$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."lang.yml", $lang);
				}
				break;
		}
	}
	
	public function command($cmd, $args, $issuer, $alias){
		$output = "";
		switch($cmd){
			case "lang":
				if(!($issuer instanceof Player)){
					$output .= "Please run this command in game.";
					break;
				}
				$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
				if($args[0] == 'ru') $lang[$issuer->username] = 'ru';
				if($args[0] == 'en') $lang[$issuer->username] = 'en';
				$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."lang.yml", $lang);
				$output .= "[/$cmd] Changed language for info messages";
				break;
			case "bug":
				if(!($issuer instanceof Player)){
					$output .= "Please run this command in game.";
					break;
				}
				if(count($args) == 0){
					$output .= "[/$cmd] You don't wrote a bug";
					break;
				}
				$message = join(" ", $args);
				$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "bugs.yml");
				array_push($cfg, array($issuer->username => $message));
				$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."bugs.yml", $cfg);
				$output .= "[/$cmd] Your message was saved! Thanks";
				break;
		}
		return $output;
	}
	
	public function sayInfo(){
		$players = $this->api->player->getAll();
		if(count($players) > 0){
			if(count($this->langEN) == count($this->langRU)) $randmsg = mt_rand(0, count($this->langEN)-1);
			else $randmsg = false;
			
			foreach($players as $player){
				$playerLang = $this->getPlayerLang($player->username);
				if($playerLang == 'en'){
					if($randmsg === false) $randmsg = mt_rand(0, count($this->langEN)-1);
					$player->sendChat($this->prefix.$this->langEN[$randmsg]);
				}
				elseif($playerLang == 'ru'){
					if($randmsg === false) $randmsg = mt_rand(0, count($this->langRU)-1);
					$player->sendChat($this->prefix.$this->langRU[$randmsg]);
				}
				else{
					if($randmsg === false) $randmsg = mt_rand(0, count($this->langEN)-1);
					$player->sendChat($this->prefix.$this->langEN[$randmsg]);
				}
			}
		}
	}
	
	public function getPlayerLang($username){
		$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
		return $lang[$username];
	}
	
	public function __destruct(){
    }
	
}