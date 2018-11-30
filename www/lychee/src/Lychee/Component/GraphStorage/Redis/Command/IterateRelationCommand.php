<?php
namespace Lychee\Component\GraphStorage\Redis\Command;

use Predis\Command\ScriptCommand;

class IterateRelationCommand extends ScriptCommand {
    public function getScript() {
        return <<<LUA
local cursor = tonumber(ARGV[1])
if cursor == 0 then
    cursor = '+inf'
end

local count = tonumber(ARGV[2])

local id_score_list = redis.call('zrevrangebyscore', KEYS[1], cursor, '-inf', 'withscores', 'limit', 0, count + 1)
local id_score_count = #id_score_list
local result_count
local next_cursor
if id_score_count == 2 * (count + 1) then
    result_count = id_score_count / 2 - 1
    next_cursor = id_score_list[id_score_count]
else
    result_count = id_score_count / 2
    next_cursor = 0
end

local ids = {}
for i = 1, result_count do
     table.insert(ids, i, id_score_list[1 + 2 * (i - 1)])
end
return {next_cursor, ids}
LUA;
    }

    protected function getKeysCount() {
        return 1;
    }

    public function parseResponse($data) {
        if (is_array($data)) {
            $data[0] = (int) $data[0];
            if (count($data) === 1) {
                $data[1] = array();
            }
        }

        return $data;
    }
} 