<?php
namespace Salus;


use pocketmine\entity\Effect;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\PluginTask;

class SpeedTask extends PluginTask {

	const WALKING_SPEED = 4.3;

	/** @var array */
	public $previousPosition = array();
	/** @var array */
	public $lastCheckTick = array();
	/** @var Main */
	private $p;

	public function __construct($p) {
		$this->p = $p;
		parent::__construct($p);
	}

	public function onRun(int $currentTick) {
		$main = $this->p;
		foreach($main->getServer()->getOnlinePlayers() as $player){
			if($this->previousPosition[$player->getName()] === null){
				$this->previousPosition[$player->getName()] = $player->getPosition();
			}
			$prev = $this->previousPosition[$player->getName()];
			$current = $player->getPosition();

			if(!($prev instanceof Position)) {
				return;
			}

			if($prev->getLevel() != $current->getLevel()) {
				return;
			}

			$maxDistance = $this->getMaxDistance($player, $currentTick - $this->lastCheckTick[$player->getName()]);

			// Ignore Y values (in case of jump boosts etc)
			$actualDistance = sqrt(abs(($prev->getX() - $current->getX()) * ($prev->getZ() - $current->getZ())));

			$diff = $maxDistance - $actualDistance;
			if($diff > 0) {

			}

			$this->previousPosition[$player->getName()] = $player->getPosition();
			$this->lastCheckTick[$player->getName()] = $currentTick;
		}
	}

	public function getMaxDistance(Player $player, $tickDifference) {
		$effects = $player->getEffects();
		$amplifier = 0;
		if(!empty($effects)) {
			foreach($effects as $effect) {
				if($effect->getId() == Effect::SPEED) {
					$a = $effect->getAmplifier();
					if($a > $amplifier) {
						$amplifier = $a;
					}
				}
			}
		}
		$distance = self::WALKING_SPEED + ($amplifier != 0) ? (self::WALKING_SPEED / (0.2 * $amplifier)) : 0;
		return $distance * ($tickDifference / 20);
	}
}
