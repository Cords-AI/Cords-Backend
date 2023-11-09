<?php

namespace App;

class ArrayUtils
{
    public static function keyMap(array $array, string $key): array
    {
        $newArray = [];
        foreach($array as $row) {
            $newArray[$row->$key] = $row;
        }
        return $newArray;
    }
}
