<?php
/*
Copyright (c) 2018 Thunder33345

Permission to use, copy, modify, and distribute this software for any
purpose without fee is hereby granted, provided that the above
copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
*/
declare(strict_types=1);
namespace Thunder33345\WorldTP;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as Format;

class WorldTP extends PluginBase
{
  const LABEL = Format::DARK_GREEN.'World'.Format::GOLD.'TP';
  const PREFIX = Format::DARK_PURPLE.'['.self::LABEL.Format::DARK_PURPLE.']'.Format::RESET;
  const PREFIX_ERROR = Format::RED.'['.self::LABEL.Format::RED.']'.Format::RESET;

  public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool
  {
    switch($command->getName()){
      case "worldtp":
        $this->commandTP($sender, $command, $label, $args);
        break;
        case "worldtpplayer":
            $this->commandPlayerTP($sender, $command, $label, $args);
            break;
      case "worldload":
        $this->commandLoad($sender, $command, $label, $args);
        break;
      case "worldunload":
        $this->commandUnload($sender, $command, $label, $args);
        break;
      case "worldlist":
        $this->commandList($sender, $command, $label, $args);
        break;
    }
    return true;
  }

  public function commandTP(CommandSender $player, Command $command, string $label, array $args)
  {
    if(!$player instanceof Player){
      $player->sendMessage(self::PREFIX_ERROR.' Please run this as a player.');
      return;
    }
    $world = implode(' ', $args);
    if(trim($world) === ''){
      $player->sendMessage(self::PREFIX_ERROR.' /worldtp <folder name>');
      return;
    }

    $server = $this->getServer();
    $level = $server->getLevelByName($world);
    if(!$level instanceof Level){
      $player->sendMessage(self::PREFIX.' Attempting to load world '.$world.'...');
      $res = $server->loadLevel($world);
      if($res) $level = $server->getLevelByName($world);

      if(!$level instanceof Level){
        $player->sendMessage(self::PREFIX_ERROR.' Failed to load level');
        return;
      }else $player->sendMessage(self::PREFIX.' Loaded world '.$world.'!');
    }
    $res = $player->teleport($level->getSafeSpawn());
    if($res){
      $player->sendMessage(self::PREFIX.' Successfully teleported you to '.$world);
    }else{
      $player->sendMessage(self::PREFIX_ERROR.' Failed to teleported you to '.$world.'!');
    }
  }

    public function commandPlayerTP(CommandSender $player, Command $command, string $label, array $args)
    {
        if(count($args) < 2){
            $player->sendMessage(self::PREFIX_ERROR.' /worldplayertp <player> <folder name>');
            return;
        }

        $targetName = array_shift($args);
        $target = $this->getServer()->getPlayer($targetName);
        if (!$target instanceof Player){
            $player->sendMessage(self::PREFIX_ERROR." Fail to find selected player($targetName)");
            return;
        }
        $world = implode(' ', $args);

        $server = $this->getServer();
        $level = $server->getLevelByName($world);
        if(!$level instanceof Level){
            $player->sendMessage(self::PREFIX.' Attempting to load world '.$world.'...');
            $res = $server->loadLevel($world);
            if($res) $level = $server->getLevelByName($world);

            if(!$level instanceof Level){
                $player->sendMessage(self::PREFIX_ERROR.' Failed to load level');
                return;
            }else $player->sendMessage(self::PREFIX.' Loaded world '.$world.'!');
        }
        $res = $player->teleport($level->getSafeSpawn());
        if($res){
            $player->sendMessage(self::PREFIX.' Successfully teleported '.$target->getName().' to '.$world);
        }else{
            $player->sendMessage(self::PREFIX_ERROR.' Failed to teleported '.$target->getName().' to '.$world.'!');
        }
    }

  public function commandList(CommandSender $sender, Command $command, string $label, array $args)
  {
    $server = $this->getServer();
    $path = $server->getDataPath().'worlds/';
    $dir = new \DirectoryIterator($path);
    $current = "";
    if($sender instanceof Player){
      $current = $sender->getLevel()->getFolderName();
    }
    $levels = [];
    foreach($dir as $obj){
      /** @var $obj \SplFileInfo */
      if($obj->isFile()) continue;
      if($obj->getFilename() === '.' OR $obj->getFilename() === '..') continue;
      $levels[$obj->getFilename()] = null;
    }
    foreach($server->getLevels() as $level){
      $levels[$level->getFolderName()] = $level->getName();
    }
    $message = [];
    $message[] = self::PREFIX.' Worlds list';
    foreach($levels as $filename => $displayName){
      $msg = self::PREFIX;
      if($filename == $current){
        $msg .= Format::GOLD.' X '.Format::WHITE.$filename;
        if(!is_null($displayName)) $msg .= ' -> '.$displayName;
      }elseif(is_null($displayName)){
        $msg .= Format::DARK_RED." + ".Format::WHITE.$filename;
      }else{
        $msg .= Format::DARK_GREEN." + ".Format::WHITE.$filename.' -> '.$displayName;
      }
      $message[] = $msg;
    }
    $sender->sendMessage(implode("\n", $message));
  }

  public function commandLoad(CommandSender $sender, Command $command, string $label, array $args)
  {
    $world = implode(' ', $args);
    if(trim($world) === ''){
      $sender->sendMessage(self::PREFIX_ERROR.' /worldload <folder name>');
      return;
    }
    $server = $this->getServer();
    if($server->isLevelLoaded($world)){
      $sender->sendMessage(self::PREFIX_ERROR." Level is loaded!");
      return;
    }
    $res = $server->loadLevel($world);
    if($res){
      $sender->sendMessage(self::PREFIX.' Loaded level: '.$world);
    }else{
      $sender->sendMessage(self::PREFIX_ERROR.' Failed to load level: '.$world.'!');
    }
  }

  public function commandUnload(CommandSender $sender, Command $command, string $label, array $args)
  {
    $world = implode(' ', $args);
    $server = $this->getServer();
    if(!$server->isLevelLoaded($world)){
      $sender->sendMessage(self::PREFIX_ERROR." Level is not loaded!");
      return;
    }
    $level = $server->getLevelByName($world);
    $res = $server->unloadLevel($level);
    if($res){
      $sender->sendMessage(self::PREFIX.' Unloaded level: '.$world);
    }else{
      $sender->sendMessage(self::PREFIX_ERROR.' Failed to unload level: '.$world.'!');
    }
  }
}
