<?php

namespace Salus\Commands;

use Salus\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class ReportCommand extends Command{

  public function __construct(){
    parent::__construct("report", "report a player for breaking the rules");
  }

  public function execute(CommandSender $sender, string $label, array $args){
    $main = Main::getInstance();
    if(!(isset($args[0]) and isset($args[1]))) {
      $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /report <player> <reason>");
      return true;
    }else{
      $player = $main->getServer()->getPlayer($args[0]);
      if($player === null) {
        $sender->sendMessage(TF::RED . "Player " . $player->getName() . " could not be found.");
        return true;
      }else{
        foreach($main->getServer()->getOnlinePlayers() as $p) {
          if($p->hasPermission("salus.moderator")) {
            unset($args[0]);
            $reason = implode(" ", $args);
            $p->sendMessage(TF::YELLOW . $sender->getName() . " reported " . $player->getName() . " for " . $reason);
          }
        }
        $sender->sendMessage(TF::GREEN . $pn . " has been reported!");
        return true;
      }
    }
  }
}
