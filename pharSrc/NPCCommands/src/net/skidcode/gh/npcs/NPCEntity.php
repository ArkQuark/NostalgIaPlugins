<?php
namespace net\skidcode\gh\npcs;

use Level;
use Player;
use Zombie;
use AddPlayerPacket;
use AddEntityPacket;
use TaskLookAtPlayer;
use Utils;
use ReflectionClass;
class NPCEntity extends Zombie
{
	const TYPE = -9999;
    function __construct(Level $level, $eid, $class, $type = 0, $data = array()){
        //$weirdcode1 = self::$despawnMobs;
        //self::$despawnMobs = false;
        parent::__construct($level, $eid, $class, MOB_ZOMBIE, $data);
        //self::$despawnMobs = $weirdcode1;
		$this->ai = new \EntityAI($this);
		$this->ai->addTask(new TaskLookAtPlayer(16));
        $this->setName($data["command"]);
        $this->yaw = $this->pitch = 0;
    }
	
	public function update($now){
		$this->server->api->schedule(10, [$this, "looking"], []);	
		parent::update($now);
}

	public function looking(){
		$this->ai->mobController->lookOn($this->findTarget($this, 10));
	}

	protected function findTarget($e, $r = 5){
		$svd = null;
		$svdDist = -1;
		foreach($e->server->api->entity->getRadius($e, $r, ENTITY_PLAYER) as $p){
			if($svdDist === -1){
				$svdDist = Utils::manh_distance($e, $p);
				$svd = $p;
				continue;
			}
			if($svd != null && $svdDist === 0){
				$svd = $p;
			}
			
			if(($cd = Utils::manh_distance($e, $p)) < $svdDist){
				$svdDist = $cd;
				$svd = $p;
			}
		}
		return $svd;
	}

	public function updateBurning(){}
	
	public function harm($dmg, $cause = "generic", $force = false)
	{
		if(is_numeric($cause)){
			$e = $this->server->api->entity->get($cause);
			if($e->isPlayer()){
				$this->server->api->console->run($this->data["command"], $e->player);
			}
		}
		return false;
	}
	
	public function getMetadata(){
		$d = parent::getMetadata();
		/*$d[16]["value"] = 2;
		$d[17]["value"] = [
			$this->x-2,
			$this->y-2,
			$this->z-2
		];*/
		return $d;
	}

	public function spawn($player){
		if(!($player instanceof Player)){
			$player = $this->server->api->player->get($player);
		}
		if($player->eid === $this->eid or $this->closed !== false or ($player->level !== $this->level and $this->class !== ENTITY_PLAYER)){
			return false;
		}
		$pk = new AddEntityPacket();
		$pk->eid = $this->eid;
		$pk->type = $this->type;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->did = 0;
		$player->dataPacket($pk);
		
		$pk = new AddPlayerPacket();
		$pk->clientID = 0;
		$pk->username = $this->getName();
		$pk->eid = $this->eid;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->itemID = 7;
		$pk->itemAuxValue = 0;
		$pk->metadata = $this->getMetadata();
		$player->dataPacket($pk);
	}
}

