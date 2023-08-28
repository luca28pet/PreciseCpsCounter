<?php

namespace luca28pet\PreciseCpsCounter;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use function array_unshift;
use function array_pop;
use function microtime;
use function round;
use function count;
use function array_filter;

class Main extends PluginBase implements Listener{

    private const ARRAY_MAX_SIZE = 100;

    /** @var bool */
    private $countLeftClickBlock;

    /** @var array[] */
    private $clicksData = [];

    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->countLeftClickBlock = $this->getConfig()->get('count-left-click-on-block');
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function initPlayerClickData(Player $player) : void{
        $this->clicksData[mb_strtolower($player->getName())] = [];
    }

    public function addClick(Player $player) : void{
        array_unshift($this->clicksData[mb_strtolower($player->getName())], microtime(true));
        if(count($this->clicksData[mb_strtolower($player->getName())]) >= self::ARRAY_MAX_SIZE){
            array_pop($this->clicksData[mb_strtolower($player->getName())]);
        }
    }

    public function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1) : float{
        if(!isset($this->clicksData[mb_strtolower($player->getName())]) || empty($this->clicksData[mb_strtolower($player->getName())])){
            return 0.0;
        }
        $ct = microtime(true);
        return round(count(array_filter($this->clicksData[mb_strtolower($player->getName())], static function(float $t) use ($deltaTime, $ct) : bool{
                return ($ct - $t) <= $deltaTime;
            })) / $deltaTime, $roundPrecision);
    }

    public function removePlayerClickData(Player $player) : void{
        unset($this->clicksData[mb_strtolower($player->getName())]);
    }

    public function playerJoin(PlayerJoinEvent $event) : void{
        $this->initPlayerClickData($event->getPlayer());
    }

    public function playerQuit(PlayerQuitEvent $event) : void{
        $this->removePlayerClickData($event->getPlayer());
    }

    public function packetReceive(DataPacketReceiveEvent $event): void
    {
        $player = $event->getOrigin()->getPlayer();
        if ($player !== null && isset($this->clicksData[mb_strtolower($player->getName())])) {
            $packet = $event->getPacket();

            if (
                ($packet::NETWORK_ID === InventoryTransactionPacket::NETWORK_ID && $packet->trData instanceof UseItemOnEntityTransactionData) ||
                ($packet::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID && $packet->sound === LevelSoundEvent::ATTACK_NODAMAGE) ||
                ($this->countLeftClickBlock && $packet::NETWORK_ID === PlayerActionPacket::NETWORK_ID && $packet->action === PlayerAction::START_BREAK) ||
                ($packet::NETWORK_ID === PlayerAuthInputPacket::NETWORK_ID &&
                    (($packet->getInputFlags() & (1 << PlayerAuthInputFlags::MISSED_SWING)) !== 0))
            ) {
                $this->addClick($player);
            }
        }
    }
}
