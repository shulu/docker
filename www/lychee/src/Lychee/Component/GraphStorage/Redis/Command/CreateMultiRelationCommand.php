<?php
namespace Lychee\Component\GraphStorage\Redis\Command;

use Predis\Command\ScriptCommand;

class CreateMultiRelationCommand extends ScriptCommand {
    public function getScript() {
        return <<<LUA
local lastItem = redis.call('zrevrange', KEYS[1], 0, 0, 'withscores')
local lastScore = 1
if #lastItem > 0 then
    lastScore = lastItem[2] + 1
end

local r = {}
for i = 1, #ARGV do
    local id = ARGV[i]
    if redis.call('zadd', KEYS[1], lastScore, id) then
        table.insert(r, id)
        lastScore = lastScore + 1
    end
end

return r

LUA;
    }

    protected function getKeysCount() {
        return 1;
    }

    protected function filterArguments(array $arguments) {
        $argv = array_pop($arguments);
        $arguments = parent::filterArguments(array_merge($arguments, $argv));

        return $arguments;
    }
} 