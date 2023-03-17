<?php
namespace net\skidcode\gh\npcs;

use EntityRegistry, Player, ServerAPI, ReflectionClass, net\skidcode\gh\npcs\command\AddNpcCommand;

class NPCMain implements \Plugin
{
	private $npcs, $filePath;
	public function __construct(ServerAPI $api, $server = false)
	{
		$this->api = $api;	
	}
	
	public function init()
	{
		$this->filePath = $this->api->plugin->pluginsPath()."NPCs.yml";
		EntityRegistry::registerEntity('\net\skidcode\gh\npcs\NPCEntity');
		if(!file_exists($this->filePath)){
			$this->npcs = [];
			$this->api->plugin->writeYAML($this->filePath, $this->npcs);
		}else{
			$this->npcs = $this->api->plugin->readYAML($this->filePath);
		}
		
		$this->api->addHandler("entity.add", [$this, "onEntityAdded"]);
		$this->api->addHandler("console.command.save-all", [$this, "save"]);

        $this->api->console->register("addnpc", "<cmd>", [new AddNpcCommand($this->api), "spawnNPC"]);
	}
	
	public function onEntityAdded($entity, $event){
		/*if(isset($entity->data["modifyByNpcCommands"])){
			$entity = new NPCEntity($entity->level, $entity->eid, $entity->class, $entity->type, $entity->data);
			$entity->yaw = $entity->pitch = 0;
			$this->api->entity->entities[$entity->eid] = $entity;
		}*/
	}
	
	public function save(){
		$this->api->plugin->writeYAML($this->filePath, $this->npcs);
	}
	
	public function __destruct(){
		$this->api->plugin->writeYAML($this->filePath, $this->npcs);
	}
}

