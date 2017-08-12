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

  public function execute(CommandSender $sender, string $label, array $args){
    if($sender->hasPermission("salus.warn")){
      $main = Main::getInstance();
      if(!(isset($args[0]) and isset($args[1]) and isset($args[2]))) {
        $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /warn <player> <points> <reason>");
        return true;
      }else{
        $points = $args[1];
        $player = $main->getServer()->getPlayer($args[0]);
        if($player === null) {
          $sender->sendMessage(TF::RED . "Player " . $player . " could not be found.");
          return true;
        }else{
          unset($args[0]);
          unset($args[1]);
          $reason = implode(" ", $args);
          $player_name = $player->getName();
          if(!(file_exists($main->getDataFolder() . "players/" . strtolower($player_name) . ".txt"))) {
            touch($main->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
            file_put_contents($main->getDataFolder() . "players/" . strtolower($player_name) . ".txt", $points);
          }else{
            $file = file_get_contents($main->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
            file_put_contents($main->getDataFolder() . "players/" . strtolower($player_name) . ".txt", $file + $points);
          }
          $file = file_get_contents($main->getDataFolder() . "players/" . strtolower($player->getName()) . ".txt");
          if($file >= $main->getConfig()->get("max-warns")) {
            $main->Ban($player, TF::RED . "You are banned for using " . $reason . " by " . $sender->getName(), $sender->getName(), $reason);
          }else{
            $player->kick("You are warned for " . $reason . " by " . $sender->getName());
          }
          $sender->sendMessage(TF::RED . "" . $player->getName() . " has been warned for ". $reason .".");
        }
      }
    }else{
      $sender->sendMessage("§l§o§3G§bC§r§7: §cYou don't have permission to use that command!");
    }
  }
}
