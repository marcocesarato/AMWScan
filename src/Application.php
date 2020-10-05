<?php

/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2020
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace marcocesarato\amwscan;

use CallbackFilterIterator;
use Exception;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Class Application.
 */
class Application
{
    /**
     * App name.
     *
     * @var string
     */
    public static $name = 'amwscan';

    /**
     * Version.
     *
     * @var string
     */
    public static $version = '0.5.1.74';

    /**
     * Root path.
     *
     * @var string
     */
    public static $root = './';

    /**
     * Quarantine path.
     *
     * @var string
     */
    public static $pathQuarantine = '/quarantine/';

    /**
     * Logs Path.
     *
     * @var string
     */
    public static $pathLogs = '/scanner.log';

    /**
     * Infected logs path.
     *
     * @var string
     */
    public static $pathLogsInfected = '/scanner-infected.log';

    /**
     * Whitelist path.
     *
     * @var string
     */
    public static $pathWhitelist = '/scanner-whitelist.csv';

    /**
     * Path to scan.
     *
     * @var string
     */
    public static $pathScan = './';

    /**
     * Max filesize.
     *
     * @var int
     */
    public static $maxFilesize = -1;

    /**
     * File extensions to scan.
     *
     * @var array
     */
    public static $extensions = array(
        'htaccess',
        'php',
        'php3',
        'ph3',
        'php4',
        'ph4',
        'php5',
        'ph5',
        'php7',
        'ph7',
        'phtm',
        'phtml',
        'ico',
    );
    /**
     * Arguments.
     *
     * @var array
     */
    public static $argv = array();

    /**
     * Whitelist.
     *
     * @var array
     */
    public static $whitelist = array();

    /**
     * Functions.
     *
     * @var array
     */
    public static $functions = array();

    /**
     * Exploits.
     *
     * @var array
     */
    public static $exploits = array();

    /**
     * Settings.
     *
     * @var array
     */
    public static $settings = array();

    /**
     * Summary scanned.
     *
     * @var int
     */
    public static $summaryScanned = 0;

    /**
     * Summary detected.
     *
     * @var int
     */
    public static $summaryDetected = 0;

    /**
     * Summary removed.
     *
     * @var array
     */
    public static $summaryRemoved = array();

    /**
     * Summary ignored.
     *
     * @var array
     */
    public static $summaryIgnored = array();

    /**
     * Summary edited.
     *
     * @var array
     */
    public static $summaryEdited = array();

    /**
     * Summary quarantined.
     *
     * @var array
     */
    public static $summaryQuarantine = array();

    /**
     * Summary Whitelisted.
     *
     * @var array
     */
    public static $summaryWhitelist = array();

    /**
     * Ignore paths.
     *
     * @var array
     */
    public static $ignorePaths = array();

    /**
     * Filter paths.
     *
     * @var array
     */
    public static $filterPaths = array();

    /**
     * Application constructor.
     */
    public function __construct()
    {
    }

    /**
     * Initialize.
     */
    private function init()
    {
        if (self::$root === './') {
            self::$root = self::currentDirectory();
        }

        if (self::$pathScan === './') {
            self::$pathScan = self::currentDirectory();
        }
        self::$pathQuarantine = self::$root . self::$pathQuarantine;
        self::$pathLogs = self::$root . self::$pathLogs;
        self::$pathWhitelist = self::$root . self::$pathWhitelist;
        self::$pathLogsInfected = self::$root . self::$pathLogsInfected;

        // Prepare whitelist
        self::$whitelist = CSV::read(self::$pathWhitelist);

        Definitions::optimizeSig(Definitions::$SIGNATURES);
    }

    /**
     * Run application.
     *
     * @param null $args
     */
    public function run($args = null)
    {
        try {
            if (function_exists('gc_enable') && (function_exists('gc_enable') && !gc_enabled())) {
                gc_enable();
            }
            // Initialize arguments
            $this->arguments($args);
            // Print header
            Console::header();
            // Initialize
            $this->init();
            // Initialize modes
            $this->modes();

            // Start scanning
            Console::displayLine('Start scanning...');

            Console::writeLine('Scan date: ' . date('d-m-Y H:i:s'));
            Console::writeLine('Scanning ' . self::$pathScan, 2);

            // Mapping files
            Console::writeLine('Mapping files...');
            $iterator = $this->mapping();

            // Counting files
            $files_count = iterator_count($iterator);
            Console::writeLine('Found ' . $files_count . ' files', 2);
            Console::writeLine('Checking files...', 2);
            Console::progress(0, $files_count);

            // Scan all files
            $this->scan($iterator);

            // Scan finished
            Console::writeBreak(2);
            Console::write('Scan finished!', 'green');
            Console::writeBreak(3);

            // Print summary
            $this->summary();
        } catch (Exception $e) {
            Console::writeBreak();
            Console::writeLine($e->getMessage(), 1, 'red');
        }
    }

    /**
     * Initialize application arguments.
     *
     * @param null $args
     */
    private function arguments($args = null)
    {
        // Define Arguments
        self::$argv = new Argv();
        self::$argv->addFlag('agile', array('alias' => '-a', 'default' => false));
        self::$argv->addFlag('help', array('alias' => '-h', 'default' => false));
        self::$argv->addFlag('log', array('alias' => '-l', 'default' => null, 'has_value' => true));
        self::$argv->addFlag('report', array('alias' => '-r', 'default' => false));
        self::$argv->addFlag('version', array('alias' => '-v', 'default' => false));
        self::$argv->addFlag('update', array('alias' => '-u', 'default' => false));
        self::$argv->addFlag('only-signatures', array('alias' => '-s', 'default' => false));
        self::$argv->addFlag('only-exploits', array('alias' => '-e', 'default' => false));
        self::$argv->addFlag('only-functions', array('alias' => '-f', 'default' => false));
        self::$argv->addFlag('list', array('default' => false));
        self::$argv->addFlag('list-exploits', array('default' => false));
        self::$argv->addFlag('list-functions', array('default' => false));
        self::$argv->addFlag('exploits', array('default' => false, 'has_value' => true));
        self::$argv->addFlag('functions', array('default' => false, 'has_value' => true));
        self::$argv->addFlag('whitelist-only-path', array('default' => false));
        self::$argv->addFlag('max-filesize', array('default' => -1, 'has_value' => true));
        self::$argv->addFlag('silent', array('default' => false));
        self::$argv->addFlag('ignore-paths', array('alias' => '--ignore-path', 'default' => null, 'has_value' => true));
        self::$argv->addFlag('filter-paths', array('alias' => '--filter-path', 'default' => null, 'has_value' => true));
        self::$argv->addArgument('path', array('var_args' => true, 'default' => ''));
        self::$argv->parse($args);

        // Version
        if (isset(self::$argv['version']) && self::$argv['version']) {
            die();
        }

        // Help
        if (isset(self::$argv['help']) && self::$argv['help']) {
            Console::helper();
        }

        // List exploits
        if (isset(self::$argv['list']) && self::$argv['list']) {
            Console::helplist();
        }

        // List exploits
        if (isset(self::$argv['list-exploits']) && self::$argv['list-exploits']) {
            Console::helplist('exploits');
        }

        // List functions
        if (isset(self::$argv['list-functions']) && self::$argv['list-functions']) {
            Console::helplist('functions');
        }

        // Update
        if (isset(self::$argv['update']) && self::$argv['update']) {
            self::update();
        }

        // Report mode
        if (isset(self::$argv['report']) && self::$argv['report']) {
            self::$settings['report'] = true;
        } else {
            self::$settings['report'] = false;
        }

        // Silent
        if (isset(self::$argv['silent']) && self::$argv['silent']) {
            Console::$silent = true;
            self::$settings['report'] = true;
        }

        // Max filesize
        if (isset(self::$argv['max-filesize']) && self::$argv['max-filesize']) {
            self::$maxFilesize = trim(self::$argv['max-filesize']);
            if (!is_numeric(self::$argv['max-filesize'])) {
                self::$maxFilesize = $this->convertToBytes(self::$maxFilesize);
            }
        }

        // Write logs
        if (isset(self::$argv['log']) && !empty(self::$argv['log'])) {
            self::$settings['log'] = true;
            if (is_string(self::$argv['log'])) {
                self::$pathLogs = self::$argv['log'];
            }
        } else {
            self::$settings['log'] = false;
        }

        // Ignore paths
        if (isset(self::$argv['ignore-paths']) && !empty(self::$argv['ignore-paths'])) {
            $paths = explode(',', self::$argv['ignore-paths']);
            foreach ($paths as $path) {
                $path = trim($path);
                self::$ignorePaths[] = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
            }
        }

        // Filter paths
        if (isset(self::$argv['filter-paths']) && !empty(self::$argv['filter-paths'])) {
            $paths = explode(',', self::$argv['filter-paths']);
            foreach ($paths as $path) {
                $path = trim($path);
                self::$filterPaths[] = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);
            }
        }

        // Check on whitelist only file path and not line number
        if (isset(self::$argv['whitelist-only-path']) && self::$argv['whitelist-only-path']) {
            self::$settings['whitelist-only-path'] = true;
        } else {
            self::$settings['whitelist-only-path'] = false;
        }

        // Check Filter exploits
        if (isset(self::$argv['exploits']) && self::$argv['exploits']) {
            if (is_string(self::$argv['exploits'])) {
                $filtered = str_replace(array("\n", "\r", "\t", ' '), '', self::$argv['exploits']);
                $filtered = @explode(',', $filtered);
                if (!empty($filtered) && count($filtered) > 0) {
                    foreach (Definitions::$EXPLOITS as $key => $value) {
                        if (in_array($key, $filtered)) {
                            self::$exploits[$key] = $value;
                        }
                    }
                    if (!empty(self::$exploits) && count(self::$exploits) > 0) {
                        Console::writeLine('Exploit to search: ' . implode(', ', array_keys(self::$exploits)));
                    } else {
                        self::$exploits = array();
                    }
                }
            }
        }

        // Check if exploit mode is enabled
        if (isset(self::$argv['only-exploits']) && self::$argv['only-exploits']) {
            self::$settings['exploits'] = true;
        } else {
            self::$settings['exploits'] = false;
        }

        // Check functions to search
        if (isset(self::$argv['functions']) && self::$argv['functions']) {
            if (is_string(self::$argv['functions'])) {
                self::$functions = str_replace(array("\n", "\r", "\t", ' '), '', self::$argv['functions']);
                self::$functions = @explode(',', self::$functions);
                if (!empty(self::$functions) && count(self::$functions) > 0) {
                    Console::writeLine('Functions to search: ' . implode(', ', self::$functions));
                } else {
                    self::$functions = array();
                }
            }
        }

        // Check if functions mode is enabled
        if (isset(self::$argv['only-functions']) && self::$argv['only-functions']) {
            self::$settings['functions'] = true;
        } else {
            self::$settings['functions'] = false;
        }

        // Check if only signatures mode is enabled
        if (isset(self::$argv['only-signatures'])) {
            self::$settings['signatures'] = true;
            self::$settings['exploits'] = false;
            self::$settings['functions'] = false;
        } else {
            self::$settings['signatures'] = false;
        }

        // Check if agile scan is enabled
        if (isset(self::$argv['agile']) && self::$argv['agile']) {
            self::$settings['agile'] = true;
            self::$exploits = Definitions::$EXPLOITS;
            self::$settings['exploits'] = true;
            self::$exploits['execution'] = '/\b(eval|assert|passthru|exec|include|system|pcntl_exec|shell_exec|`|array_map|ob_start|call_user_func(_array)?)\s*\(\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/';
            self::$exploits['concat_vars_with_spaces'] = '/(\$([a-zA-Z0-9]+)[\s\r\n]*\.[\s\r\n]*){8}/';  // concatenation of more than 8 words, with spaces
            self::$exploits['concat_vars_array'] = '/(\$([a-zA-Z0-9]+)(\{|\[)([0-9]+)(\}|\])[\s\r\n]*\.[\s\r\n]*){8}.*?(?=\})\}/i'; // concatenation of more than 8 words, with spaces
            unset(self::$exploits['nano'], self::$exploits['double_var2'], self::$exploits['base64_long']);
        } else {
            self::$settings['agile'] = false;
        }

        // Check if logs and scan at the same time
        if (isset(self::$argv['log']) && self::$argv['log'] && isset(self::$argv['report']) && self::$argv['report']) {
            unset(self::$settings['log']);
        }

        // Check for path or functions as first argument
        $arg = self::$argv->arg(0);
        if (!empty($arg)) {
            $path = trim($arg);
            if (file_exists(realpath($path))) {
                self::$pathScan = realpath($path);
            }
        }

        // Check path
        if (!is_dir(self::$pathScan)) {
            self::$pathScan = pathinfo(self::$pathScan, PATHINFO_DIRNAME);
        }
    }

    /**
     * Init application modes.
     */
    private function modes()
    {
        if (self::$settings['functions'] && self::$settings['signatures'] && self::$settings['exploits']) {
            Console::writeLine('Can\'t be set flags --only-signatures, --only-functions and --only-exploits together!', 2);
            die();
        }

        if (self::$settings['functions'] && self::$settings['signatures']) {
            Console::writeLine('Can\'t be set both flags --only-signatures and --only-functions together!', 2);
            die();
        }

        if (self::$settings['signatures'] && self::$settings['exploits']) {
            Console::writeLine('Can\'t be set both flags --only-signatures and --only-exploits together!', 2);
            die();
        }

        if (self::$settings['functions'] && self::$settings['exploits']) {
            Console::writeLine('Can\'t be set both flags --only-functions and --only-exploits together!', 2);
            die();
        }

        // Malware Definitions
        if (self::$settings['functions'] || (!self::$settings['exploits'] && empty(self::$functions))) {
            // Functions to search
            self::$functions = Definitions::$FUNCTIONS;
        } elseif (self::$settings['exploits']) {
            self::$functions = array();
            if (!self::$settings['agile']) {
                Console::writeLine('Exploits mode enabled');
            }
        } else {
            Console::writeLine('No functions to search');
        }

        if (self::$argv['max-filesize'] > 0) {
            Console::writeLine('Max filesize: ' . self::$maxFilesize . ' bytes', 2);
        }

        // Exploits to search
        if (!self::$settings['functions'] && empty(self::$exploits)) {
            self::$exploits = Definitions::$EXPLOITS;
        }

        if (self::$settings['agile']) {
            Console::writeLine('Agile mode enabled');
        }

        if (self::$settings['signatures']) {
            Console::writeLine('Signatures mode enabled');
        }

        if (self::$settings['report']) {
            Console::writeLine('Report scan mode enabled');
        }

        if (self::$settings['functions']) {
            self::$exploits = array();
        }

        if (self::$settings['exploits']) {
            self::$functions = array();
        }

        if (self::$settings['signatures']) {
            self::$exploits = array();
            self::$functions = array();
        }
    }

    /**
     * Map files.
     *
     * @return CallbackFilterIterator
     */
    public function mapping()
    {
        // Mapping files
        $directory = new RecursiveDirectoryIterator(self::$pathScan);
        $files = new RecursiveIteratorIterator($directory);
        $iterator = new CallbackFilterIterator($files, function ($cur) {
            $ignore = false;
            $wildcard = '.*?'; // '[^\\\\\\/]*'
            // Ignore
            foreach (self::$ignorePaths as $ignorePath) {
                $ignorePath = preg_quote($ignorePath, ';');
                $ignorePath = str_replace('\*', $wildcard, $ignorePath);
                if (preg_match(';' . $ignorePath . ';i', $cur->getPath())) {
                    $ignore = true;
                }
            }
            // Filter
            foreach (self::$filterPaths as $filterPath) {
                $filterPath = preg_quote($filterPath, ';');
                $filterPath = str_replace('\*', $wildcard, $filterPath);
                if (!preg_match(';' . $filterPath . ';i', $cur->getPath())) {
                    $ignore = true;
                }
            }

            return
                !$ignore &&
                $cur->isFile() &&
                in_array($cur->getExtension(), self::$extensions, true)
            ;
        });

        return $iterator;
    }

    /**
     * Detect infected favicon.
     *
     * @param $file
     *
     * @return bool
     */
    public static function isInfectedFavicon($file)
    {
        // Case favicon_[random chars].ico
        $_FILE_NAME = $file->getFilename();
        $_FILE_EXTENSION = $file->getExtension();

        return ((strpos($_FILE_NAME, 'favicon_') === 0) && ($_FILE_EXTENSION === 'ico') && (strlen($_FILE_NAME) > 12)) || preg_match('/^\.[\w]+\.ico/i', trim($_FILE_NAME));
    }

    /**
     * Scan file.
     *
     * @param $info
     *
     * @return array
     */
    public function scanFile($info)
    {
        $_FILE_PATH = $info->getPathname();

        $is_favicon = self::isInfectedFavicon($info);
        $pattern_found = array();

        $mime_type = 'text/php';
        if (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($_FILE_PATH);
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mime_type = finfo_file($finfo, $_FILE_PATH);
            finfo_close($finfo);
        }

        if (0 === stripos($mime_type, 'text')) {
            $deobfuctator = new Deobfuscator();

            $fc = file_get_contents($_FILE_PATH);
            $fc_clean = php_strip_whitespace($_FILE_PATH);
            $fc_deobfuscated = $deobfuctator->deobfuscate($fc);
            $fc_decoded = $deobfuctator->decode($fc_deobfuscated);

            // Scan exploits
            $last_match = null;
            foreach (self::$exploits as $key => $pattern) {
                $match_description = null;
                $lineNumber = null;
                if (@preg_match($pattern, $fc, $match, PREG_OFFSET_CAPTURE) || // Original
                   @preg_match($pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE) || // No comments
                   @preg_match($pattern, $fc_decoded, $match, PREG_OFFSET_CAPTURE)) { // Decoded
                    $last_match = $match[0][0];
                    $match_description = $key . "\n => " . $last_match;
                    if (!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/i', $fc, $match, PREG_OFFSET_CAPTURE)) {
                        $lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
                        $match_description = $key . ' [line ' . $lineNumber . "]\n => " . $last_match;
                    }
                    if (!empty($match_description)) {
                        //$pattern_found[$match_description] = $pattern;
                        $pattern_found[$match_description] = array(
                            'key' => $key,
                            'line' => $lineNumber,
                            'pattern' => $pattern,
                            'match' => $last_match,
                        );
                    }
                }
            }
            unset($last_match, $match_description, $lineNumber, $match);

            // Scan php commands
            $last_match = null;
            foreach (self::$functions as $_func) {
                $match_description = null;
                $func = preg_quote(trim($_func), '/');
                // Basic search
                $regex_pattern = "/(?:^|[\s\r\n]+|[^a-zA-Z0-9_>]+)(" . $func . "[\s\r\n]*\((?<=\().*?(?=\))\))/si";
                if (@preg_match($regex_pattern, $fc_decoded, $match, PREG_OFFSET_CAPTURE) ||
                   @preg_match($regex_pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE)) {
                    $last_match = explode($_func, $match[0][0]);
                    $last_match = $_func . $last_match[1];
                    $match_description = $_func . "\n => " . $last_match;
                    if (!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
                        $lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
                        $match_description = $_func . ' [line ' . $lineNumber . "]\n => " . $last_match;
                    }
                    if (!empty($match_description)) {
                        $pattern_found[$match_description] = array(
                            'key' => $_func,
                            'line' => $lineNumber,
                            'pattern' => $regex_pattern,
                            'match' => $last_match,
                        );
                    }
                }
                // Check of base64
                $regex_pattern_base64 = '/' . base64_encode($_func) . '/s';
                if (@preg_match($regex_pattern_base64, $fc_decoded, $match, PREG_OFFSET_CAPTURE) ||
                   @preg_match($regex_pattern_base64, $fc_clean, $match, PREG_OFFSET_CAPTURE)) {
                    $last_match = explode($_func, $match[0][0]);
                    $last_match = $_func . $last_match[1];
                    $match_description = $_func . "_base64\n => " . $last_match;

                    if (!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
                        $lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
                        $match_description = $_func . '_base64 [line ' . $lineNumber . "]\n => " . $last_match;
                    }
                    if (!empty($match_description)) {
                        $pattern_found[$match_description] = array(
                            'key' => $_func . '_base64',
                            'line' => $lineNumber,
                            'pattern' => $regex_pattern_base64,
                            'match' => $last_match,
                        );
                    }
                }

                /*$field = bin2hex($pattern);
                $field = chunk_split($field, 2, '\x');
                $field = '\x' . substr($field, 0, -2);
                $regex_pattern = "/(" . preg_quote($field) . ")/i";
                if (@preg_match($regex_pattern, $contents, $match, PREG_OFFSET_CAPTURE)) {
                    $found = true;
                    $lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
                    $pattern_found[$pattern . " [line " . $lineNumber . "]"] = $regex_pattern;
                }*/

                unset($last_match, $match_description, $lineNumber, $regex_pattern, $regex_pattern_base64, $match);
            }

            foreach (Definitions::$SIGNATURES as $key => $pattern) {
                $regex_pattern = '#' . $pattern . '#smiS';
                if (preg_match($regex_pattern, $fc_deobfuscated, $match, PREG_OFFSET_CAPTURE)) {
                    $last_match = $match[0][0];
                    if (!empty($last_match) && @preg_match('/' . preg_quote($match[0][0], '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
                        $lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
                        $match_description = 'Sign ' . $key . ' [line ' . $lineNumber . "]\n => " . $last_match;
                    }
                    if (!empty($match_description)) {
                        $pattern_found[$match_description] = array(
                            'key' => $key,
                            'line' => $lineNumber,
                            'pattern' => $regex_pattern,
                            'match' => $last_match,
                        );
                    }
                }
            }

            unset($fc, $fc_decoded, $fc_clean, $fc_deobfuscated);
        }

        if ($is_favicon) {
            $pattern_found['infected_icon'] = array(
                'key' => 'infected_icon',
                'line' => '',
                'pattern' => '',
                'match' => '',
            );
        }

        return $pattern_found;
    }

    /**
     * Run index.php.
     *
     * @param $iterator
     */
    private function scan($iterator)
    {
        $files_count = iterator_count($iterator);

        // Scanning
        foreach ($iterator as $info) {
            Console::progress(self::$summaryScanned, $files_count);

            $_FILE_PATH = $info->getPathname();
            $_FILE_EXTENSION = $info->getExtension();
            $_FILE_SIZE = filesize($_FILE_PATH);

            $is_favicon = self::isInfectedFavicon($info);

            if ((
                in_array($_FILE_EXTENSION, self::$extensions) &&
                (self::$maxFilesize < 1 || $_FILE_SIZE <= self::$maxFilesize) &&
                (!file_exists(self::$pathQuarantine) || strpos(realpath($_FILE_PATH), realpath(self::$pathQuarantine)) === false)
                   /*&& (strpos($filename, '-') === FALSE)*/
            ) ||
               $is_favicon) {
                $pattern_found = $this->scanFile($info);

                // Check whitelist
                $in_whitelist = 0;
                foreach (self::$whitelist as $item) {
                    foreach ($pattern_found as $key => $pattern) {
                        $lineNumber = $pattern['line'];
                        $exploit = $pattern['key'];
                        $whitelist_filePath = trim($item[0], ' "');
                        $whitelist_exploit = trim($item[1], ' "');
                        $whitelist_lineNumber = trim($item[2], ' "');

                        // TODO: from char to length
                        if (strpos($_FILE_PATH, $whitelist_filePath) !== false &&
                            $exploit == $whitelist_exploit &&
                           (self::$settings['whitelist-only-path'] || (!self::$settings['whitelist-only-path'] && $lineNumber == $whitelist_lineNumber))) {
                            $in_whitelist++;
                        }
                    }
                }

                // Scan finished

                self::$summaryScanned++;
                usleep(10);

                if (realpath($_FILE_PATH) != realpath(__FILE__) && ($is_favicon || !empty($pattern_found)) && ($in_whitelist === 0 || $in_whitelist != count($pattern_found))) {
                    self::$summaryDetected++;
                    if (self::$settings['report']) {
                        // Scan mode only
                        self::$summaryIgnored[] = 'File: ' . $_FILE_PATH . PHP_EOL .
                                                   'Exploits:' . PHP_EOL .
                                                   ' => ' . implode(PHP_EOL . ' => ', array_keys($pattern_found));
                        continue;
                    }

                    // Scan with code check
                    $_WHILE = true;
                    $last_command = '0';
                    Console::newLine(2);
                    Console::writeBreak();
                    Console::writeLine('PROBABLE MALWARE FOUND!', 1, 'red');

                    while ($_WHILE) {
                        $fc = file_get_contents($_FILE_PATH);
                        $preview_lines = explode(Console::eol(1), trim($fc));
                        $preview = implode(Console::eol(1), array_slice($preview_lines, 0, 1000));
                        if (!in_array($last_command, array('4', '5', '7'))) {
                            Console::displayLine("$_FILE_PATH", 2, 'yellow');
                            Console::display(Console::title(' PREVIEW ', '='), 'white', 'red');
                            Console::newLine(2);
                            Console::code($preview, $pattern_found);
                            if (count($preview_lines) > 1000) {
                                Console::newLine(2);
                                Console::display('  [ ' . (count($preview_lines) - 1000) . ' rows more ]');
                            }
                            Console::newLine(2);
                            Console::display(Console::title('', '='), 'white', 'red');
                        }
                        Console::newLine(2);
                        Console::writeLine('File path: ' . $_FILE_PATH, 1, 'yellow');
                        Console::writeLine('Exploits found: ' . Console::eol(1) . implode(Console::eol(1), array_keys($pattern_found)), 2, 'red');
                        Console::displayLine('OPTIONS:', 2);
                        $confirmation = Console::choice('What is your choice? ', array(
                            1 => 'Delete file',
                            2 => 'Move to quarantine',
                            3 => 'Try remove evil code',
                            4 => 'Try remove evil line code',
                            5 => 'Open/Edit with vim',
                            6 => 'Open/Edit with nano',
                            7 => 'Add to whitelist',
                            8 => 'Show source',
                            '-' => 'Ignore',
                        ));
                        Console::newLine();

                        $last_command = $confirmation;
                        unset($preview_lines, $preview);

                        if (in_array($confirmation, array('1'))) {
                            // Remove file
                            Console::writeLine('File path: ' . $_FILE_PATH, 1, 'yellow');
                            $confirm2 = Console::read('Want delete this file [y|N]? ', 'purple');
                            Console::newLine();
                            if ($confirm2 == 'y') {
                                unlink($_FILE_PATH);
                                self::$summaryRemoved[] = $_FILE_PATH;
                                Console::writeLine("File '$_FILE_PATH' removed!", 2, 'green');
                                $_WHILE = false;
                            }
                        } elseif (in_array($confirmation, array('2'))) {
                            // Move to quarantine
                            $quarantine = self::$pathQuarantine . str_replace(realpath(self::currentDirectory()), '', $_FILE_PATH);

                            if (!is_dir(dirname($quarantine))) {
                                if (!mkdir($concurrentDirectory = dirname($quarantine), 0755, true) && !is_dir($concurrentDirectory)) {
                                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                                }
                            }
                            rename($_FILE_PATH, $quarantine);
                            self::$summaryQuarantine[] = $quarantine;
                            Console::writeLine("File '$_FILE_PATH' moved to quarantine!", 2, 'green');
                            $_WHILE = false;
                        } elseif (in_array($confirmation, array('3')) && count($pattern_found) > 0) {
                            // Remove evil code
                            foreach ($pattern_found as $pattern) {
                                preg_match('/(<\?php)(.*?)(' . preg_quote($pattern['match'], '/') . '[\s\r\n]*;?)/si', $fc, $match);
                                $match[2] = trim($match[2]);
                                $match[4] = trim($match[4]);
                                if (!empty($match[2]) || !empty($match[4])) {
                                    $fc = str_replace($match[0], $match[1] . $match[2] . $match[4] . $match[5], $fc);
                                } else {
                                    $fc = str_replace($match[0], '', $fc);
                                }
                                $fc = preg_replace('/<\?php[\s\r\n]*\?\>/si', '', $fc);
                            }
                            Console::newLine();
                            Console::display(Console::title(' SANITIZED ', '='), 'black', 'green');
                            Console::newLine(2);
                            Console::code($fc);
                            Console::newLine(2);
                            Console::display(Console::title('', '='), 'black', 'green');
                            Console::newLine(2);
                            Console::displayLine('File sanitized, now you must verify if has been fixed correctly.', 2, 'yellow');
                            $confirm2 = Console::read('Confirm and save [y|N]? ', 'purple');
                            Console::newLine();
                            if ($confirm2 == 'y') {
                                Console::writeLine("File '$_FILE_PATH' sanitized!", 2, 'green');
                                file_put_contents($_FILE_PATH, $fc);
                                self::$summaryRemoved[] = $_FILE_PATH;
                                $_WHILE = false;
                            } else {
                                self::$summaryIgnored[] = $_FILE_PATH;
                            }
                        } elseif (in_array($confirmation, array('4')) && count($pattern_found) > 0) {
                            // Remove evil line code
                            $fc_expl = explode(PHP_EOL, $fc);
                            foreach ($pattern_found as $pattern) {
                                unset($fc_expl[intval($pattern['line']) - 1]);
                            }
                            $fc = implode(PHP_EOL, $fc_expl);

                            Console::newLine();
                            Console::display(Console::title(' SANITIZED ', '='), 'black', 'green');
                            Console::newLine(2);
                            Console::code($fc);
                            Console::newLine(2);
                            Console::display(Console::title('', '='), 'black', 'green');
                            Console::newLine(2);
                            Console::displayLine('File sanitized, now you must verify if has been fixed correctly.', 2, 'yellow');
                            $confirm2 = Console::read('Confirm and save [y|N]? ', 'purple');
                            Console::newLine();
                            if ($confirm2 == 'y') {
                                Console::writeLine("File '$_FILE_PATH' sanitized!", 2, 'green');
                                file_put_contents($_FILE_PATH, $fc);
                                self::$summaryRemoved[] = $_FILE_PATH;
                                $_WHILE = false;
                            } else {
                                self::$summaryIgnored[] = $_FILE_PATH;
                            }
                        } elseif (in_array($confirmation, array('5'))) {
                            // Edit with vim
                            $descriptors = array(
                                array('file', '/dev/tty', 'r'),
                                array('file', '/dev/tty', 'w'),
                                array('file', '/dev/tty', 'w'),
                            );
                            $process = proc_open("vim '$_FILE_PATH'", $descriptors, $pipes);
                            while (true) {
                                $proc_status = proc_get_status($process);
                                if ($proc_status['running'] == false) {
                                    break;
                                }
                            }
                            self::$summaryEdited[] = $_FILE_PATH;
                            Console::writeLine("File '$_FILE_PATH' edited with vim!", 2, 'green');
                            self::$summaryRemoved[] = $_FILE_PATH;
                        } elseif (in_array($confirmation, array('6'))) {
                            // Edit with nano
                            $descriptors = array(
                                array('file', '/dev/tty', 'r'),
                                array('file', '/dev/tty', 'w'),
                                array('file', '/dev/tty', 'w'),
                            );
                            $process = proc_open("nano -c '$_FILE_PATH'", $descriptors, $pipes);
                            while (true) {
                                $proc_status = proc_get_status($process);
                                if ($proc_status['running'] == false) {
                                    break;
                                }
                            }
                            $summary_edited[] = $_FILE_PATH;
                            Console::writeLine("File '$_FILE_PATH' edited with nano!", 2, 'green');
                            self::$summaryRemoved[] = $_FILE_PATH;
                        } elseif (in_array($confirmation, array('7'))) {
                            // Add to whitelist
                            foreach ($pattern_found as $key => $pattern) {
                                //$exploit           = preg_replace("/^(\S+) \[line [0-9]+\].*/si", "$1", $key);
                                //$lineNumber        = preg_replace("/^\S+ \[line ([0-9]+)\].*/si", "$1", $key);
                                $exploit = $pattern['key'];
                                $lineNumber = $pattern['line'];
                                self::$whitelist[] = array(str_replace(self::$pathScan, '', $_FILE_PATH), $exploit, $lineNumber);
                            }
                            self::$whitelist = array_map('unserialize', array_unique(array_map('serialize', self::$whitelist)));

                            // TODO: from char to length
                            if (CSV::write(self::$pathWhitelist, self::$whitelist)) {
                                self::$summaryWhitelist[] = $_FILE_PATH;
                                Console::writeLine("Exploits of file '$_FILE_PATH' added to whitelist!", 2, 'green');
                                $_WHILE = false;
                            } else {
                                Console::writeLine("Exploits of file '$_FILE_PATH' failed adding file to whitelist! Check write permission of '" . self::$pathWhitelist . "' file!", 2, 'red');
                            }
                        } elseif (in_array($confirmation, array('8'))) {
                            // Show source code
                            Console::newLine();
                            Console::displayLine("$_FILE_PATH", 2, 'yellow');
                            Console::display(Console::title(' SOURCE ', '='), 'white', 'red');
                            Console::newLine(2);
                            Console::code($fc, $pattern_found);
                            Console::newLine(2);
                            Console::display(Console::title('', '='), 'white', 'red');
                            Console::newLine(2);
                        } else {
                            // None
                            Console::writeLine("File '$_FILE_PATH' skipped!", 2, 'green');
                            self::$summaryIgnored[] = $_FILE_PATH;
                            $_WHILE = false;
                        }

                        Console::writeBreak();
                    }
                    unset($fc);
                }
            }
        }
    }

    /**
     * Print summary.
     */
    private function summary()
    {
        // Statistics
        Console::displayTitle('SUMMARY', 'black', 'cyan');
        Console::writeBreak();
        Console::writeLine('Files scanned: ' . self::$summaryScanned);
        if (!self::$settings['report']) {
            self::$summaryIgnored = array_unique(self::$summaryIgnored);
            self::$summaryEdited = array_unique(self::$summaryEdited);
            Console::writeLine('Files edited: ' . count(self::$summaryEdited));
            Console::writeLine('Files quarantined: ' . count(self::$summaryQuarantine));
            Console::writeLine('Files whitelisted: ' . count(self::$summaryWhitelist));
            Console::writeLine('Files ignored: ' . count(self::$summaryIgnored), 2);
        }
        Console::writeLine('Malware detected: ' . self::$summaryDetected);
        if (!self::$settings['report']) {
            Console::writeLine('Malware removed: ' . count(self::$summaryRemoved));
        }

        if (self::$settings['report']) {
            Console::writeLine(Console::eol(1) . "Files infected: '" . self::$pathLogsInfected . "'", 1, 'red');
            file_put_contents(self::$pathLogsInfected, 'Log date: ' . date('d-m-Y H:i:s') . Console::eol(1) . implode(Console::eol(2), self::$summaryIgnored));
            Console::writeBreak(2);
        } else {
            if (count(self::$summaryRemoved) > 0) {
                Console::writeBreak();
                Console::writeLine('Files removed:', 1, 'red');
                foreach (self::$summaryRemoved as $un) {
                    Console::writeLine($un);
                }
            }
            if (count(self::$summaryEdited) > 0) {
                Console::writeBreak();
                Console::writeLine('Files edited:', 1, 'green');
                foreach (self::$summaryEdited as $un) {
                    Console::writeLine($un);
                }
            }
            if (count(self::$summaryQuarantine) > 0) {
                Console::writeBreak();
                Console::writeLine('Files quarantined:', 1, 'yellow');
                foreach (self::$summaryIgnored as $un) {
                    Console::writeLine($un);
                }
            }
            if (count(self::$summaryWhitelist) > 0) {
                Console::writeBreak();
                Console::writeLine('Files whitelisted:', 1, 'cyan');
                foreach (self::$summaryWhitelist as $un) {
                    Console::writeLine($un);
                }
            }
            if (count(self::$summaryIgnored) > 0) {
                Console::writeBreak();
                Console::writeLine('Files ignored:', 1, 'cyan');
                foreach (self::$summaryIgnored as $un) {
                    Console::writeLine($un);
                }
            }
            Console::writeBreak(2);
        }
    }

    /**
     * Update index.php to last version.
     */
    public static function update()
    {
        Console::writeLine('Checking update...');
        $version = file_get_contents('https://raw.githubusercontent.com/marcocesarato/PHP-Antimalware-Scanner/master/dist/version');
        if (!empty($version)) {
            if (version_compare(self::$version, $version, '<')) {
                Console::write('New version');
                Console::write(' ' . $version . ' ');
                Console::writeLine('of the index.php available!', 2);
                $confirm = Console::read('You sure you want update the index.php to the last version [y|N]? ', 'purple');
                Console::writeBreak();
                if (strtolower($confirm) === 'y') {
                    $new_version = file_get_contents('https://raw.githubusercontent.com/marcocesarato/PHP-Antimalware-Scanner/master/dist/scanner');
                    file_put_contents(__FILE__, $new_version);
                    Console::write('Updated to last version');
                    Console::write(' (' . self::$version . ' => ' . $version . ') ');
                    Console::writeLine('with SUCCESS!', 2);
                } else {
                    Console::writeLine('Updated SKIPPED!', 2);
                }
            } else {
                Console::writeLine('You have the last version of the index.php yet!', 2);
            }
        } else {
            Console::writeLine('Update FAILED!', 2, 'red');
        }
        die();
    }

    /**
     * Convert to Bytes.
     *
     * @param string $from
     *
     * @return int|null
     */
    private function convertToBytes($from)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        $number = substr($from, 0, -2);
        $suffix = strtoupper(substr($from, -2));

        if (is_numeric($suffix[0])) {
            return preg_replace('/[^\d]/', '', $from);
        }
        $pow = array_flip($units)[$suffix] ?: null;
        if ($pow === null) {
            return null;
        }

        return $number * pow(1024, $pow);
    }

    /**
     * Return real current path.
     *
     * @return string|string[]|null
     */
    public static function currentDirectory()
    {
        if (method_exists('Phar', 'running')) {
            return dirname(Phar::running(false));
        }
        $string = __DIR__;
        $string = pathinfo($string);
        $dir = parse_url($string['dirname']);

        return $dir['host'] . ':/' . $dir['path'];
    }
}
