<?php

/**
 * Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 * @version 0.4.0.38
 */

namespace marcocesarato\amwscan;

/**
 * Class CSV
 * @package marcocesarato\amwscan
 */
Class CSV {

    /**
     * Read
     * @param $filename
     * @return array
     */
    public static function read($filename) {
        if(!file_exists($filename)) {
            return array();
        }
        $file_handle = fopen($filename, 'r');
        $array       = array();
        while(!feof($file_handle)) {
            $array[] = fgetcsv($file_handle, 1024);
        }
        fclose($file_handle);

        return $array;
    }

    /**
     * Generate
     * @param $data
     * @param string $delimiter
     * @param string $enclosure
     * @return string
     */
    public static function generate($data, $delimiter = ',', $enclosure = '"') {
        $handle = fopen('php://temp', 'r+');
        foreach($data as $line) {
            fputcsv($handle, $line, $delimiter, $enclosure);
        }
        $contents = '';
        rewind($handle);
        while(!feof($handle)) {
            $contents .= fread($handle, 8192);
        }
        fclose($handle);

        return $contents;
    }

    /**
     * Write
     * @param $filename
     * @param $data
     * @param string $delimiter
     * @param string $enclosure
     */
    public static function write($filename, $data, $delimiter = ',', $enclosure = '"') {
        $csv = self::generate($data, $delimiter, $enclosure);
        return file_put_contents($filename, $csv);
    }
}