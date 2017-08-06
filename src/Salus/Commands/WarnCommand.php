<?php

namespace Salus\Commands;

use Salus\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class WarnCommand extends Command{

  public function __construct(){
    parent::__construct("warn", "warn a player for breaking the rules");
  }

  public function execute(CommandSender $sender, string $label, array $args):bool{
    $main = Main::getInstance();
    if(!(isset($args[0]) and isset($args[1]))) {
      $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /warn <player> <points> <reason>");
      return true;
    }else{
      if($args[1] === null) {
        $points = "1";
      }else{
        $points = $args[1];
      }
      $player = $main->getServer()->getPlayer($args[0]);
      if($player === null) {
        $sender->sendMessage(TF::RED . "Player " . $player . " could not be found.");
        return true;
      }else{
        unset($args[0]);
        unset($args[1]);
        $reason = implode(" ", $args);
        $main->HackDetected($player, $reason, $sender, $points);
        $sender->sendMessage(TF::RED . "Player " . $player . " has been warned for". $reason .".");
      }
    }
  }
}
