<?php

namespace io\enchy\friendsystem;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\utils\Config;

class FriendSystem extends PluginBase implements Listener {

  public $data = [];
  public $data_file = [];

  public $prefix = "[FriendSystem] >";

  public function getPrefix(){
    return $this->prefix . " ";
  }

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getServer()->getLogger()->info($this->getPrefix() . "Enabled by @EnchyID!");
    
    $this->data_file = new Config($this->getDataFolder() . "data.json", Config::JSON);
    $this->data = $this->data_file->getAll();

    $this->saveResource("data.json");
  }

  public function saveData(){
    $this->data_file->setAll($this->data);
    $this->data_file->save();
  }

  public function getMyFriends(string $name) : array {
    $results = [];
    foreach(array_keys($this->data[$name]["friends"]) as $indexs){
      $results[] = $indexs;
    }
    return $results;
  }

  public function getMyPendings(string $name) : array {
    $results = [];
    foreach(array_keys($this->data[$name]["pendings"]) as $indexs){
      $results[] = $indexs;
    }
    return $results;
  }

  public function getMyRequests(string $name) : array {
    $results = [];
    foreach(array_keys($this->data[$name]["requests"]) as $indexs){
      $results[] = $indexs;
    }
    return $results;
  }

  public function onJoin(PlayerJoinEvent $event){
    $player = $event->getPlayer();
    $name = strtolower($player->getName());

    $reqs = count($this->getMyRequests($name));
    
    if(!isset($this->data[$name])){
      $this->data[$name] = array(
        "friends" => [],
        "requests" => [],
        "pendings" => []
      );
      $this->saveData();
      $player->sendMessage($this->getPrefix() . "You have " . $reqs . " of requests.");
    }else{
      $player->sendMessage($this->getPrefix() . "You have " . $reqs . " of requests.");
    }
  }

  public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool {
    if($cmd->getName() == "friends"){
      
      $player = $sender;
      if($player instanceof Player){
        $name = strtolower($player->getName());
        
        if(!isset($args[0]){
           $player->sendMessage($this->getPrefix() . "Usage /friends help - to show all commands.");
          return true;
        }
      
        if(strtolower($args[0]) == "help"){
          $text = 
            "+=====================[ Friends Help ]=====================+\n" .
            "+ /friends add <name> - to add new friend\n" .
            "+ /friends delete <name> - to delete your friend\n" .
            "+ /friends accept <name> - to accept friend request of player\n" .
            "+ /friends deny <name> - to deny friend request of player\n" .
            "+ /friends requests - to show all of your friend requests\n" .
            "+ /friends pendings - to show all of your friend pendings\n" .
            "+ /friends list - to show all of your friends\n" .
            "+=====================[ Friends Help ]=====================+"
            $player->sendMessage($text);
        }
      
        if(strtolower($args[0]) == "add"){
          if(!isset($args[1])){
            $player->sendMessage($this->getPrefix() . "Usage /friends add <name>");
            return true;
          }
          
          $target = strtolower($args[1]);

          if($target == $name){
            $player->sendMessage($this->getPrefix() . "You can't add your self!");
            return true;
          }
          
          if(!isset($this->data[$target])){
            $player->sendMessage($this->getPrefix() . "Can't find player by name " . $target . ", not found!");
            return true;
          }

          if(isFriend($name, $target)){
            $player->sendMessage($this->getPrefix() . "You has friend with " . $target . "!");
            return true;
          }

          $this->addRequest($name, $target);
          $player->sendMessage($this->getPrefix() . "Your request of " . $target . " has been sent!");
          return true;
        }

        if(strtolower($args[0]) == "delete"){
          if(!isset($args[1])){
            $player->sendMessage($this->getPrefix() . "Usage /friends delete <name>");
            return true;
          }
          
          $target = strtolower($args[1]);

          if($target == $name){
            $player->sendMessage($this->getPrefix() . "You can't delete your self!");
            return true;
          }
          
          if(!isset($this->data[$name]["friends"][$target])){
            $player->sendMessage($this->getPrefix() . "Can't find friend by name " . $target . ", not be friend!");
            return true;
          }

          $this->deleteFriend($name, $target);
          $player->sendMessage($this->getPrefix() . "You now not friend with " . $target . "!");
          return true
        }

        if(strtolower($args[0]) == "accept"){
          if(!isset($args[1])){
            $player->sendMessage($this->getPrefix() . "Usage /friends accept <name>");
            return true;
          }
          
          $target = strtolower($args[1]);

          if($target == $name){
            $player->sendMessage($this->getPrefix() . "You can't accept your self!");
            return true;
          }
          
          if(!isset($this->data[$name]["requests"][$target])){
            $player->sendMessage($this->getPrefix() . "Can't find request by name " . $target . ", not be request!");
            return true;
          }

          $this->acceptRequest($name, $target);
          $player->sendMessage($this->getPrefix() . "You now friend with " . $target . "!");
          return true
        }

        if(strtolower($args[0]) == "deny"){
          if(!isset($args[1])){
            $player->sendMessage($this->getPrefix() . "Usage /friends deny <name>");
            return true;
          }
          
          $target = strtolower($args[1]);

          if($target == $name){
            $player->sendMessage($this->getPrefix() . "You can't deny your self!");
            return true;
          }
          
          if(!isset($this->data[$name]["requests"][$target])){
            $player->sendMessage($this->getPrefix() . "Can't find request by name " . $target . ", not be request!");
            return true;
          }

          $this->denyRequest($name, $target);
          $player->sendMessage($this->getPrefix() . "You deny request of " . $target . "!");
          return true
        }

        if(strtolower($args[0]) == "requests"){
          if(count($this->getMyRequests($name)) == 0){
            $player->sendMessage($this->getPrefix() . "You don't have requests");
            return true;
          }

          $text = "+==========[ Friend Requests ]==========+\n";
          foreach($this->getMyRequests($name) as $indexs){
            $index = $this->getMyRequests($name)[$indexs];
            $text .= "+ " . $index . "\n";
          }

          $player->sendMessage($text);
          return true
        }

        if(strtolower($args[0]) == "pendings"){
          if(count($this->getMyRequests($name)) == 0){
            $player->sendMessage($this->getPrefix() . "You don't have pendings");
            return true;
          }

          $text = "+==========[ Friend Pendings ]==========+\n";
          foreach($this->getMyPendings($name) as $indexs){
            $index = $this->getMyPendings($name)[$indexs];
            $text .= "+ " . $index . "\n";
          }

          $player->sendMessage($text);
          return true
        }

        if(strtolower($args[0]) == "list"){
          if(count($this->getMyRequests($name)) == 0){
            $player->sendMessage($this->getPrefix() . "You don't have friends");
            return true;
          }

          $text = "+==========[ Friends List ]==========+\n";
          foreach($this->getMyFriends($name) as $indexs){
            $index = $this->getMyFriends($name)[$indexs];
            $text .= "+ " . $index . "\n";
          }

          $player->sendMessage($text);
          return true
        }
        return true;
      }
      return false;
    }
    return false;
  }

  public function isFriend(string $name, string $target){
    return isset($this->data[$name]["friends"][$target]);
  }

  public function addRequest(string $name, string $target){
    $this->data[$target]["requests"][$name] = true;
    $this->data[$name]["pendings"][$target] = true;
    $this->saveData();
  }

  public function addFriend(string $name, string $target){
    $this->data[$target]["friends"][$name] = true;
    $this->data[$name]["friends"][$target] = true;
    $this->saveData();
  }

  public function deleteRequest(string $name, string $target){
    unset($this->data[$name]["requests"][$target]);
    unset($this->data[$target]["pendings"][$name]);
    $this->saveData();
  }

  public function deletePending(string $name, string $target){
    unset($this->data[$target]["requests"][$name]);
    unset($this->data[$name]["pendings"][$target]);
    $this->saveData();
  }

  public function deleteFriend(string $name, string $target){
    unset($this->data[$target]["friends"][$name]);
    unset($this->data[$name]["friends"][$target]);
    $this->saveData();
  }

  public function acceptRequest(string $name, string $target){
    $this->deleteRequest($name, $target);
    $this->deletePending($target, $name);
    $this->addFriend($name, $target);
    $this->saveData();
  }

  public function denyRequest(string $name, string $target){
    $this->deleteRequest($name, $target);
    $this->deletePending($target, $name);
    $this->saveData();
  }

}
