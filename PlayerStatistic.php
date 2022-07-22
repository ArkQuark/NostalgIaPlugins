<?php
  /*
__PocketMine Plugin__
name=PlayerStatistic
description=Statictic players ingame time
version=0.6.0
author=ArkQuark
class=PlayerStats
apiversion=12.1
*/

class PlayerStats implements Plugin{
	
	public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
	}
	
	public function init(){
		//date_default_timezone_set('Europe/Minsk');
		$this->config = new Config($this->api->plugin->configPath($this)."times.yml", CONFIG_YAML, array());
		$this->api->schedule(60*20, array($this, "checkOnline"), array(), true);
		$this->api->event("player.join", array($this, "event"));
		//$this->api->event("player.quit", array($this, "event"));
		$this->api->console->register("mytime", "Check ingame time!", array($this, "commandHandler"));
		$this->api->ban->cmdWhitelist("mytime");
		$this->convertNicknames();
	}
	
	public function event(&$data, $event){
		switch($event){
			case 'player.join':
				$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
				$username = $data->username;
				if(!isset($cfg[$username])){
					$cfg[$username] = 0;
					$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."times.yml", $cfg);
				}
				break;
			/*case 'player.quit':
				break;*/
		}
	}
	
	public function convertNicknames(){
		$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
		foreach($cfg as $nickname => $time){
			unset($cfg[$nickname]);
			$cfg[strtolower($nickname)] = $time;
			$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."times.yml", $cfg);
		}
	}
	
	public function checkOnline(){
		//console(date('h:i:s'));
		$players = $this->api->player->getAll();
		if(count($players) > 0){
			foreach($players as $player){
				$this->updateConfig($player);
			}
		}
	}
	
	public function top(){
		$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
		arsort($cfg, SORT_NUMERIC);
		$array = array();
		foreach($cfg as $username => $time){
			array_push($array, array($username => $time));
		}
		return $array;
	}
	
	public function updateConfig(Player $player){
		$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
		$username = strtolower($player->username);
		++$cfg[$username];
		$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."times.yml", $cfg);
	}
	
	private function formatTime(&$m){
		$time = "";
		if($m == 0){
			$time = "0 minutes";
			return $time;
		}	
		
		$d = (int) ($m / 1440);
		if($d > 0) $h = (int) (fmod($m, 1440) / 60);
		else $h = (int) ($m / 60);
		$m = fmod($m, 60);
		
		if($d == 1) $time .= $d." day ";
		elseif($d > 1) $time .= $d." days ";
			
		if($h == 1) $time .= $h." hour ";
		elseif($h > 1) $time .= $h." hours ";
			
		if($m == 1) $time .= $m." minute";
		elseif($m == 0) $time = rtrim($time, " ");
		elseif($m > 1) $time .= $m." minutes";

		return $time;
	}
	
	public function commandHandler($cmd, $args, $issuer, $alias){
		$output = '';
		switch($cmd){
			case 'mytime':
				switch($args[0]){
					case "help":
						$output .= "/mytime top - Players ingame time top\n/mytime see <nickname> - See player's ingame time\n/mytime - Your ingame time";
						break;
					case "top":
						$top = $this->top();
						for($i = 0; $i < 5; $i++){
							foreach($top[$i] as $username => $time){
								$pTime = $this->formatTime($time);
								$output .= "[№".($i+1)."] ".$username." (".$pTime.")\n";
							}
						}
						break;
					case "see":
						$username = strtolower($args[1]);
						if($username == ""){
							$output .= "Usage: /mytime see <nickname>";
							break;
						}
						$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
						if(!isset($cfg[$username])){
							$output .= "This Player doesn't exist!";
							break;
						}
						else{
							$time = $cfg[$username];
							$output .= $username."'s ingame time: ".$this->formatTime($time);
						}
						break;
					case "":
						if(!$issuer instanceof Player){
							$output .= "Please run this command ingame!";
							break;
						}
						$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
						$time = $this->formatTime($cfg[strtolower($issuer->username)]);
						$output .= "Your ingame time: ".$time;
						break;
				}
		}
		return $output;
	}
	
	public function __destruct(){
    }
}