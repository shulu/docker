<?php
namespace Lychee\Component\GraphStorage\Redis\Command;

use Predis\Command\ScriptCommand;

class CreateRelationCommand extends ScriptCommand {
    public function getScript() {
        return <<<LUA
local lastItem = redis.call('zrevrange', KEYS[1], 0, 0, 'withscores')
if #lastItem == 0 then
    return redis.call('zadd', KEYS[1], 1, ARGV[1])
else
    return redis.call('zadd', KEYS[1], lastItem[2] + 1, ARGV[1])
end

LUA;
    }

    protected function getKeysCount() {
        return 1;
    }
} 