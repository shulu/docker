<?php
namespace Lychee\Component\Counter;

use Predis\Command\ScriptCommand;

class DecrMustEnoughRedisCommand extends ScriptCommand {
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    public function getScript() {
        return <<<LUA
local val,decr_num = tonumber(redis.call('get', KEYS[1])), tonumber(ARGV[1])
if val == nil or val < decr_num then
    return nil
end

return redis.call('decrby', KEYS[1], decr_num)
LUA;
    }

    protected function getKeysCount() {
        return 1;
    }

    public function parseResponse($data) {
        if (!is_numeric($data)) {
            return false;
        }
        return $data;
    }
} 