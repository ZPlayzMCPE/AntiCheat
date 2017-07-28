<?php
namespace Salus;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat as TF;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use Salus\SpeedTask;

class Main extends PluginBase implements Listener {

  /** @var array */
  public $point = array();
  /** @var array */
  public $surroundings = array();
  /** @var array */
  public $fly = array();

  public function onEnable() {
    if (!$this->isSpoon()) {
      $this->getServer()->getPluginManager()->registerEvents($this, $this);
      if(!(file_exists($this->getDataFolder()))) {
        @mkdir($this->getDataFolder());
        chdir($this->getDataFolder());
        @mkdir("players/", 0777, true);
      }
      $this->saveResource("config.yml");
      $this->getLogger()->info("§3Salus has been enabled!");
      @mkdir($this->getDataFolder() . "players");
      if($this->getConfig()->get("detect-Speed") === true){
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new SpeedTask($this), 20);
      }
      if($this->getConfig()->get("config-version") !== 1.1){
        $this->getServer()->getLogger()->error(TF::RED . "[Salus] > Your Config is out of date!");
        $this->getServer()->shutdown();
      }
    }
  }

  public function isSpoon(){
    if ($this->getServer()->getName() !== "PocketMine-MP") {
      $this->getLogger()->error("Well... You're using a spoon. So enjoy a featureless AntiCheat plugin by Driesboy until you switch to PMMP! :)");
      return true;
    }
    if($this->getDescription()->getAuthors() !== ["Driesboy"] || $this->getDescription()->getName() !== "AntiCheat"){
      $this->getLogger()->error("You are not using the original version of this plugin (AntiCheat) by Driesboy.");
      return true;
    }
    return false;
  }


  /** _____                _
  *  |  ___|              | |
  *  | |____   _____ _ __ | |_
  *  |  __\ \ / / _ \ '_ \| __|
  *  | |___\ V /  __/ | | | |_
  *  \____/ \_/ \___|_| |_|\__|
  */

  public function onPlayerJoin(PlayerJoinEvent $event){
    $this->reset($event->getPlayer());
    $this->checkForceOP($event->getPlayer());
  }

  public function onPlayerQuit(PlayerQuitEvent $event){
    unset($this->point[$event->getPlayer()->getName()]);
  }

  public function onPlayerMove(PlayerMoveEvent $event){
    $this->checkForceOP($event);
    $this->checkNoClip($event);
    $this->checkFly($event);
  }

  public function onDamage(EntityDamageEvent $event){
    $this->checkReach($event);
  }

  public function onRecieve(DataPacketReceiveEvent $event) {
    $player = $event->getPlayer();
    $packet = $event->getPacket();
    if($this->getConfig()->get("detect-UpdateAttributesPacket") === true){
      if($packet instanceof UpdateAttributesPacket){
        $this->HackDetected($player, "UpdateAttributesPacket Hacks", "Salus", "1");
      }
    }
    if($this->getConfig()->get("detect-ForceGameMode") === true){
      if($packet instanceof SetPlayerGameTypePacket){
        $this->HackDetected($player, "Force-GameMode Hacks", "Salus", "1");
      }
    }
    if($this->getConfig()->get("detect-FlyPackets") === true){
      if($packet instanceof AdventureSettingsPacket){
        if(!$player->isCreative() and !$player->isSpectator() and !$player->isOp() and !$player->getAllowFlight()){
          switch ($packet->flags){
            case 614:
            case 615:
            case 103:
            case 102:
            case 38:
            case 39:
            $this->HackDetected($player, "Fly Hacks", "Salus", "1");
            break;
            default:
            break;
          }
          if((($packet->flags >> 9) & 0x01 === 1) or (($packet->flags >> 7) & 0x01 === 1) or (($packet->flags >> 6) & 0x01 === 1)){
            $this->HackDetected($player, "Fly Hacks", "Salus", "1");
          }
        }
      }
    }
  }

  /** ___  ______ _____
  *  / _ \ | ___ \_   _|
  * / /_\ \| |_/ / | |
  * |  _  ||  __/  | |
  * | | | || |    _| |_
  * \_| |_/\_|    \___/
  */

  public function reset(Player $player){
    $this->point[$player->getName()]["speed"] = (float) 0;
    $this->point[$player->getName()]["fly"] = (float) 0;
    $this->point[$player->getName()]["reach"] = (float) 0;
    $this->point[$player->getName()]["noclip"] = (float) 0;
  }

  public function ScanMessage($message, $player){
    $pos = strpos(strtoupper($message), "%PLAYER%");
    $newmsg = $message;
    if ($pos !== false){
      $newmsg = substr_replace($message, $player, $pos, 8);
    }
    return $newmsg;
  }

  public function GetSurroundingBlocks(Player $player){
    $level = $player->getLevel();

    $posX = $player->getX();
    $posY = $player->getY();
    $posZ = $player->getZ();

    $pos1 = new Vector3($posX  , $posY, $posZ  );
    $pos2 = new Vector3($posX-1, $posY, $posZ  );
    $pos3 = new Vector3($posX-1, $posY, $posZ-1);
    $pos4 = new Vector3($posX  , $posY, $posZ-1);
    $pos5 = new Vector3($posX+1, $posY, $posZ  );
    $pos6 = new Vector3($posX+1, $posY, $posZ+1);
    $pos7 = new Vector3($posX  , $posY, $posZ+1);
    $pos8 = new Vector3($posX+1, $posY, $posZ-1);
    $pos9 = new Vector3($posX-1, $posY, $posZ+1);

    $bpos1 = $level->getBlock($pos1)->getId();
    $bpos2 = $level->getBlock($pos2)->getId();
    $bpos3 = $level->getBlock($pos3)->getId();
    $bpos4 = $level->getBlock($pos4)->getId();
    $bpos5 = $level->getBlock($pos5)->getId();
    $bpos6 = $level->getBlock($pos6)->getId();
    $bpos7 = $level->getBlock($pos7)->getId();
    $bpos8 = $level->getBlock($pos8)->getId();
    $bpos9 = $level->getBlock($pos9)->getId();

    $this->surroundings = array ($bpos1, $bpos2, $bpos3, $bpos4, $bpos5, $bpos6, $bpos7, $bpos8, $bpos9);
  }

  /** _____ _               _
  *  /  __ \ |             | |
  *  | /  \/ |__   ___  ___| | _____
  *  | |   | '_ \ / _ \/ __| |/ / __|
  *  | \__/\ | | |  __/ (__|   <\__ \
  *  \____/_| |_|\___|\___|_|\_\___/
  */

  public function checkReach($event) {
    if($this->getConfig()->get("detect-Reach") === true){
      if($event instanceof EntityDamageByEntityEvent and $event->getEntity() instanceof Player and $event->getDamager() instanceof Player and $event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK){
        if(round($event->getEntity()->distanceSquared($event->getDamager())) >= 12){
          $this->point[$event->getDamager()]["reach"] += (float) 1;
          $event->setCancelled();
          if((float) $this->point[$event->getDamager()]["reach"] > (float) 3){
            $this->HackDetected($event->getDamager(), "Reach Hacks", "Salus", "1");
          }
        }else{
          $this->point[$player->getName()]["reach"] = (float) 0;
        }
      }
    }
  }

  public function checkForceOP($event) {
    $player = $event->getPlayer();
    if($this->getConfig()->get("detect-ForceOP") === true){
      if ($player->isOp()){
        if (!$player->hasPermission("salus.legitop")){
          $event->setCancelled();
          $this->HackDetected($player, "Force-OP Hacks", "Salus", "3");
        }
      }
    }
  }

  public function checkFly($event){
    $player = $event->getPlayer();
    $oldPos = $event->getFrom();
    $newPos = $event->getTo();
    if($this->getConfig()->get("detect-Fly") === true){
      if(!$player->isCreative() and !$player->isSpectator() and !$player->getAllowFlight()){
        if ($oldPos->getY() <= $newPos->getY()){
          if($player->GetInAirTicks() > 20){
            $maxY = $player->getLevel()->getHighestBlockAt(floor($newPos->getX()), floor($newPos->getZ()));
            if($newPos->getY() - 2 > $maxY){
              $this->point[$player->getName()]["fly"] += (float) 1;
              if((float) $this->point[$player->getName()]["fly"] > (float) 3){
                $event->setCancelled();
                $this->HackDetected($player, "Fly Hacks", "Salus", "1");
              }
            }
          }
        }else{
          $this->point[$player->getName()]["fly"] = (float) 0;
        }
      }
    }
  }

  public function checkNoClip($event){
    if($this->getConfig()->get("detect-NoClip") === true){
      $player = $event->getPlayer();
      $level = $player->getLevel();
      $pos = new Vector3($player->getX(), $player->getY(), $player->getZ());
      $BlockID = $level->getBlock($pos)->getId();

      if (
        //BUILDING MATERIAL
        $BlockID == 1
        or $BlockID == 2
        or $BlockID == 3
        or $BlockID == 4
        or $BlockID == 5
        or $BlockID == 7
        or $BlockID == 17
        or $BlockID == 18
        or $BlockID == 20
        or $BlockID == 43
        or $BlockID == 45
        or $BlockID == 47
        or $BlockID == 48
        or $BlockID == 49
        or $BlockID == 79
        or $BlockID == 80
        or $BlockID == 87
        or $BlockID == 89
        or $BlockID == 97
        or $BlockID == 98
        or $BlockID == 110
        or $BlockID == 112
        or $BlockID == 121
        or $BlockID == 155
        or $BlockID == 157
        or $BlockID == 159
        or $BlockID == 161
        or $BlockID == 162
        or $BlockID == 170
        or $BlockID == 172
        or $BlockID == 174
        or $BlockID == 243
        //ORES TODO
        or $BlockID == 14
        or $BlockID == 15
        or $BlockID == 16
        or $BlockID == 21
        or $BlockID == 56
        or $BlockID == 73
        or $BlockID == 129
      ){
        if(    !in_array(Block::SLAB                , $this->surroundings )
        and !in_array(Block::WOOD_STAIRS         , $this->surroundings )
        and !in_array(Block::COBBLE_STAIRS       , $this->surroundings )
        and !in_array(Block::BRICK_STAIRS        , $this->surroundings )
        and !in_array(Block::STONE_BRICK_STAIRS  , $this->surroundings )
        and !in_array(Block::NETHER_BRICKS_STAIRS, $this->surroundings )
        and !in_array(Block::SPRUCE_WOOD_STAIRS  , $this->surroundings )
        and !in_array(Block::BIRCH_WOODEN_STAIRS , $this->surroundings )
        and !in_array(Block::JUNGLE_WOOD_STAIRS  , $this->surroundings )
        and !in_array(Block::QUARTZ_STAIRS       , $this->surroundings )
        and !in_array(Block::WOOD_SLAB           , $this->surroundings )
        and !in_array(Block::ACACIA_WOOD_STAIRS  , $this->surroundings )
        and !in_array(Block::DARK_OAK_WOOD_STAIRS, $this->surroundings )
        and !in_array(Block::SNOW                , $this->surroundings )){

          $this->point[$player->getName()]["noclip"] += (float) 1;
          if((float) $this->point[$player->getName()]["noclip"] > (float) 3){
            $event->setCancelled();
            $this->HackDetected($player, "No-Clip Hacks", "Salus", "1");
          }
        }else{
          $this->point[$player->getName()]["noclip"] = (float) 0;
        }
      }else{
        $this->point[$player->getName()]["noclip"] = (float) 0;
      }
    }
  }

  /**______            _     _                          _
  *  | ___ \          (_)   | |                        | |
  *  | |_/ /   _ _ __  _ ___| |__  _ __ ___   ___ _ __ | |_ ___
  *  |  __/ | | | '_ \| / __| '_ \| '_ ` _ \ / _ \ '_ \| __/ __|
  *  | |  | |_| | | | | \__ \ | | | | | | | |  __/ | | | |_\__ \
  *  \_|   \__,_|_| |_|_|___/_| |_|_| |_| |_|\___|_| |_|\__|___/
  */

  public function HackDetected(Player $player, $reason, $sender, $points){
    $player_name = $player->getName();
    if(!(file_exists($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt"))) {
      touch($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
      file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", "1");
    }else{
      $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt");
      file_put_contents($this->getDataFolder() . "players/" . strtolower($player_name) . ".txt", $file + $points);
    }
    $this->CheckMax($player ,$reason, $sender);
  }

  public function CheckMax(Player $player, $reason, $sender){
    $file = file_get_contents($this->getDataFolder() . "players/" . strtolower($player->getName()) . ".txt");
    if($file >= $this->getConfig()->get("max-warns")) {
      $this->Ban($player, TF::RED . "You are banned for using " . $reason . " by " . $sender, $sender);
    }else{
      $player->kick(TF::YELLOW . "You are warned for " . $reason . " by " . $sender);
      return true;
    }
  }

  public function Ban(Player $player, $message, $sender){
    $message = $this->getConfig()->get("punishment");
    if ($this->getConfig()->get("punishment") === "Ban"){
      $this->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);
    }elseif ($this->getConfig()->get("punishment") === "IPBan"){
      $this->getServer()->getIPBans()->addBan($player->getAddress(), $message, null, $sender);
    }elseif ($this->getConfig()->get("punishment") === "ClientBan"){
      // todo
    }elseif ($this->getConfig()->get("punishment") === "MegaBan"){
      $this->getServer()->getIPBans()->addBan($player->getAddress(), $message, null, $sender);
      $this->getServer()->getNameBans()->addBan($player->getName(), $message, null, $sender);
      // todo ClientBan
    }elseif ($this->getConfig()->get("punishment") === "Command"){
      foreach($this->getConfig()->get("punishment-command") as $command){
        $send = $this->ScanMessage($command, $player->getName());
        $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $send);
      }
    }
  }
}
