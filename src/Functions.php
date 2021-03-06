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

class Functions
{
    /**
     * Default functions definitions.
     *
     * @var array
     */
    public static $default = [
        'il_exec',
        'shell_exec',
        'eval',
        'system',
        'create_function',
        'exec',
        'assert',
        'syslog',
        'passthru',
        'define_syslog_variables',
        'posix_kill',
        'posix_getpwuid',
        'posix_mkfifo',
        'posix_setpgid',
        'posix_setsid',
        'posix_setuid',
        'posix_uname',
        'proc_close',
        'proc_get_status',
        'proc_nice',
        'proc_open',
        'proc_terminate',
        'pcntl_exec',
        'pcntl_fork',
        'inject_code',
        'apache_child_terminate',
        'apache_note',
        'define_syslog_variables',
    ];

    /**
     * Dangerous encoded functions definitions.
     *
     * @var array
     */
    public static $dangerous = [
        // PHP Code Execution
        'il_exec',
        'shell_exec',
        'eval',
        'system',
        'create_function',
        'exec',
        'pcntl_exec',
        'pcntl_fork',
        'assert',
        'passthru',
        'create_function',
        'include',
        'include_once',
        'require',
        'require_once',
        'preg_replace',
        // Files and configurations
        'syslog',
        'define_syslog_variables',
        'debugger_off',
        'get_meta_tags',
        'highlight_file',
        'debugger_on',
        'parse_ini_file',
        'php_strip_whitespace',
        'show_source',
        'symlink',
        'fopen',
        'file_get_contents',
        'file_put_contents',
        'chmod',
        'chown',
        'copy',
        'move',
        'is_file',
        'is_dir',
        'ini_alter',
        'ini_get_all',
        'ini_restore',
        'parse_ini_file',
        'inject_code',
        'apache_child_terminate',
        'apache_setenv',
        'apache_note',
        'define_syslog_variables',
        // Curl
        'curl_init',
        'curl_setopt',
        'curl_exec',
        // Posix
        'posix_kill',
        'posix_getpwuid',
        'posix_mkfifo',
        'posix_setpgid',
        'posix_setsid',
        'posix_setuid',
        'posix_uname',
        // Processes
        'popen',
        'proc_close',
        'proc_get_status',
        'proc_nice',
        'proc_open',
        'proc_terminate',
        // Encoding
        'escapeshellarg',
        'escapeshellcmd',
        'base64_decode',
        'urldecode',
        'rawurldecode',
        'str_rot13',
        'preg_replace',
        // Information Disclosure
        'phpinfo',
        'posix_mkfifo',
        'posix_getlogin',
        'posix_ttyname',
        'getenv',
        'get_current_user',
        'proc_get_status',
        'get_cfg_var',
        'disk_free_space',
        'disk_total_space',
        'diskfreespace',
        'getcwd',
        'getlastmo',
        'getmygid',
        'getmyinode',
        'getmypid',
        'getmyuid',
        // Callback functions
        'ob_start',
        'array_diff_uassoc',
        'array_diff_ukey',
        'array_filter',
        'array_intersect_uassoc',
        'array_intersect_ukey',
        'array_map',
        'array_reduce',
        'array_udiff_assoc',
        'array_udiff_uassoc',
        'array_udiff',
        'array_uintersect_assoc',
        'array_uintersect_uassoc',
        'array_uintersect',
        'array_walk_recursive',
        'array_walk',
        'assert_options',
        'uasort',
        'uksort',
        'usort',
        'preg_replace_callback',
        'spl_autoload_register',
        'iterator_apply',
        'call_user_func',
        'call_user_func_array',
        'register_shutdown_function',
        'register_tick_function',
        'set_error_handler',
        'set_exception_handler',
        'session_set_save_handler',
        'sqlite_create_aggregate',
        'sqlite_create_function',
        'win32_create_service',
        'mb_ereg_replace_callback',
        'shmop_open',
        'suhosin.executor.func.blacklist',
        'stream_socket_pair',
    ];

    /**
     * Get all default functions to check.
     *
     * @return array
     */
    public static function getDefault()
    {
        return self::$default;
    }

    /**
     * Get all dangerous functions to check.
     *
     * @return array
     */
    public static function getDangerous()
    {
        return self::$dangerous;
    }
}
