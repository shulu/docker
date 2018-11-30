<?php
namespace Lychee\Module\Measurement\ActiveUser;

use Predis\Command\ScriptCommand;

class RecordCommand extends ScriptCommand {
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    public function getScript() {
        return <<<LUA
local userId = tonumber(ARGV[1])
if redis.call('exists', KEYS[1]) == 1 then
    local byteCount = redis.call('strlen', KEYS[1])
    if userId > byteCount * 8 then
        redis.call('setbit', KEYS[1], userId + 1000000 - 1, 0)
    end
    redis.call('setbit', KEYS[1], userId - 1, 1)
else
    redis.call('setbit', KEYS[1], userId + 1000000 - 1, 0)
    redis.call('setbit', KEYS[1], userId - 1, 1)
end
LUA;
    }

    protected function getKeysCount() {
        return 1;
    }
}