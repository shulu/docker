<?php
namespace Lychee\Bundle\ApiBundle\AntiSpam;

use Predis\Command\ScriptCommand;

class CheckSpamCommand extends ScriptCommand {

    private $script;

    /**
     * CheckSpamCommand constructor.
     *
     * @param int $timeWindow
     * @param int $maxActionPerWindow
     */
    public function __construct($timeWindow, $maxActionPerWindow) {
        $this->script = <<<LUA
local c = redis.call('incr', KEYS[1])
if c == 1 or c > {$maxActionPerWindow} then
    redis.call('expire', KEYS[1], {$timeWindow})
end
return c
LUA;
    }

    public function getScript() {
        return $this->script;
    }

    protected function getKeysCount() {
        return 1;
    }
}