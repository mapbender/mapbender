<?php

namespace FOM\UserBundle\Util;

class PasswordUtil
{
    /**
     * Generate a ~random alphanumeric string
     *
     * Taken from http://code.activestate.com/recipes/576894-generate-a-salt/
     *
     * @param int $length
     * @return string
     */
    public static function generateSalt($length = 15)
    {
        $characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $i = 0;
        $salt = "";
        do {
            $salt .= $characterList{mt_rand(0,strlen($characterList)-1)};
            $i++;
        } while ($i < $length);

        return $salt;
    }
}
