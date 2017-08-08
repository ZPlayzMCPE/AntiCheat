<?php

namespace Salus\Commands;

use Salus\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;
use pocketmine\network\mcpe\protocol\ContainerSetContentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;

class VanishCommand extends Command{

  /** @var array */
  public $spectator = array();

  public function __construct(){
    parent::__construct("vanish", "spectating command for moderators");
  }

  public function execute(CommandSender $sender, string $label, array $args){
    if($sender->hasPermission("salus.vanish")){
      $main = Main::getInstance();
      if(!(isset($args[0]))) {
        $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /vanish on, /vanish off, /vanish tp <player>");
        return true;
      }else{
        switch(strtolower($args[0])){
          case "on":
            if ($sender->getLevel()->getName() === $main->getServer()->getDefaultLevel()->getName()){
              if (!in_array($sender->getName(), $this->spectator)){
                $sender->setGamemode("3");
                $this->spectator[] = $sender->getName();
              }else{
                $sender->sendMessage(TF::RED . "You are already vanished");
              }
            }else{
              $sender->sendMessage(TF::RED . "You are not in the lobby");
            }
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
              $sender->setGamemode("1");
              $pk = new ContainerSetContentPacket();
              $pk->windowid = ContainerIds::CREATIVE;
              $pk->targetEid = $sender->getId();
              $sender->dataPacket($pk);
            }else{
              $sender->sendMessage(TF::RED . "You are not vanished");
            }
          break;
        }
      }
    }else{
      $sender->sendMessage("§l§o§3G§bC§r§7: §cYou don't have permission to use that command!");
    }
  }
}
