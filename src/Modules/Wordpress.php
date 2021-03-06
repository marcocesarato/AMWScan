<?php

/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace AMWScan\Modules;

use AMWScan\Cache;
use AMWScan\Console\CLI;
use AMWScan\Interfaces\VerifierInterface;
use GlobIterator;

class Wordpress implements VerifierInterface
{
    /**
     * Wordpress roots.
     *
     * @var array
     */
    protected static $roots = [];

    /**
     * Time to live.
     *
     * @var int
     */
    protected static $ttl = -1;

    /**
     * Initialize path.
     *
     * @param $path
     */
    public static function init($path)
    {
        if (self::isRoot($path)) {
            $version = self::getVersion($path);
            if (!empty($version) && !isset(self::$roots[$path])) {
                $locale = self::getLocale($path);
                CLI::writeLine('Found WordPress ' . $version . ' (' . $locale . ') at "' . $path . '"', 1, 'green');

                $plugins = self::getPlugins($path);
                self::$roots[$path] = [
                    'path' => $path,
                    'version' => $version,
                    'locale' => $locale,
                    'plugins' => $plugins,
                ];
                self::getChecksums($version, $locale);
                self::getPluginsChecksums($plugins);
            }
        }
    }

    /**
     * Detect root.
     *
     * @param $path
     *
     * @return bool
     */
    public static function isRoot($path)
    {
        return
            is_dir($path) &&
            is_dir($path . DIRECTORY_SEPARATOR . 'wp-admin') &&
            is_dir($path . DIRECTORY_SEPARATOR . 'wp-content') &&
            is_dir($path . DIRECTORY_SEPARATOR . 'wp-includes') &&
            is_file($path . DIRECTORY_SEPARATOR . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php');
    }

    /**
     * Get version.
     *
     * @param $root
     *
     * @return string|null
     */
    public static function getVersion($root)
    {
        $versionFile = $root . DIRECTORY_SEPARATOR . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php';
        if (is_file($versionFile)) {
            $versionContent = file_get_contents($versionFile);
            preg_match('/\$wp_version[\s]*=[\s]*[\'"]([0-9.]+)[\'"]/m', $versionContent, $match);
            $version = trim($match[1]);
            if (!empty($version)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * Get locale.
     *
     * @param $root
     *
     * @return string
     */
    public static function getLocale($root)
    {
        $versionFile = $root . DIRECTORY_SEPARATOR . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php';
        if (is_file($versionFile)) {
            $versionContent = file_get_contents($versionFile);
            preg_match('/\$wp_local_package[\s]*=[\s]*[\'"]([A-Za-z_-]+)[\'"]/m', $versionContent, $match);
            if (!empty($match[1])) {
                return $match[1];
            }
        }

        return 'en_US';
    }

    /**
     * Get plugins.
     *
     * @param $root
     *
     * @return string[]
     */
    public static function getPlugins($root)
    {
        $plugins = [];
        $files = new GlobIterator($root . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $cur) {
            if ($cur->isFile()) {
                $headers = self::getPluginHeaders($cur->getPathname());
                if (!empty($headers['name']) && !empty($headers['version'])) {
                    if (empty($headers['domain'])) {
                        $headers['domain'] = $cur->getBasename('.' . $cur->getExtension());
                    }
                    $headers['path'] = $cur->getPath();
                    $plugins[$cur->getPath()] = $headers;
                    CLI::writeLine('Found WordPress Plugin ' . $headers['name'] . ' ' . $headers['version'], 1, 'green');
                }
            }
        }

        return $plugins;
    }

    /**
     * Get file headers.
     *
     * @param $file
     *
     * @return string[]
     */
    public static function getPluginHeaders($file)
    {
        $headers = ['name' => 'Plugin Name', 'version' => 'Version', 'domain' => 'Text Domain'];
        $fileData = file_get_contents($file);
        $fileData = str_replace("\r", "\n", $fileData);
        foreach ($headers as $field => $regex) {
            if (preg_match('/^[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $fileData, $match) && $match[1]) {
                $headers[$field] = trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $match[1]));
            } else {
                $headers[$field] = '';
            }
        }

        return $headers;
    }

    /**
     * Get checksums.
     *
     * @param string $version
     * @param string $locale
     *
     * @return array|false
     */
    public static function getChecksums($version, $locale = 'en_US')
    {
        $cache = Cache::getInstance();
        $key = 'wordpress_' . $locale . '-' . $version;
        $checksums = $cache->get($key);

        if (is_null($checksums)) {
            CLI::writeLine('Retrieving checksums of Wordpress ' . $version, 1, 'grey');

            $checksums = [];
            $dataChecksums = self::getData('https://api.wordpress.org/core/checksums/1.0/?version=' . $version . '&locale=' . $locale);
            if (!$dataChecksums) {
                $cache->set($key, false, self::$ttl);

                return false;
            }
            $versionChecksums = $dataChecksums['checksums'];

            // Sanitize paths and checksum
            foreach ($versionChecksums as $filePath => $checksum) {
                $sanitizePath = self::sanitizePath($filePath);
                $checksums[$sanitizePath] = strtolower($checksum);
            }
            $cache->set($key, $checksums, self::$ttl);
        }

        return $checksums;
    }

    /**
     * Get plugins checksums.
     *
     * @param array $plugins
     *
     * @return array|false
     */
    public static function getPluginsChecksums($plugins = [])
    {
        $cache = Cache::getInstance();
        $pluginsChecksums = [];
        foreach ($plugins as $plugin) {
            $key = 'wordpress-plugin_' . $plugin['domain'] . '-' . $plugin['version'];
            $checksums = $cache->get($key);

            if (!is_null($checksums)) {
                $pluginsChecksums[$plugin['domain']][$plugin['version']] = $checksums;
                continue;
            }

            CLI::writeLine('Retrieving checksums of Wordpress Plugin ' . $plugin['name'] . ' ' . $plugin['version'], 1, 'grey');
            $dataChecksums = self::getData('https://downloads.wordpress.org/plugin-checksums/' . $plugin['domain'] . '/' . $plugin['version'] . '.json');
            if (!$dataChecksums) {
                $cache->set($key, [], self::$ttl);
                $pluginsChecksums[$plugin['domain']][$plugin['version']] = [];
                continue;
            }
            $pluginChecksums = $dataChecksums['files'];

            $checksums = [];
            foreach ($pluginChecksums as $filePath => $checksum) {
                $path = $plugin['path'] . DIRECTORY_SEPARATOR . $filePath;
                $root = self::getRoot($path);
                $sanitizePath = str_replace($root['path'], '', $path);
                $sanitizePath = self::sanitizePath($sanitizePath);
                if (is_array($checksum['md5'])) {
                    $checksums[$sanitizePath] = array_filter($checksum['md5'], 'strtolower');
                } else {
                    $checksums[$sanitizePath] = strtolower($checksum['md5']);
                }
            }
            $cache->set($key, $checksums, self::$ttl);
            $pluginsChecksums[$plugin['domain']][$plugin['version']] = $checksums;
        }

        return $pluginsChecksums;
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
        if (!is_file($path)) {
            return false;
        }

        $root = self::getRoot($path);
        if (!empty($root)) {
            $comparePath = str_replace($root['path'], '', $path);
            $comparePath = self::sanitizePath($comparePath);
            $checksums = self::getChecksums($root['version'], $root['locale']);
            if (!$checksums) {
                return false;
            }
            // Core
            if (!empty($checksums[$comparePath])) {
                $checksum = md5_file($path);
                $checksum = strtolower($checksum);

                if (is_array($checksums[$comparePath])) {
                    return in_array($checksum, $checksums[$comparePath], true);
                }

                return $checksums[$comparePath] === $checksum;
            }
            // Plugins
            $pluginRoot = self::getPluginRoot($root, $path);
            $pluginsChecksums = self::getPluginsChecksums($root['plugins']);
            if (!empty($root['plugins'][$pluginRoot])) {
                $plugin = $root['plugins'][$pluginRoot];
                $checksums = $pluginsChecksums[$plugin['domain']][$plugin['version']];
                if (!empty($pluginsChecksums[$plugin['domain']][$plugin['version']]) && !empty($checksums[$comparePath])) {
                    $checksum = md5_file($path);
                    $checksum = strtolower($checksum);

                    return $checksums[$comparePath] === $checksum;
                }
            }
        }

        return false;
    }

    /**
     * Get root from child file.
     *
     * @param $path
     *
     * @return array
     */
    public static function getRoot($path)
    {
        foreach (self::$roots as $root) {
            if (strpos($path, $root['path']) === 0) {
                return $root;
            }
        }

        return null;
    }

    /**
     * Get root from child plugin file.
     *
     * @param $root
     * @param $path
     *
     * @return mixed|null
     */
    protected static function getPluginRoot($root, $path)
    {
        $pluginsPaths = array_keys($root['plugins']);
        foreach ($pluginsPaths as $pluginPath) {
            if (strpos($path, $pluginPath) === 0) {
                return $pluginPath;
            }
        }

        return null;
    }

    /**
     * Sanitize path to be compared.
     *
     * @param $path
     *
     * @return string
     */
    public static function sanitizePath($path)
    {
        $sanitized = preg_replace('#[\\\\/]+#', DIRECTORY_SEPARATOR, $path);
        $sanitized = trim($sanitized);
        $sanitized = trim($sanitized, DIRECTORY_SEPARATOR);

        return strtolower($sanitized);
    }

    /**
     * HTTP request get data.
     *
     * @param $url
     *
     * @return mixed|null
     */
    protected static function getData($url)
    {
        $headers = get_headers($url);
        if (substr($headers[0], 9, 3) !== '200') {
            return null;
        }

        $content = @file_get_contents($url);

        return @json_decode($content, true);
    }
}
