<?php
namespace Lychee\Component\IdGenerator;

use Predis\Command\ScriptCommand;

class IdGenerateRedisCommand extends ScriptCommand {
    public function getScript() {
        return <<<LUA
local seq = redis.call('incr', KEYS[1])
if seq == 1 then
    redis.call('pexpire', KEYS[1], 1)
end
if seq >= 1023 then
    while seq ~= 1 do
        seq = redis.call('incr', KEYS[1])
    end
    redis.call('pexpire', KEYS[1], 1)
end
local time = redis.call('time')
local second = time[1] - ARGV[1]
local millisecond = math.floor(time[2] / 1000)
local id = second * math.pow(2, 20) + millisecond * math.pow(2, 10) + seq
return id
LUA;
    }

    protected function getKeysCount() {
        return 1;
    }
}