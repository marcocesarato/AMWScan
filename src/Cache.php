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

use AMWScan\Abstracts\SingletonAbstract;
use RuntimeException;

class Cache extends SingletonAbstract
{
    /**
     * Cache.
     *
     * @var array
     */
    protected static $cache;

    /**
     * Cache file extension.
     *
     * @var string
     */
    protected static $ext = 'cache';

    /**
     * Temp dir.
     *
     * @var string
     */
    protected $tempdir;

    /**
     * Cache constructor.
     */
    protected function __construct()
    {
        parent::__construct();
        $tempdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . Scanner::getLowerName() . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

        if (!is_dir($tempdir) && (!mkdir($tempdir, 0777, true) && !is_dir($tempdir))) {
            throw new RuntimeException(sprintf('Temp Directory "%s" was not created', $tempdir));
        }

        $this->tempdir = $tempdir;
    }

    /**
     * Set cache item.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    public function set($key, $value, $ttl = 3600)
    {
        $key = $this->key($key);

        $data = ['ttl' => $ttl > 0 ? time() + $ttl : $ttl, 'data' => $value];

        if (!Scanner::isCacheEnabled()) {
            self::$cache[$key] = $data;

            return true;
        }

        $fileData = '<?php return ' . var_export($data, true) . ';';
        if (file_put_contents($this->tempdir . $key . '.' . self::$ext, $fileData) !== false) {
            self::$cache[$key] = $data;

            return true;
        }

        return false;
    }

    /**
     * Update time to live.
     *
     * @param string $key
     * @param int $ttl
     *
     * @return bool
     */
    public function touch($key, $ttl = 3600)
    {
        if (!Scanner::isCacheEnabled()) {
            return true;
        }

        if ($data = $this->get($key)) {
            return $this->set($key, $data, $ttl);
        }

        return false;
    }

    /**
     * Get cache item.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $key = $this->key($key);
        $file = $this->tempdir . $key . '.' . self::$ext;

        $data = false;
        if (isset(self::$cache[$key])) {
            $data = self::$cache[$key];
        } elseif (Scanner::isCacheEnabled() && is_file($file)) {
            $data = include $file;
            self::$cache[$key] = $data;
        }

        if ($data !== false) {
            if (!empty($data)) {
                if (isset($data['ttl'], $data['data'])) {
                    if ($data['ttl'] <= 0 || $data['ttl'] >= time()) {
                        return $data['data'];
                    }

                    $this->deleteItem($file);
                }
            } else {
                $this->deleteItem($file);
            }
        }

        return $default;
    }

    /**
     * Clean cache.
     *
     * @return bool
     */
    public function flush()
    {
        return $this->deleteRegex();
    }

    /**
     * Delete cache item.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        $key = $this->key($key);

        return $this->deleteItem($this->tempdir . $key);
    }

    /**
     * Delete item cache by regex.
     *
     * @param string $pattern
     *
     * @return bool
     */
    public function deleteRegex($pattern = '*')
    {
        $return = true;

        foreach (glob($this->tempdir . $pattern, GLOB_NOSORT | GLOB_BRACE) as $cacheFile) {
            if (!$this->deleteItem($cacheFile)) {
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Generate key.
     *
     * @see https://stackoverflow.com/a/42058764
     *
     * @param string $key
     *
     * @return string
     */
    protected function key($key)
    {
        $filename = preg_replace(
            '~' .
            '[<>:"/\\|?*]|' .            // file system reserved https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
            '[\x00-\x1F]|' .             // control characters http://msdn.microsoft.com/en-us/library/windows/desktop/aa365247%28v=vs.85%29.aspx
            '[\x7F\xA0\xAD]|' .          // non-printing characters DEL, NO-BREAK SPACE, SOFT HYPHEN
            '[#\[\]@!$&\'()+,;=]|' .     // URI reserved https://tools.ietf.org/html/rfc3986#section-2.2
            '[{}^\~`]' .                 // URL unsafe characters https://www.ietf.org/rfc/rfc1738.txt
            '~x',
            '-',
            $key
        );
        // avoids ".", ".." or ".hiddenFiles"
        $filename = ltrim($filename, '.-');
        // maximize filename length to 255 bytes http://serverfault.com/a/9548/44086

        // reduce consecutive characters
        $filename = preg_replace([
            '/ +/', // "file   name.zip" becomes "file-name.zip"
            '/_+/', // "file___name.zip" becomes "file-name.zip"
            '/-+/', // "file---name.zip" becomes "file-name.zip"
        ], '-', $filename);
        $filename = preg_replace([
            '/-*\.-*/',  // "file--.--.-.--name.zip" becomes "file.name.zip"
            '/\.{2,}/',  // "file...name..zip" becomes "file.name.zip"
        ], '.', $filename);
        // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));
        // ".file-name.-" becomes "file-name"
        $filename = trim($filename, '.-');

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $filename = mb_strcut(pathinfo($filename, PATHINFO_FILENAME), 0, 255 - ($ext !== '' ? strlen($ext) + 1 : 0), mb_detect_encoding($filename)) . ($ext !== '' ? '.' . $ext : '');

        return trim($filename, '.-_');
    }

    /**
     * Delete a item.
     *
     * @param string $item
     *
     * @return bool
     */
    private function deleteItem($item)
    {
        unset(self::$cache[basename($item)]);

        clearstatcache(true, $item);

        if (file_exists($item)) {
            if (!unlink($item)) {
                return file_put_contents($item, '') !== false;
            }

            clearstatcache(true, $item);
        }

        return true;
    }
}
