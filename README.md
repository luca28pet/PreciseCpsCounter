# PreciseCpsCounter
Latest development phars: https://poggit.pmmp.io/ci/luca28pet/PreciseCpsCounter

**PreciseCpsCounter** is an API that can measure the CPS (clicks per second) of a player (all left clicks are taken into account).
You can use PreciseCpsCounter from other plugins to get the CPS of a player using the public function getCps:

`public function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1) : float;`

An example of how you can use this plugin:

- Sending the CPS in a popup every second. 
    ```
    $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $_) : void{
        foreach($this->getServer()->getOnlinePlayers() as $p){
            $p->sendPopup('CPS: '.$this->getCps($p));
        }
    }), 20);
    ```

Apparently, it does not work with CPSs greater than 30.

## Known Issues
**Incorrect CPS measurement when hitting blocks**
If a click is well-timed and you manage to initiate the breaking of 2 (or more) blocks with one click, it will result as 2+ clicks because the client sends two PlayerActionPackets.
You can workaround this by disabling count-left-click-on-block in the config.yml file, but it will not take into account any left click on blocks.
