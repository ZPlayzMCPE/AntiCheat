<?php

namespace Salus\Driesboy;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use pocketmine\command\ConsoleCommandSender;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;

class Main extends PluginBase implements Listener{

  private $playersfly = array();

  public function onEnable(){
    $this->saveDefaultConfig();
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    $this->saveResource("config.yml");
    $this->getLogger()->info("§3Salus has been enabled!");
    @mkdir($this->getDataFolder() . "players");
  }

  public function onDisable(){
    $this->getLogger()->info("§3Salus has been disabled!");
  }

  // API
  public function ScanMessage($message, $player){
    $pos    = strpos(strtoupper($message), "%PLAYER%");
    $newmsg = $message;
    if ($pos !== false){
      $newmsg = substr_replace($message, $player, $pos, 8);
    }
    return $newmsg;
  }
  
  public function CheckForceOP(Player $player){
    if ($player->isOp()){
      if (!$player->hasPermission("salus.legitop")){
        $event->setCancelled(true);
        $this->HackDetected($player, "Force-OP");
      }
    }
  }
  
  public function CheckFly(Player $player){
    if(!$player->isCreative() and !$player->isSpectator() and !$player->getAllowFlight()){
      $block = $player->getLevel()->getBlock(new Vector3($player->getFloorX(),$player->getFloorY()-1,$player->getFloorZ()));
      if($block->getID() == 0 and !$block->getID() == 10 and !$block->getID() == 11 and !$block->getID() == 8 and !$block->getID() == 9 and !$block->getID() == 182 and !$block->getID() == 171 and !$block->getID() == 126 and !$block->getID() == 44){
        if(!isset($this->playersfly[$player->getName()])) $this->playersfly[$player->getName()] = 0;
          $this->playersfly[$player->getName()]++;
          if($this->playersfly[$player->getName()] >= $this->getConfig()->get("Fly-Threshold")){
            $this->playersfly[$player->getName()] = 0;
            $this->HackDetected($player, "Flying");
          }
        }
      } elseif($this->players[$player->getName()] > 0) $this->players[$player->getName()] = 0;
    } 
  }

  public function HackDetected(Player $player, $reason){
    $player_name = $player->getName();
    $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
    if(!(file_exists($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt"))) {
      touch($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
      file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", "1");
    }else{
      file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", $file + 1);
    }
    $this->CheckMax($player ,$reason, "AntiCheat");
  }

  public function CheckMax(Player $player, $reason, $sender){
    if($file >= $this->getConfig()->get("max-warns")) {
      $this->Ban($player, TF::RED . "You are banned for using" . $reason . "hacks by " . $sender);
    }else{
      $player->kick(TF::YELLOW . "You are warned for " . $reason . " by " . $sender);
      return true;
    }
  }

  public function Ban(Player $player, $message){
    $message = $this->getConfig()->get("punishment");
    if ($this->getConfig()->get("punishment") === "Ban"){
      $sender->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);

    }elseif ($this->getConfig()->get("punishment") === "IPBan"){
      $this->getServer()->getIPBans()->addBan($player->getAddress(), $message, null, $sender);

    }elseif ($this->getConfig()->get("punishment") === "ClientBan"){
      // todo

    }elseif ($this->getConfig()->get("punishment") === "MegaBan"){
      $this->getServer()->getIPBans()->addBan($player->getAddress(), "You are banned for " . $reason, null, $sender);
      $sender->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);
      // todo ClientBan

    }elseif ($this->getConfig()->get("punishment") === "Command"){
      foreach($this->getConfig()->get("punishment-command") as $command){
        $send = $this->ScanMessage($command, $player);
        $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), $send);
      }
    }
  }

  // Events
  public function onRecieve(DataPacketReceiveEvent $event) {
    $player = $event->getPlayer();
    $packet = $event->getPacket();
    if((($packet->flags >> 9) & 0x01 === 1) or (($packet->flags >> 7) & 0x01 === 1) or (($packet->flags >> 6) & 0x01 === 1)){
      $this->HackDetected($player, "Flying");
    }
  }

  public function onDamage(EntityDamageEvent $event) {
    if($event instanceof EntityDamageByEntityEvent){
      if($event->getDamager() instanceof Player && $event->getEntity() instanceof Player) {
        if($event->getDamager()->distance($event->getEntity()) > 4){
          $this->HackDetected($event->getDamager(), "Reach");
        }
      }
    }
  }
  
  public function onJoin(PlayerJoinEvent $event){
    $this->CheckForceOP($event->getPlayer());
  }
  
  public function onMove(PlayerMoveEvent $event){
    $this->CheckForceOP($event->getPlayer());
    $this->CheckFly($event->getPlayer());
    $this->CheckSpeed($event->getPlayer();                 
  }
}
