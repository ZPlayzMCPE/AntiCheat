<?php

namespace Salus\Driesboy;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\math\Vector3;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;

class Main extends PluginBase implements Listener{

  private $playersfly = array();

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this,$this);
    if(!(file_exists($this->getDataFolder()))) {
      @mkdir($this->getDataFolder());
      chdir($this->getDataFolder());
      @mkdir("Players/", 0777, true);
    }
    $this->saveResource("config.yml");
    $this->getLogger()->info("§3Salus has been enabled!");
    @mkdir($this->getDataFolder() . "players");
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckTask($this), 20);
  }

  public function onDisable(){
    $this->getLogger()->info("§3Salus has been disabled!");
  }

  // API
  public function ScanMessage($message, $player){
    $pos = strpos(strtoupper($message), "%PLAYER%");
    $newmsg = $message;
    if ($pos !== false){
      $newmsg = substr_replace($message, $player, $pos, 8);
    }
    return $newmsg;
  }
  
  public function CheckForceOP(Player $player){
    if ($player->isOp()){
      if (!$player->hasPermission("salus.legitop")){
        $this->HackDetected($player, "Force-OP");
      }
    }
  }
  
  public function CheckFly(Player $player){
    if(!$player->isCreative() and !$player->isSpectator() and !$player->getAllowFlight()){
      $block = $player->getLevel()->getBlock(new Vector3($player->getFloorX(),$player->getFloorY()-1,$player->getFloorZ()));
      if($block->getID() !== 0 and $block->getID() !== 10 and $block->getID() !== 11 and $block->getID() !== 8 and $block->getID() !== 9 and $block->getID() !== 182 and $block->getID() !== 171 and $block->getID() !== 126 and $block->getID() !== 44){
        if(!isset($this->playersfly[$player->getName()])) $this->playersfly[$player->getName()] = 0;
          $this->playersfly[$player->getName()]++;
          if($this->playersfly[$player->getName()] >= $this->getConfig()->get("Fly-Threshold")){
            $this->playersfly[$player->getName()] = 0;
            $this->HackDetected($player, "Fly");
          }
      }elseif($this->playersfly[$player->getName()] > 0) { 
        $this->playersfly[$player->getName()] = 0;
      }
    } 
  }
  
  public function HackDetected(Player $player, $reason){
    $player_name = $player->getName();
    if(!(file_exists($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt"))) {
      touch($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
      file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", "1");
    }else{
      $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
      file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", $file + 1);
    }
    $this->CheckMax($player ,$reason, "AntiCheat");
  }

  public function CheckMax(Player $player, $reason, $sender){
    $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".txt");
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
      $this->getServer()->getIPBans()->addBan($player->getAddress(), $message, null, $sender);
      $sender->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);
      // todo ClientBan

    }elseif ($this->getConfig()->get("punishment") === "Command"){
      foreach($this->getConfig()->get("punishment-command") as $command){
        $send = $this->ScanMessage($command, $player);
        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $send);
      }
    }
  }
  
  public function ResetPoints(Player $player){
    $this->movePlayers[$player->getName()]["distance"] = (float) 0;
    $this->point[$player->getName()]["distance"] = (float) 0;
    $this->movePlayers[$player->getName()]["fly"] = (float) 0;
    $this->point[$player->getName()]["fly"] = (float) 0;
    $this->playersfly[$player->getName()] = 0;
  }
  
  public function CheckTask($event){
    $player = $event->getPlayer();
    $oldPos = $event->getFrom();
	$newPos = $event->getTo();
    if(!$player->isCreative() and !$player->isSpectator() and !$player->isOp() and !$player->getAllowFlight()){
      $FlyMove = (float) round($newPos->getY() - $oldPos->getY(),3);
      $DistanceMove = (float) round(sqrt(($newPos->getX() - $oldPos->getX()) ** 2 + ($newPos->getZ() - $oldPos->getZ()) ** 2),2);
      if($FlyMove === (float) -0.002 or $FlyMove === (float) -0.003){
        $this->movePlayers[$player->getName()]["distance"] += 3;
      }
      $this->movePlayers[$player->getName()]["fly"] += $FlyMove;
      $this->movePlayers[$player->getName()]["distance"] += $DistanceMove;
    }
  }
  
  // Events
  public function onRecieve(DataPacketReceiveEvent $event) {
    $player = $event->getPlayer();
    $packet = $event->getPacket();
    if($packet instanceof UpdateAttributesPacket){ 
      $this->HackDetected($player, "UpdateAttributesPacket");
    }
    if($packet instanceof SetPlayerGameTypePacket){ 
      $this->HackDetected($player, "Force-GameMode");
    }
    if ($packet instanceof AdventureSettingsPacket) {
      if(!$player->isCreative() and !$player->isSpectator() and !$player->getAllowFlight()){
        if ($packet->noClip && $player->isSpectator() !== true) {
          $this->HackDetected($player, "NoClip");
        }
        if (($packet->allowFlight || $packet->isFlying)) {
          $this->HackDetected($player, "Fly");
        } 
        if((($packet->flags >> 9) & 0x01 === 1) or (($packet->flags >> 7) & 0x01 === 1) or (($packet->flags >> 6) & 0x01 === 1)){
          $this->HackDetected($player, "Fly");
        }
        switch ($packet->flags){
          case 614:
          case 615:
          case 103:
          case 102:
          case 38:
          case 39:
            $this->HackDetected($player, "NoClip");
            break;
          default:
            break;
        }
      }
    }
  }
  
  public function onDamage(EntityDamageEvent $event){
    if($event instanceof EntityDamageByEntityEvent and $event->getEntity() instanceof Player and $event->getDamager() instanceof Player and $event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK){
      if(round($event->getEntity()->distanceSquared($event->getDamager())) >= 12){
        $this->HackDetected($event->getDamager(), "Reach");
      }
    }
  }
  
  public function onJoin(PlayerJoinEvent $event){
    $player = $event->getPlayer();
    $this->ResetPoints($player);
    $this->CheckForceOP($player);
  }
  
  public function onMove(PlayerMoveEvent $event){
    $this->CheckForceOP($event->getPlayer());
    $this->CheckFly($event->getPlayer());
    $this->CheckTask($event);                 
  }
}
