<?php
/*
__PocketMine Plugin__
name=DevTools
description=Load .phar plugins from folder
version=1.0
author=NCDev
class=DevTools
apiversion=12.1
*/
class DevTools implements Plugin{
 	public function __construct(ServerAPI $api, $server = false){
 		$path = $api->plugin->pluginsPath();
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
				if(!isset($pluginInfo["classLoader"])){
					//console("[INFO] [DevTools] Plugin is not found");
					continue;
				}
				include($p."/src/".$pluginInfo["classLoader"]);
				$class = $pluginInfo["CLClass"];
				$loader = new $class();
				$loader->loadAll($p."/");
				
				$pluginName = PharUtils::getNameSpaceClass($pluginInfo["mainFile"]);
				include($p."/src/".$pluginInfo["mainFile"]);
				$plugin = new $pluginName($api, false);
				if(!($plugin instanceof Plugin)){
					console("[ERROR] [DevTools] Plugin \"" . $pluginInfo["name"] . "\" doesn't use the Plugin Interface");
					$plugin->__destruct();
					unset($plugin);
					continue;
				}
				$identifier = $api->plugin->getIdentifier($pluginInfo["name"], $pluginInfo["author"]);
				$api->plugin->plugins[$identifier] = [$plugin, $pluginInfo];
 			}
 		}
 	}
 	
 	public function init(){}
}