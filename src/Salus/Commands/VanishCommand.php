<?php

namespace Salus\Commands;

use Salus\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class VanishCommand extends Command{

  /** @var array */
  public $spectator = array();

  public function __construct(){
    parent::__construct("vanish", "spectating command for moderators");
  }

  public function execute(CommandSender $sender, string $label, array $args){
    $main = Main::getInstance();
    if(!(isset($args[0]))) {
      $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /vanish on, /vanish off, /vanish tp <player>");
      return true;
    }else{
      switch(strtolower($args[0])){
        case "on":
        $sender->setGamemode("3");
        $this->spectator[] = $sender->getName();
        break;

        case "tp":
        if (in_array($sender->getName(), $this->spectator)){
          $player = $main->getServer()->getPlayer($args[1]);
          if($player === null) {
            $sender->sendMessage(TF::RED . "Player " . $player->getName() . " could not be found.");
            return true;
          }else{
            $main->getServer()->dispatchCommand(new ConsoleCommandSender(),"tp " . $sender->getName() . " " . $player->getName());
          }
        }else{
          $sender->sendMessage(TF::RED . "You are not vanished");
        }
        break;

        case "off":
        if (in_array($sender->getName(), $this->spectator)){
          unset($this->spectator[array_search($sender->getName(), $this->spectator)]);
          $sender->teleport($main->getServer()->getDefaultLevel()->getSafeSpawn());
          $sender->setGamemode($main->getServer()->getDefaultGamemode());
        }
        break;
      }
    }
  }
}
