<?php

/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace AMWScan;

use AMWScan\Modules\Wordpress;

class Modules
{
    /**
     * Check path.
     *
     * @param $path
     */
    public static function init($path)
    {
        Wordpress::init($path);
    }

    /**
     * Is verified file.
     *
     * @param $path
     *
     * @return bool
     */
    public static function isVerified($path)
    {
        return Wordpress::isVerified($path);
    }
}
