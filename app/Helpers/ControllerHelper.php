<?php

namespace App\Helpers;

class ControllerHelper
{
    /**
     * Makes sure that the given $content is always an array (pass by reference!)
     * 
     * @param mixed &$content the content to put in an array
     */
    public static function makeToArray(&$content): void
    {
        if (!is_array($content)) {
            $content = [$content];
        }
    }
}
