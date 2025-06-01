<?php
namespace net\skidcode\gh\npcs;

class ClassLoader implements \IClassLoader
{
	public function loadAll($pharPath)
	{
		$src = $pharPath."/src/";
		include($src."/net/skidcode/gh/npcs/command/AddNpcCommand.php");
		include($src."/net/skidcode/gh/npcs/api/ApiNPC.php");
		include($src."/net/skidcode/gh/npcs/NPCEntity.php");
	}

}

