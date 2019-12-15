**PreciseCpsCounter** is an API that can measure the CPS (clicks per second) of a player (all left clicks are taken into account).
You can use PreciseCpsCounter from other plugins to get the CPS of a player using the public function getCps:

`public function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1) : float;`

An example of how you can use this plugin:
- Sending the CPS in a popup every second.
`$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $_) : void{
    foreach($this->getServer()->getOnlinePlayers() as $p){
        $p->sendPopup('CPS: '.$this->getCps($p));
    }
}), 20);`

Apparently, it does not work with CPSs greater than 30.