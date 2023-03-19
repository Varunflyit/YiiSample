<?php

namespace app\helpers;

/**
 * @author Yoyon Cahyono <yoyoncahyono@gmail.com>
 */
class UuidHelper
{
    /**
     * Converts a UUID string into a compact binary string.
     *
     * @param  string $uuid UUID in canonical format, i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a
     *
     * @return string compact 16-byte binary representation of the UUID.
     */
    public static function uuid2bin($uuid)
    {
        // normalize to uppercase, remove hyphens
        $hex = str_replace('-', '', strtoupper($uuid));

        // H for big-endian to behave similarly to MySQL's
        // HEX() and UNHEX() functions.
        $bin = pack('H*', $hex);

        return $bin;
    }

    /**
     * Converts a compact 16-byte binary representation of the UUID into
     * a string in canonical format, i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a.
     *
     * @param  string $uuidBin compact 16-byte binary representation of the UUID.
     *
     * @return string UUID in canonical format, i.e. 25769c6c-d34d-4bfe-ba98-e0ee856f3e7a
     */
    public static function bin2uuid($uuidBin)
    {
        // H for big-endian to behave similarly to MySQL's
        // HEX() and UNHEX() functions.
        $hexArray = unpack('H*', $uuidBin);

        $hex = strtolower(array_shift($hexArray));

        // break into components
        $components = [
          substr($hex, 0, 8),
          substr($hex, 8, 4),
          substr($hex, 12, 4),
          substr($hex, 16, 4),
          substr($hex, 20, 12),
        ];

        return implode('-', $components);
    }
}
