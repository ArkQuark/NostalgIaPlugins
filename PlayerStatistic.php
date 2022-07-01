<?php
  /*
__PocketMine Plugin__
name=PlayerStatistic
description=Статистика наигранных минут игроков
version=0.4.2 [SkywarsUpdate]
author=ArkQuark
class=PlayerStats
apiversion=12.1
*/

class PlayerStats implements Plugin{
	
	public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
	}
	
	public function init(){
		$this->config = new Config($this->api->plugin->configPath($this)."times.yml", CONFIG_YAML, array());
		$this->api->schedule(60*20, array($this, "checkOnline"), array(), true);
		$this->api->event("player.join", array($this, "event"));
		$this->api->event("player.quit", array($this, "event"));
		$this->api->console->register("mytime", "Check your ingame time!", array($this, "commandHandler"));
		$this->api->ban->cmdWhitelist("mytime");
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
			case 'player.quit':
				break;
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
		$username = $player->username;
		++$cfg[$username];
		$this->api->plugin->writeYAML($this->api->plugin->configPath($this)."times.yml", $cfg);
	}
	
	private function formatTime(&$m){
		$time = "";
		if($m == 0){
			$time = "0 minutes";
			return $time;
		}
		if($m < 60){
			if($m > 1){
				$time .= $m." minutes";
			}
			else{
				$time .= $m." minute";
			}
		}
		elseif($m > 59 and $m < 3601){
			$hm = array(floor($m / 60), $m - floor($m / 60) * 60);
			if($hm[0] >= 2){
				$time .= "$hm[0] hours";
			}elseif($hm[0] == 1){
				$time .= "$hm[0] hour";
			}
		}
		else{
			$d = array(floor($m / 3600), $m - floor($m / 3600) * 3600);
			if($d[0] >= 2){
				$time .= "$d[0] days";
			}elseif($d[0] == 1){
				$time .= "$d[0] day";
			}
		}
		return $time;
	}
	
	public function commandHandler($cmd, $args, $issuer, $alias){
		$output = '';
		switch($cmd){
			case 'mytime':
				if($args[0] == "top"){
					$top = $this->top();
					for($i = 0; $i < 5; $i++){
						foreach($top as $int => $array){
							if($int == $i){
								foreach($array as $username => $time){
									$ptime = $this->formatTime($time);
									$output .= "[№".($i+1)."] ".$username." (".$ptime.")\n";
								}
							}
						}
					}
				}
				elseif($args[0] == ""){
					if(!$issuer instanceof Player) break;
					$cfg = $this->api->plugin->readYAML($this->api->plugin->configPath($this). "times.yml");
					$time = $this->formatTime($cfg[$issuer->username]);
					$output .= "Your ingame time: ".$time;
				}
				break;
			
		}
		return $output;
	}
	
	public function __destruct(){
    }
}