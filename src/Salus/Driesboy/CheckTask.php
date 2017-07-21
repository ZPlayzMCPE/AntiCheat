<?php 

namespace Salus\Driesboy;
	
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
    
Class CheckTask extends PluginTask {

	private $instance;
	
	public function __construct(Main $plugin){
		parent::__construct($plugin);
		$this->instance = $plugin;
	}
	
	public function onRun($tick){
		$list = $this->instance->movePlayers;
		foreach ($list as $key => $value) {
				$player = $this->instance->getServer()->getPlayer($key);
				if((float) $value["distance"] > (float) 7.6){
					$this->instance->point[$key]["distance"] += (float) 1;
					if((float) $this->instance->point[$key]["distance"] > (float) 3){
						if($player instanceof Player){
							$this->instance->HackDetected($player, "Speed");
						}
					}
				} else {
					$this->instance->point[$key]["distance"] = (float) 0;
				}
				if((float) $value["fly"] > (float) 7.4){
					$this->instance->point[$key]["fly"] += (float) 1;
					if((float) $this->instance->point[$key]["fly"] > (float) 3){
						if($player instanceof Player){
							$this->instance->HackDetected($player, "Fly");
						}
					}
				} else {
					$this->instance->point[$key]["fly"] = (float) 0;
				}
				$this->instance->movePlayers[$key]["distance"] = (float) 0;
				$this->instance->movePlayers[$key]["fly"] = (float) 0;
			}
		}
	}