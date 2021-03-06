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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class Actions
{
    /**
     * Put contents.
     *
     * @param $filename
     * @param $data
     * @param int $flags
     * @param null $context
     *
     * @return false|int
     */
    public static function putContents($filename, $data, $flags = 0, $context = null)
    {
        if (Scanner::isBackupEnabled()) {
            self::makeBackup($filename);
        }

        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * Clean Evil Code.
     *
     * @param $code
     * @param $patternFound
     *
     * @return string|string[]|null
     */
    public static function cleanEvilCode($code, $patternFound)
    {
        foreach ($patternFound as $pattern) {
            preg_match('/(<\?php)(.*?)(' . preg_quote($pattern['match'], '/') . '[\s\r\n]*;?)/si', $code, $match);
            $match[2] = trim($match[2]);
            $match[4] = trim($match[4]);
            if (!empty($match[2]) || !empty($match[4])) {
                $code = str_replace($match[0], $match[1] . $match[2] . $match[4] . $match[5], $code);
            } else {
                $code = str_replace($match[0], '', $code);
            }
            $code = preg_replace('/<\?php[\s\r\n]*\?>/i', '', $code);
        }

        return $code;
    }

    /**
     * Clean Evil Code Line.
     *
     * @param $code
     * @param $patternFound
     *
     * @return string
     */
    public static function cleanEvilCodeLine($code, $patternFound)
    {
        $lines = explode(PHP_EOL, $code);
        foreach ($patternFound as $pattern) {
            unset($lines[(int)$pattern['line'] - 1]);
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Delete File.
     *
     * @param $file
     *
     * @return bool
     */
    public static function deleteFile($file)
    {
        if (Scanner::isBackupEnabled()) {
            self::makeBackup($file);
        }

        return self::unlink($file);
    }

    /**
     * Move to Quarantine.
     *
     * @param $file
     *
     * @return string
     */
    public static function moveToQuarantine($file)
    {
        $quarantine = Scanner::getPathQuarantine() . DIRECTORY_SEPARATOR . str_replace(realpath(Scanner::getPathScan()), '', realpath($file));
        $quarantine = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $quarantine);
        if (!is_dir(dirname($quarantine)) && (!mkdir($concurrentDirectory = dirname($quarantine), 0755, true) && !is_dir($concurrentDirectory))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        rename($file, $quarantine);

        return $quarantine;
    }

    /**
     * Move to Quarantine.
     *
     * @param $file
     *
     * @return string
     */
    public static function makeBackup($file)
    {
        $scanPath = realpath(Scanner::getPathScan());
        if (is_file($scanPath)) {
            $scanPath = dirname($scanPath);
        }
        $backup = Scanner::getPathBackups() . DIRECTORY_SEPARATOR . str_replace($scanPath, '', realpath($file));
        $backup = Path::get($backup);
        if (!is_dir(dirname($backup)) &&
            (!mkdir($concurrentDirectory = dirname($backup), 0755, true) && !is_dir($concurrentDirectory))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        copy($file, $backup);

        return $backup;
    }

    /**
     * Add to Whitelist.
     *
     * @param $file
     * @param $patternFound
     *
     * @return false|int
     */
    public static function addToWhitelist($file, $patternFound)
    {
        $whitelist = Scanner::getWhitelist();
        foreach ($patternFound as $key => $pattern) {
            $exploit = $pattern['key'];
            $lineNumber = $pattern['line'];
            $match = $pattern['match'];
            $fileName = str_replace(Scanner::getPathScan(), '', $file);
            $key = md5($exploit . $fileName . $lineNumber);
            $whitelist[$key] = [
                'file' => $fileName,
                'exploit' => $exploit,
                'line' => $lineNumber,
                'match' => $match,
            ];
        }
        Scanner::setWhitelist($whitelist);

        return file_put_contents(Scanner::getPathWhitelist(), json_encode($whitelist));
    }

    /**
     * Open with VIM.
     *
     * @param $file
     */
    public static function openWithVim($file)
    {
        if (Scanner::isBackupEnabled()) {
            self::makeBackup($file);
        }

        $descriptors = [
            ['file', '/dev/tty', 'r'],
            ['file', '/dev/tty', 'w'],
            ['file', '/dev/tty', 'w'],
        ];
        $process = proc_open("vim '$file'", $descriptors, $pipes);
        while (true) {
            $procStatus = proc_get_status($process);
            if ($procStatus['running'] == false) {
                break;
            }
        }
    }

    /**
     * Open with Nano.
     *
     * @param $file
     */
    public static function openWithNano($file)
    {
        if (Scanner::isBackupEnabled()) {
            self::makeBackup($file);
        }

        $descriptors = [
            ['file', '/dev/tty', 'r'],
            ['file', '/dev/tty', 'w'],
            ['file', '/dev/tty', 'w'],
        ];
        $process = proc_open("nano '$file'", $descriptors, $pipes);
        while (true) {
            $procStatus = proc_get_status($process);
            if ($procStatus['running'] == false) {
                break;
            }
        }
    }

    /**
     * Clean Quarantine path.
     *
     * @return bool
     */
    public static function cleanQuarantine()
    {
        return self::unlink(Scanner::getPathQuarantine());
    }

    /**
     * Unlink.
     *
     * @param $path
     *
     * @return bool
     */
    protected static function unlink($path)
    {
        if (is_dir($path) && !is_link($path)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $action($fileinfo->getRealPath());
            }

            return rmdir($path);
        }

        if (file_exists($path)) {
            return unlink($path);
        }

        return false;
    }
}
