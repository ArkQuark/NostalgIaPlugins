<?php
  /*
__PocketMine Plugin__
name=ProjectRubyINFO
version=0.5.0
author=ArkQuark
class=ProjectRuby
apiversion=12.1
*/

class ProjectRuby implements Plugin{
	
	public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
		$this->server = ServerAPI::request();
		$this->prefix = "[INFO] ";
	}
	
	public function init(){
		$this->api->event("player.join", [$this, "event"]);
		
		$this->lang = new Config($this->api->plugin->configPath($this)."lang.yml", CONFIG_YAML, []);
		$this->bugs = new Config($this->api->plugin->configPath($this)."bugs.yml", CONFIG_YAML, []);
		$this->api->schedule(5*60*20/*change for test*/, [$this,"sayInfo"], [], true);
		
		$this->api->console->register("lang", "<ru|en>", [$this, "command"]);
		$this->api->console->register("bug", "<message>", [$this, "command"]);
		$this->api->ban->cmdWhitelist("lang");
		$this->api->ban->cmdWhitelist("bug");
		
		if(!file_exists($this->api->plugin->configPath($this)."mainConfig.yml")){
			console(FORMAT_RED."Please configure config files!".FORMAT_RESET);
		}
	
		$this->constantConfig = new Config($this->api->plugin->configPath($this)."mainConfig.yml", CONFIG_YAML, [
			"defaultLanguage" => "en",
			"serverip" => "0.0.0.0:19132",
			"discordlink" => "null",
			"ncVer" => MAJOR_VERSION,
		]);
		
		
		$this->langEN = new Config($this->api->plugin->configPath($this)."langEN.yml", CONFIG_YAML, [
			"Server works on NostalgiaCore %ver",
			"If you seen a bug just /bug",
			"Server ip: %ip",
			"We are in discord: %discordlink",
		]);
		
		$this->langRU = new Config($this->api->plugin->configPath($this)."langRU.yml", CONFIG_YAML, [
			"Сервер работает на NostalgiaCore %ver",
			"Если вы заметили баг напишите /bug",
			"Айпи сервера: %ip",
			"Наш дискорд сервер: %discordlink",
		]);
	}
	
	
	public function event(&$data, $event){
		switch($event){
			case 'player.join':
				$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
				$username = $data->username;
				if(!isset($lang[$username])){
					$this->api->chat->broadcast("$username joined first time!");
					$lang[$username] = $this->constantConfig["defaultLanguage"];
					$data->sendChat($this->parseMessage("You can change language for info messages.\nJust use /lang <en|ru>"));
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
					$output .= "Please run this command in game!";
					break;
				}
				$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
				if($args[0] == 'ru'){
					$lang[$issuer->username] = 'ru';
					$output .= "[/$cmd] Изменен язык для инфо сообщений";
				}
				if($args[0] == 'en'){
					$lang[$issuer->username] = 'en';
					$output .= "[/$cmd] Changed language for info messages";
				}
				$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."lang.yml", $lang);
				break;
			case "bug":
				if(!($issuer instanceof Player)){
					$output .= "Please run this command in game.";
					break;
				}
				$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
				$pLang = $lang[$issuer->username];
				if(count($args) == 0){
					if($pLang == 'en') $output .= "[/$cmd] You don't wrote a bug";
					elseif($pLang == 'ru') $output .= "[/$cmd] Вы не написали баг";
					break;
				}
				$message = join(" ", $args);
				$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "bugs.yml");
				array_push($cfg, [$issuer->username => $message]);
				$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."bugs.yml", $cfg);
				if($pLang == 'en') $output .= "[/$cmd] Your message was saved! Thanks";
				elseif($pLang == 'ru') $output .= "[/$cmd] Ваше сообщение было сохранено! Спасибо";
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
				switch($playerLang){
					case 'ru':
						if($randmsg === false) $randmsg = mt_rand(0, count($this->langRU)-1);
						$player->sendChat($this->parseMessage($this->langRU[$randmsg]));
						break;
					case 'en':
					default:
						if($randmsg === false) $randmsg = mt_rand(0, count($this->langEN)-1);
						$player->sendChat($this->parseMessage($this->langEN[$randmsg]));
						break;
				}
			}
		}
	}
	
	public function getPlayerLang($username){
		$lang = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "lang.yml");
		return $lang[$username] ?? $this->constantConfig["defaultLanguage"];
	}
	
	public function parseMessage($message){
		$parsed = $this->prefix;
		
		$patterns = ["/%ver/", "/%ip/", "/%discordlink/"];
		$replacements = [MAJOR_VERSION, $this->constantConfig["serverip"], $this->constantConfig["discordlink"]];
		
		$parsed .= preg_replace($patterns, $replacements, $message);
		
		return $parsed;
	}
	
	public function __destruct(){
    }
	
}