<?php

/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace AMWScan\Interfaces;

interface VerifierInterface
{
    /**
     * Initialize path.
     *
     * @param $path
     *
     * @return mixed
     */
    public static function init($path);

    /**
     * Is verified file.
     *
     * @param $path
     *
     * @return mixed
     */
    public static function isVerified($path);
}
