<?php

/**
 * @link http://www.appttitude.com/
 * @copyright Copyright (c) 2014 APPttitude
 * @license http://www.appttitude.com/license/
 */

namespace jcvalerio\kartikgii\crud\helpers;

class UtilHelper
{

    /**
     * Code MUST use 4 spaces for indenting, not tabs.
     * @param integer $howManyIdentations Define how many indentation is required.
     * @return string With the required indentation.
     */
    public static function indentCode($howManyIdentations)
    {
        $indentation = '';
        for ($i = 0; $i < $howManyIdentations; $i++) {
            $indentation .= '    ';
        }
        return $indentation;
    }
}
