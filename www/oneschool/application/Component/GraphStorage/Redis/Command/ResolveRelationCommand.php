<?php
namespace Lychee\Component\GraphStorage\Redis\Command;

use Predis\Command\ScriptCommand;

class ResolveRelationCommand extends ScriptCommand {

    public function getScript() {
        return <<<LUA
local r = {}
for i = 1, #ARGV do
    local id = ARGV[i]
    if redis.call('zscore', KEYS[1], id) then
        table.insert(r, id)
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

//    public function parseResponse($data) {
//        $result = array();
//
//        for ($i = 0; $i < count($data); $i++) {
//            $result[$data[$i]] = boolval($data[++$i]);
//        }
//
//        return $result;
//    }
} 