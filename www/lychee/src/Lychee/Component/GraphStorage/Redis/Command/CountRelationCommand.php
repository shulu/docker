<?php
namespace Lychee\Component\GraphStorage\Redis\Command;

use Predis\Command\ScriptCommand;

class CountRelationCommand extends ScriptCommand {
    /**
     * Gets the body of a Lua script.
     *
     * @return string
     */
    public function getScript() {
        return <<<LUA
local r = {}
for i = 1, #KEYS do
    local count = redis.call('zcard', KEYS[i])
    table.insert(r, ARGV[i])
    table.insert(r, count)
end
return r
LUA;
    }

    private $keysCount = 0;

    protected function getKeysCount() {
        return $this->keysCount;
    }

    protected function filterArguments(array $arguments) {
        //the first argument is the "key prefix", and the rest are all keys
        $keyPrefix = $arguments[0];
        $ids = $arguments[1];
        $keys = array_map(function($id) use ($keyPrefix) {return $keyPrefix.$id;}, $ids);
        $this->keysCount = count($keys);
        $arguments = parent::filterArguments(array_merge($keys, $ids));

        return $arguments;
    }

    public function parseResponse($data) {
        $result = array();

        for ($i = 0; $i < count($data); $i++) {
            $result[$data[$i]] = intval($data[++$i]);
        }

        return $result;
    }
} 