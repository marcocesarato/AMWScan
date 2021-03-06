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

use Phar;

class Path
{
    /**
     * Clean path.
     *
     * @param $path
     *
     * @return string
     */
    public static function get($path)
    {
        $path = trim($path);
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return preg_replace('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '+/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Return real current filename.
     *
     * @return string|string[]|null
     */
    public static function getCurrent()
    {
        if (strlen(Phar::running()) > 0) {
            return Phar::running(false);
        }
        $string = pathinfo('index.php');
        $dir = parse_url($string['dirname'] . '/' . $string['basename']);

        return realpath($dir['path']);
    }

    /**
     * Return real current path.
     *
     * @return string|string[]|null
     */
    public static function getCurrentDir()
    {
        return dirname(self::getCurrent());
    }

    /**
     * Get filesize.
     *
     * @param $filePath
     * @param int $dec
     *
     * @return string
     */
    public static function getFilesize($filePath, $dec = 2)
    {
        $bytes = filesize($filePath);
        $size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
    }

    /**
     * Convert to Bytes.
     *
     * @param string $from
     *
     * @return int|null
     */
    public static function sizeToBytes($from)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from, -2));

        if (is_numeric($suffix[0])) {
            return preg_replace('/\D/', '', $from);
        }
        $pow = array_flip($units)[$suffix] ?: null;
        if ($pow === null) {
            return null;
        }

        return $number * pow(1024, $pow);
    }
}
