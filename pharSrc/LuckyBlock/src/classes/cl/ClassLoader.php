<?php
namespace classes\cl;

use IClassLoader;

class ClassLoader implements IClassLoader{
	public function loadAll($dir){
		include($dir."src/classes/LBRandom.php");
		include($dir."src/classes/LBExecute.php");
		include($dir."src/classes/LBStructure.php");
		include($dir."src/classes/LBBonusChest.php");
	}
}