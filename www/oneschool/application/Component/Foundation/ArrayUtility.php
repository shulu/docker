<?php
namespace Lychee\Component\Foundation;

class ArrayUtility {
    /**
     * @param mixed $array
     * @param callable $predicate
     *
     * @return bool|int|string false when no found, or key of value
     *      make the predicate return true.
     */
    static public function find($array, $predicate) {
        foreach ((array)$array as $key => $value) {
            if (call_user_func($predicate, $value) === true) {
                return $key;
            }
        }
        return false;
    }

    static public function mergeKeepKeys() {
        $argList = func_get_args();
        $result = array();
        foreach((array)$argList as $arg){
            foreach((array)$arg as $key => $value){
                $result[$key] = $value;
            }
        }
        return $result;
    }

    static public function mergeValues($arrays) {
        return call_user_func_array('array_merge', array_values($arrays));
    }

    static public function mapKeys($callback, $array) {
        $result = array();
        foreach ($array as $key => $value) {
            $mappedKey = $callback($key);
            $result[$mappedKey] = $value;
        }
        return $result;
    }

    static public function mapOnlyValues($callback, $array) {
        $result = array();
        foreach ($array as $value) {
            $result[] = $callback($value);
        }
        return $result;
    }

    static public function mapByColumn($array, $columnName) {
        if (empty($array)) {
            return array();
        }

        $firstItem = current($array);
        if (is_object($firstItem)) {
            $result = array();
            if (isset($firstItem->$columnName)) {
                foreach ($array as $object) {
                    $result[$object->$columnName] = $object;
                }
            } else {
                $getter = 'get' . ucfirst($columnName);
                foreach ($array as $object) {
                    $result[$object->$getter()] = $object;
                }
            }

            return $result;
        } else {
            $result = array();
            foreach ($array as $object) {
                $result[$object[$columnName]] = $object;
            }
            return $result;
        }
    }

    static public function columnsGroupBy($array, $columnName, $groupKey) {
        if (empty($array)) {
            return array();
        }

        if (is_object(current($array))) {
            $result = array();
            foreach ($array as $object) {
                $result[$object->$groupKey][] = $object->$columnName;
            }
            return $result;
        } else {
            $result = array();
            foreach ($array as $item) {
                $result[$item[$groupKey]][] = $item[$columnName];
            }
            return $result;
        }
    }

    static public function columns($array, $columnName, $indexKey = null) {
        if (empty($array)) {
            return array();
        }

        if (is_object(current($array))) {
            $result = array();
            if ($indexKey !== null) {
                foreach ($array as $object) {
                    $result[$object->$indexKey] = $object->$columnName;
                }
            } else {
                foreach ($array as $object) {
                    $result[] = $object->$columnName;
                }
            }
            return $result;
        } else {
            return array_column($array, $columnName, $indexKey);
        }
    }

    static public function separate($array, $predicate, $keepKey = false) {
        $trueResult = array();
        $falseResult = array();
        if ($keepKey) {
            foreach ($array as $key => $value) {
                if ($predicate($value, $key) === true) {
                    $trueResult[$key] = $value;
                } else {
                    $falseResult[$key] = $value;
                }
            }
        } else {
            foreach ($array as $key => $value) {
                if ($predicate($value, $key) === true) {
                    $trueResult[] = $value;
                } else {
                    $falseResult[] = $value;
                }
            }
        }

        return array($trueResult, $falseResult);
    }

    static public function filterNonNull($array) {
        return array_filter($array, function($value){return $value !== null;});
    }

    static public function filterValuesNonNull($array) {
        $result = array();
        foreach ($array as $value) {
            if ($value !== null) {
                $result[] = $value;
            }
        }
        return $result;
    }

    static public function mergeSortedArrays() {

    }

    static public function binarySearch(array $array, $needle) {
        $start = 0;
        $end = count($array) - 1;

        while ($start <= $end) {
            $middle = (int) ($start + ($end - $start) / 2);
            if ($needle < $array[$middle]) {
                $end = $middle - 1;
            } else if ($needle > $array[$middle]) {
                $start = $middle + 1;
            } else {
                return $needle;
            }
        }

        return false;
    }

    static public function diffValue($a, $b) {
        $map = $out = array();
        foreach($a as $val) {
            $map[$val] = 1;
        }
        foreach($b as $val) {
            if(isset($map[$val])) {
                $map[$val] = 0;
            }
        }
        foreach($map as $val => $ok) {
            if($ok) {$out[] = $val;}
        }
        return $out;
    }

    /**
     * @param int[] $arr
     * @param int[] $sortingArr
     *
     * @return int[]
     */
    static public function sortIntsByIntArray($arr, $sortingArr) {
        $arrFlipped = array_flip($arr);
        $result = array();
        foreach ($sortingArr as $sortingKey) {
            if (isset($arrFlipped[$sortingKey])) {
                $result[] = $sortingKey;
                unset($arrFlipped[$sortingKey]);
            }
        }
        foreach ($arrFlipped as $key=>$value) {
            $result[] = $key;
        }
        return $result;
    }

    /**
     * @param array $arr
     * @param int $size
     * @param callable $func
     */
    static public function sliding(array $arr, $size, callable $func) {
        assert($size > 0);
        $offset = 0;
        while (true) {
            $slice = array_slice($arr, $offset, $size);
            if (empty($slice)) {
                break;
            }

            $func($slice);
            $offset += $size;
        }
    }
} 