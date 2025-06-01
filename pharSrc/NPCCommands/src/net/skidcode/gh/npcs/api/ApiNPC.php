<?php
namespace net\skidcode\gh\npcs\api;

use net\skidcode\gh\npcs\command\AddNpcCommand;

class ApiNPC
{
	/**
	 * @var NPCCommands
	 */
	private static $plugin;
	public static function init($plugin){
		self::$plugin = $plugin;
	}
}

