<?php
/*
__PocketMine Plugin__
name=DevTools
description=Load .phar plugins from folder
version=1.1
author=NCDev
class=DevToolsG
apiversion=3,4,5,7,9,10,11,12,12.1
*/
/*
1.1
- Class name was changed to DevToolsG(to avoid conflicts with PMF DevTools plugin)
- PMMP 1.0.0 support
- PMMP 1.3.12 support
- - PharLoader compability
- API version will be checked automatically
1.0
- Initial release

*/
class DevToolsG implements Plugin{
 	public function __construct(ServerAPI $api, $server = false){
		$path = $this->pluginsPath($api->plugin);
		if(!is_dir("$path/NostalgiaPHAR_/")) mkdir("$path/NostalgiaPHAR_/", 0777, true);
		if(!is_file("$path/NostalgiaPHAR_/IClassLoader.php")){
			file_put_contents("$path/NostalgiaPHAR_/IClassLoader.php", '<?php interface IClassLoader{public function loadAll($pharPath);}');
		}
		if(!is_file("$path/NostalgiaPHAR_/PharUtils.php")){
			file_put_contents("$path/NostalgiaPHAR_/PharUtils.php", '<?php class PharUtils{public static function readMainConfig($content){$pluginData=[];$content=explode("\n",$content);foreach($content as $id=>$line){if(!strpos($line,"=")){continue;}$line=explode("=",$line);$content[$line[0]]=$line[1];}$pluginData["name"]=$content["name"];$pluginData["description"]=$content["description"];$pluginData["version"]=$content["version"];$pluginData["author"]=$content["author"];$pluginData["mainFile"]=$content["mainFile"];$pluginData["api"]=$content["api"];$pluginData["classLoader"]=$content["classLoader"];$pluginData["CLClass"]=self::getNameSpaceClass($pluginData["classLoader"]);return $pluginData;}public static function getNameSpaceClass($content){return substr(str_replace("/","\\\\",$content),0,-4);}}');
		}
		
		if(!class_exists("PharUtils")){
			include("$path/NostalgiaPHAR_/PharUtils.php");
		}
		if(!interface_exists("IClassLoader")){
			include("$path/NostalgiaPHAR_/IClassLoader.php");
		}
		
		$rc = new ReflectionClass('PluginAPI');
		$pluginProp = $rc->getProperty('plugins');
		$pluginProp->setAccessible(true);
		
 		
 		$dir = dir($path);
 		while(false !== ($file = $dir->read())){
 			if($file[0] === "."){
 				continue;
 			}
 			$p = $path.$file;
 			if(is_dir($p)){
 				$pluginInfo = [];
 				foreach(new DirectoryIterator($p) as $dfile){
 					if($dfile == "plugin.cfg" || $dfile == "plugin.yml"){
 						console("[INFO] [DevTools]: Loading plugin from {$file}...");
 						
 						$content = file_get_contents($p."/".$dfile);
 						$pluginInfo = PharUtils::readMainConfig($content);
 						break;
 					}
				}
				if(!isset($pluginInfo["classLoader"])) continue;
				
				$aver = CURRENT_API_VERSION;
				$a = $pluginInfo["api"];
				if(!is_array($pluginInfo["api"])){
					$a = explode(",", $pluginInfo["api"]);
				}
				if(!in_array((string) CURRENT_API_VERSION, $a)){
					if(is_array($pluginInfo)) $s = implode(",",$pluginInfo["api"]);
					else $s = $pluginInfo["api"];
					console("[WARNING] [DevTools]: API is not the same as Core, might cause bugs($s != {$aver})");
				}
				
				include($p."/src/".$pluginInfo["classLoader"]);
				$class = $pluginInfo["CLClass"];
				$loader = new $class();
				$loader->loadAll($p."/");
				
				$pluginName = PharUtils::getNameSpaceClass($pluginInfo["mainFile"]);
				include("$p/src/{$pluginInfo["mainFile"]}");
				$plugin = new $pluginName($api, false);
				if(!($plugin instanceof Plugin)){
					console("[ERROR] [DevTools] Plugin \"" . $pluginInfo["name"] . "\" doesn't use the Plugin Interface");
					$plugin->__destruct();
					unset($plugin);
					continue;
				}
				$identifier = $this->getIdentifier($pluginInfo, $api->plugin);
				$plugins = $pluginProp->getValue($api->plugin); //get plugins everytime
				$plugins[$identifier] = [$plugin, $pluginInfo];
				$pluginProp->setValue($api->plugin, $plugins);
 			}
 		}
 	}
	
	public function getIdentifier($pluginInfo, $api){
		if(!defined("CURRENT_API_VERSION")){
			return "phared-".$pluginInfo["name"];
		}
		switch(CURRENT_API_VERSION){
			case 9:
			case 8:
			case 7:
			case 6:
			case 5:
			case 4:
			case 3:
			case 2:
			case 1:
			case 10:
				return "phared-".$pluginInfo["name"];
			default:
				return $api->getIdentifier($pluginInfo["name"], $pluginInfo["author"]);
		}
	}
	
 	public function pluginsPath($api){
		if(!defined("CURRENT_API_VERSION")){
			if(is_dir(FILE_PATH."/data/plugins/")) return FILE_PATH."/data/plugins/"; //1.0.0
			return FILE_PATH."plugins/";
		}
		switch(CURRENT_API_VERSION){
			case 9:
			case 8:
			case 7:
			case 6:
			case 5:
			case 4:
			case 3:
			case 2:
			case 1:
			case 10:
				return DATA_PATH."plugins/";
			default:
				return $api->pluginsPath();
		}
	}
	
 	public function init(){}
	
	public function __destruct(){}
}