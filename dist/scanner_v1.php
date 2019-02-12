#!/usr/bin/php
<?php

/**
 * Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 * @version 0.3.15.32
 */

$isCLI = (php_sapi_name() == 'cli');
if(!$isCLI) {
	die("This file must run from a console session.");
}

// Settings
set_time_limit(- 1);
ini_set("memory_limit", - 1);

// Variables
define("__NAME__", "amwscan");
define("__VERSION__", "0.3.15.32");
define("__ROOT__", dirname(__FILE__));
define("__PATH_QUARANTINE__", __ROOT__ . "/quarantine");
define("__PATH_LOGS__", __ROOT__ . "/scanner.log");
define("__PATH_WHITELIST__", __ROOT__ . "/scanner_whitelist.csv");
define("__PATH_LOGS_INFECTED__", __ROOT__ . "/scanner_infected.log");

// Newlines
define("PHP_EOL2", PHP_EOL . PHP_EOL);
define("PHP_EOL3", PHP_EOL2 . PHP_EOL);

// Errors
error_reporting(0);
ini_set('display_errors', 0);

/*error_reporting(E_ALL);
ini_set('display_errors', 1);*/

// Scan
$_SCAN_PATH         = __ROOT__;
$_SCAN_EXTENSIONS   = array('php', 'php3', 'ph3', 'php4', 'ph4', 'php5', 'ph5', 'php7', 'ph7', 'phtm', 'phtml', 'ico');

// Summaries
$summary_scanned    = 0;
$summary_detected   = 0;
$summary_removed    = array();
$summary_ignored    = array();
$summary_edited     = array();
$summary_quarantine = array();
$summary_whitelist  = array();

// Definitions
$_FUNCTIONS        = array();
$_EXPLOITS         = array();

// Default exploits definitions
$_DEFAULT_EXPLOITS = array(
	"eval_chr"                => '/chr[\s\r\n]*\([\s\r\n]*101[\s\r\n]*\)[\s\r\n]*\.[\s\r\n]*chr[\s\r\n]*\([\s\r\n]*118[\s\r\n]*\)[\s\r\n]*\.[\s\r\n]*chr[\s\r\n]*\([\s\r\n]*97[\s\r\n]*\)[\s\r\n]*\.[\s\r\n]*chr[\s\r\n]*\([\s\r\n]*108[\s\r\n]*\)/i',
	"eval_preg"               => '/(preg_replace(_callback)?|mb_ereg_replace|preg_filter)\s*\(.+(\/|\\\\x2f)(e|\\\\x65)[\\\'\"].*?(?=\))\)/i',
	"eval_base64"             => '/eval[\s\r\n]*\([\s\r\n]*base64_decode[\s\r\n]*\((?<=\().*?(?=\))\)/i',
	"eval_comment"            => '/(eval|preg_replace|system|assert|passthru|(pcntl_)?exec|shell_exec|call_user_func(_array)?)\/\*[^\*]*\*\/\((?<=\().*?(?=\))\)/',
	"align"                   => '/(\$\w+=[^;]*)*;\$\w+=@?\$\w+\((?<=\().*?(?=\))\)/si',
	"b374k"                   => '/(\\\'|\")ev(\\\'|\")\.(\\\'|\")al(\\\'|\")\.(\\\'|\")\(\"\?>/i', // b374k shell
	"weevely3"                => '/\$\w=\$[a-zA-Z]\(\'\',\$\w\);\$\w\(\);/i', // weevely3 launcher
	"c99_launcher"            => '/;\$\w+\(\$\w+(,\s?\$\w+)+\);/i',	// http://bartblaze.blogspot.fr/2015/03/c99shell-not-dead.html
	"too_many_chr"            => '/(chr\([\d]+\)\.){8}/i', // concatenation of more than eight `chr()`
	"concat"                  => '/(\$[\w\[\]\\\'\"]+\\.[\n\r]*){10}/i', // concatenation of vars array
	"concat_vars_with_spaces" => '/(\$([a-zA-Z0-9]+)[\s\r\n]*\.[\s\r\n]*){6}/',  // concatenation of more than 6 words, with spaces
	"concat_vars_array"       => '/(\$([a-zA-Z0-9]+)(\{|\[)([0-9]+)(\}|\])[\s\r\n]*\.[\s\r\n]*){6}.*?(?=\})\}/i', // concatenation of more than 6 words, with spaces
	"var_as_func"             => '/\$_(GET|POST|COOKIE|REQUEST|SERVER)[\s\r\n]*\[[^\]]+\][\s\r\n]*\((?<=\().*?(?=\))\)/i',
	"global_var_string"       => '/\$\{[\s\r\n]*(\\\'|\")_(GET|POST|COOKIE|REQUEST|SERVER)(\\\'|\")[\s\r\n]*\}/i',
	"extract_global"          => '/extract\([\s\r\n]*\$_(GET|POST|COOKIE|REQUEST|SERVER).*?(?=\))\)/i',
	"escaped_path"            => '/(\\x[0-9abcdef]{2}[a-z0-9.-\/]{1,4}){4,}/i',
	"include_icon"            => '/include\(?[\s\r\n]*(\"|\\\')(.*?)(\.|\\056\\046\\2E)(\i|\\\\151|\\x69|\\105)(c|\\143\\099\\x63)(o|\\157\\111|\\x6f)(\"|\\\')\)?/mi', // Icon inclusion
	"backdoor_code"           => '/eva1fYlbakBcVSir/i',
	"infected_comment"        => '/\/\*[a-z0-9]{5}\*\//i', // usually used to detect if a file is infected yet
	"hex_char"                => '/\\[Xx](5[Ff])/i',
	"hacked_by"               => '/hacked[\s\r\n]*by/i',
	"killall"                 => '/killall[\s\r\n]*\-9/i',
	"download_remote_code"    => '/echo\s+file_get_contents[\s\r\n]*\([\s\r\n]*base64_url_decode[\s\r\n]*\([\s\r\n]*@*\$_(GET|POST|SERVER|COOKIE|REQUEST).*?(?=\))\)/i',
	"globals_concat"          => '/\$GLOBALS\[[\s\r\n]*\$GLOBALS[\\\'[a-z0-9]{4,}\\\'\]/i',
	"globals_assign"          => '/\$GLOBALS\[\\\'[a-z0-9]{5,}\\\'\][\s\r\n]*=[\s\r\n]*\$[a-z]+\d+\[\d+\]\.\$[a-z]+\d+\[\d+\]\.\$[a-z]+\d+\[\d+\]\.\$[a-z]+\d+\[\d+\]\./i',
	/*"php_long"                => '/^.*<\?php.{800,}\?>.*$/i',*/
	//"base64_long"             => '/[\\\'\"][A-Za-z0-9+\/]{260,}={0,3}[\\\'\"]/',
	"clever_include"          => '/include[\s\r\n]*\([\s\r\n]*[^\.]+\.(png|jpe?g|gif|bmp|ico).*?(?=\))\)/i',
	"basedir_bypass"          => '/curl_init[\s\r\n]*\([\s\r\n]*[\"\\\']file:\/\/.*?(?=\))\)/i',
	"basedir_bypass2"         => '/file\:file\:\/\//i', // https://www.intelligentexploit.com/view-details.html?id=8719
	"non_printable"           => '/(function|return|base64_decode).{,256}[^\\x00-\\x1F\\x7F-\\xFF]{3}/i',
	"double_var"              => '/\${[\s\r\n]*\${.*?}(.*)?}/i',
	"double_var2"             => '/\${\$[0-9a-zA-z]+}/i',
	"global_save"             => '/\[\s\r\n]*=[\s\r\n]*\$GLOBALS[\s\r\n]*\;[\s\r\n]*\$[\s\r\n]*\{/i',
	"hex_var"                 => '/\$\{[\s\r\n]*(\\\'|\")\\\\x.*?(?=\})\}/i', // check for ${"\xFF"}, IonCube use this method ${"\x
	"register_function"       => '/register_[a-z]+_function[\s\r\n]*\([\s\r\n]*[\\\'\"][\s\r\n]*(eval|assert|passthru|exec|include|system|shell_exec|`).*?(?=\))\)/i', // https://github.com/nbs-system/php-malware-finder/issues/41
	"safemode_bypass"         => '/\x00\/\.\.\/|LD_PRELOAD/i',
	"ioncube_loader"          => '/IonCube\_loader/i',
	"nano"                    => '/\$[a-z0-9-_]+\[[^]]+\]\((?<=\().*?(?=\))\)/', //https://github.com/UltimateHackers/nano
	"ninja"                   => '/base64_decode[^;]+getallheaders/',
	"execution"               => '/\b(eval|assert|passthru|exec|include|system|pcntl_exec|shell_exec|base64_decode|`|array_map|ob_start|call_user_func(_array)?)\s*\(\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/', // function that takes a callback as 1st parameter
	"execution2"              => '/\b(array_filter|array_reduce|array_walk(_recursive)?|array_walk|assert_options|uasort|uksort|usort|preg_replace_callback|iterator_apply)\s*\(\s*[^,]+,\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/',  // functions that takes a callback as 2nd parameter
	"execution3"              => '/\b(array_(diff|intersect)_u(key|assoc)|array_udiff)\s*\(\s*([^,]+\s*,?)+\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER))\s*\[[^]]+\]\s*\)+\s*;/',  // functions that takes a callback as 2nd parameter
	"shellshock"              => "/\(\)\s*{\s*[a-z:]\s*;\s*}\s*;/",
	"silenced_eval"           => '/@eval\s*\((?<=\().*?(?=\))\)/',
	"various"                 => '/\<\!\-\-\#exec\s*cmd\=/i',  //http://www.w3.org/Jigsaw/Doc/User/SSI.html#exec
	"htaccess_handler"        => '/SetHandler[\s\r\n]*application\/x\-httpd\-php/i',
	"htaccess_type"           => '/AddType\s+application\/x-httpd-(php|cgi)/i',
	"file_prepend"            => '/php_value\s*auto_prepend_file/i',
	"iis_com"                 => '/IIS\:\/\/localhost\/w3svc/i',
	"reversed"                => '/(noitcnuf\_etaerc|metsys|urhtssap|edulcni|etucexe\_llehs|ecalper\_rts|ecalper_rts)/i',
	"rawurlendcode_rot13"     => '/rawurldecode[\s\r\n]*\(str_rot13[\s\r\n]*\((?<=\().*?(?=\))\)/i',
	"serialize_phpversion"    => '/\@serialize[\s\r\n]*\([\s\r\n]*(Array\(|\[)(\\\'|\")php(\\\'|\")[\s\r\n]*\=\>[\s\r\n]*\@phpversion[\s\r\n]*\((?<=\().*?(?=\))\)/si',
	//"disable_magic_quotes"    => '/set_magic_quotes_runtime\s*\(\s*0\s*\)/',
	"md5_create_function"     => '/\$md5\s*=\s*.*create_function\s*\(.*?\);\s*\$.*?\)\s*;/si',
	"god_mode"                => '/\/\*god_mode_on\*\/eval\(base64_decode\([\"\\\'][^\"\\\']{255,}[\"\\\']\)\);\s*\/\*god_mode_off\*\//si',
	"wordpress_filter"        => '/\$md5\s*=\s*[\"|\\\']\w+[\"|\\\'];\s*\$wp_salt\s*=\s*[\w\(\),\"\\\'\;$]+\s*\$wp_add_filter\s*=\s*create_function\(.*?\);\s*\$wp_add_filter\(.*?\);/si',
	"password_protection_md5" => '/md5\s*\(\s*\$_(GET|REQUEST|POST|COOKIE|SERVER)[^)]+\)\s*===?\s*[\\\'\"][0-9a-f]{32}[\\\'\"]/si',
	"password_protection_sha" => '/sha1\s*\(\s*\$_(GET|REQUEST|POST|COOKIE|SERVER)[^)]+\)\s*===?\s*[\\\'\"][0-9a-f]{40}[\\\'\"]/si',
);

// Default functions definitions
$_DEFAULT_FUNCTIONS = array(
	"il_exec",
	"shell_exec",
	"eval",
	//"system",
	"create_function",
	/*"str_rot13",
	"exec",
	"assert",
	"syslog",
	"passthru",
	"dl",
	"define_syslog_variables",
	"debugger_off",
	"debugger_on",
	"stream_select",
	"parse_ini_file",
	"show_source",
	"symlink",
	"popen",*/
	"posix_kill",/*
    "posix_getpwuid",
    "posix_mkfifo",
    "posix_setpgid",
    "posix_setsid",
    "posix_setuid",
    "posix_uname",*/
	"proc_close",
	"proc_get_status",
	"proc_nice",
	"proc_open",/*
    "proc_terminate",
    "ini_alter",
    "ini_get_all",
    "ini_restore",
    "parse_ini_file",*/
	"inject_code",
	"apache_child_terminate",
	//"apache_setenv",
	"apache_note",
	"define_syslog_variables",/*
    "escapeshellarg",
    "escapeshellcmd",
    "ob_start",*/
);

// Classes

/**
 * Class Console
 * Console manager
 */
class Console {

	/**
	 * Font colors
	 * @var array
	 */
	public static $foreground_colors = array(
		'black'        => '0;30',
		'dark_gray'    => '1;30',
		'blue'         => '0;34',
		'light_blue'   => '1;34',
		'green'        => '0;32',
		'light_green'  => '1;32',
		'cyan'         => '0;36',
		'light_cyan'   => '1;36',
		'red'          => '0;31',
		'light_red'    => '1;31',
		'purple'       => '0;35',
		'light_purple' => '1;35',
		'brown'        => '0;33',
		'yellow'       => '1;33',
		'light_gray'   => '0;37',
		'white'        => '1;37',
	);

	/**
	 * Background colors
	 * @var array
	 */
	public static $background_colors = array(
		'black'      => '40',
		'red'        => '41',
		'green'      => '42',
		'yellow'     => '43',
		'blue'       => '44',
		'magenta'    => '45',
		'cyan'       => '46',
		'light_gray' => '47',
	);

	/**
	 * Print progress
	 * @param $done
	 * @param $total
	 * @param int $size
	 */
	public static function progress($done, $total, $size = 30) {
		static $start_time;
		if($done > $total) {
			return;
		}
		if(empty($start_time)) {
			$start_time = time();
		}
		$now        = time();
		$perc       = (double) ($done / $total);
		$bar        = floor($perc * $size);
		$status_bar = "\r[";
		$status_bar .= str_repeat("=", $bar);
		if($bar < $size) {
			$status_bar .= ">";
			$status_bar .= str_repeat(" ", $size - $bar);
		} else {
			$status_bar .= "=";
		}
		$disp       = number_format($perc * 100, 0);
		$status_bar .= "] $disp%";
		$rate       = ($now - $start_time) / $done;
		$left       = $total - $done;

		$eta        = round($rate * $left, 2);
		$eta_type = "sec.";
		$elapsed    = $now - $start_time;
		$elapsed_type = "sec.";

		if($eta > 59){
		    $eta_type = "min.";
			$eta = round($eta / 60);
        }

		if($elapsed > 59){
			$elapsed_type = "min.";
			$elapsed = round($elapsed / 60);
		}

		self::display("$status_bar ", "black", "green");
		self::display(" ");
		self::display("$done/$total", "green");
		self::display(" remaining: " . number_format($eta) . " ". $eta_type ."  elapsed: " . number_format($elapsed) . " " . $elapsed_type);
		flush();
		if($done == $total) {
			self::display(PHP_EOL);
		}
	}

	/**
	 * Print message without writing logs
	 * @param $string
	 * @param string $foreground_color
	 * @param null $background_color
	 */
	public static function display($string, $foreground_color = "white", $background_color = null, $escape = true) {
		self::write($string, $foreground_color, $background_color, false, $escape);
	}

	/**
	 * Print message
	 * @param $string
	 * @param string $foreground_color
	 * @param null $background_color
	 * @param null $log
	 */
	public static function write($string, $foreground_color = "white", $background_color = null, $log = null, $escape = true) {
		$return_string = $string;
		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$foreground_color = null;
			$background_color = null;
		}
		if(isset($_REQUEST['log']) && $log === null) {
			$log = true;
		}
		if($escape) {
			$return_string = self::escape($return_string);
		}
		$colored_string = "";
		if(isset(self::$foreground_colors[$foreground_color])) {
			$colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
		}
		if(isset(self::$background_colors[$background_color])) {
			$colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
		}
		$colored_string .= $return_string . "\033[0m";

		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			echo $return_string;
		} else {
			echo $colored_string;
		}

		if($log) {
			self::log($string);
		}
	}

	/**
	 * Read input
	 * @param $string
	 * @param string $foreground_color
	 * @param null $background_color
	 * @return string
	 */
	public static function read($string, $foreground_color = "white", $background_color = null) {
		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$foreground_color = null;
			$background_color = null;
		}
		$colored_string = "";
		if(isset(self::$foreground_colors[$foreground_color])) {
			$colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
		}
		if(isset(self::$background_colors[$background_color])) {
			$colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
		}
		$colored_string .= $string . "\033[0m";

		$read = null;

        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo __NAME__ . " > " . trim($string) . " ";
            $read = trim(stream_get_line(STDIN, 1024, PHP_EOL));
        } else {
            $read = readline(__NAME__ . " > " . trim($colored_string) . " ");
        }

		return $read;
	}

	/**
	 * Print code
	 * @param $string
	 * @param null $log
	 */
    public static function code($string, $errors, $log = null) {
		if(isset($_REQUEST['log']) && $log === null) {
			$log = true;
		}
		$code = $string;
		if(count($errors) > 0) {
			foreach($errors as $pattern) {
				preg_match($pattern, $code, $string_match);
				$escaped = self::escape($string_match[0]);
				$code = str_replace($string_match[0],  "\033[" . self::$foreground_colors['red'] . "m" . $escaped . "\033[" . self::$foreground_colors['white'] . "m", $code);
			}
		}
		$lines = explode("\n", $code);
        for($i = 0; $i < count($lines); $i ++) {
            if($i != 0) {
                self::display(PHP_EOL);
            }
            self::display("  " . str_pad((string) ($i + 1), strlen((string) count($lines)), " ", STR_PAD_LEFT) . ' | ', 'yellow');
            self::display($lines[$i], 'white', null, false);
        }
		if($log) {
			self::log($string);
		}
	}

	/**
	 * Write logs
	 * @param $string
	 */
	public static function log($string) {
		file_put_contents(__PATH_LOGS__, $string, FILE_APPEND);
	}

	/**
     * Escape colors string
	 * @param $string
	 * @return string
	 */
	public static function escape($string) {
	    return mb_convert_encoding(preg_replace('/(e|\x1B|[[:cntrl:]]|\033)\[([0-9]{1,2}(;[0-9]{1,2})?)?[mGKc]/','',$string), "utf-8", "auto");
    }

	/**
	 * Print Helper
	 */
	public static function helper() {
	    $exploit_list = implode(PHP_EOL.'- ', array_keys($GLOBALS["_EXPLOITS"]));
		$functions_list = implode(PHP_EOL.'- ', $GLOBALS["_DEFAULT_FUNCTIONS"]);
		Console::display("                                                               ", 'black', 'cyan');
		Console::display(PHP_EOL);
		Console::display("                             HELP                              ", 'black', 'cyan');
		Console::display(PHP_EOL);
		Console::display("                                                               ", 'black', 'cyan');
		Console::display(PHP_EOL);
		$help = <<<EOD

Exploits: 
- $exploit_list
    
Functions: 
- $functions_list

Arguments:
<path>                       Define the path to scan (default: current directory)

Flags:
-a   --agile                 Help to have less false positive on WordPress and others platforms
                             enabling exploits mode and removing some common exploit pattern
                             but this method could not find some malware
-e   --only-exploits         Check only exploits and not the functions
                             -- Recommended for WordPress or others platforms
-f   --only-functions        Check only functions and not the exploits
-h   --help                  Show the available flags and arguments
-l   --log                   Write a log file 'scanner.log' with all the operations done
-s   --scan                  Scan only mode without check and remove malware. It also write
                             all malware paths found to 'scanner_infected.log' file
                             
     --exploits="..."        Filter exploits
     --functions="..."       Define functions to search
     --whitelist-only-path   Check on whitelist only file path and not line number
     
Notes: For open files with nano or vim run the scripts with "-d disable_functions=''"
       examples: php -d disable_functions='' scanner ./mywebsite/http/ --log --agile --only-exploits
                 php -d disable_functions='' scanner --agile --only-exploits
                 php -d disable_functions='' scanner --exploits="double_var2" --functions="eval, str_replace"
EOD;
		self::display($help . PHP_EOL2 . $GLOBALS['_ARGV']->usage() . PHP_EOL2);
		die();
	}
}

/**
 * Class Flag
 */
class Flag {
	public
		$name,
		$callback,
		$aliases = array(),
		$hasValue = false,
		$defaultValue,
		$var,
		$help;

	function __construct($name, $options = array(), $callback = null) {
		$this->name         = $name;
		$this->callback     = $callback;
		$this->aliases      = array_merge(array("--$name"), (array) @$options["alias"]);
		$this->defaultValue = @$options["default"];
		$this->hasValue     = (bool) @$options["has_value"];
		$this->help         = @$options["help"];
		if(array_key_exists("var", $options)) {
			$this->var =& $options["var"];
		}
	}

	function __toString() {
		$s = join('|', $this->aliases);
		if($this->hasValue) {
			$s = "$s <{$this->name}>";
		}

		return "[$s]";
	}
}

/**
 * Class Argument
 */
class Argument {
	public
		$name,
		$vararg = false,
		$required = false,
		$defaultValue,
		$help;

	function __construct($name, $options = array()) {
		$this->name         = $name;
		$this->vararg       = (bool) @$options["var_arg"];
		$this->required     = (bool) @$options["required"];
		$this->defaultValue = @$options["default"];
		$this->help         = @$options["help"];
	}

	function __toString() {
		$arg = "<{$this->name}>";
		if($this->vararg) {
			$arg = "$arg ...";
		}
		if(!$this->required) {
			return "[$arg]";
		}

		return $arg;
	}
}

/**
 * Class Argv
 */
class Argv implements \ArrayAccess {
	protected $name,
		$description,
		$examples = array(),
		$flags = array(),
		$args = array(),
		$parsedFlags = array(),
		$parsedNamedArgs = array(),
		$parsedArgs = array();

	/**
	 * Build
	 * @param $callback
	 * @return static
	 */
	static function build($callback) {
		$parser = new static;
		if($callback instanceof \Closure and is_callable(array($callback, "bindTo"))) {
			$callback = $callback->bindTo($parser);
		}
		call_user_func($callback, $parser);

		return $parser;
	}

	/**
	 * Argv constructor.
	 * @param string $description
	 * @param null $name
	 * @param array $examples
	 */
	function __construct($description = '', $name = null, $examples = array()) {
		$this->description = $description;
		$this->name        = $name;
		$this->examples    = $examples;
	}

	/**
	 * Parse argvs
	 * @param null $args
	 */
	function parse() {

		$args = array_slice($_SERVER['argv'], 1); // First argument removed (php [scanner.php] [<path>] [<functions>])

		foreach($args as $pos => $arg) {
			// reset value
			$value = null;
			if(substr($arg, 0, 1) === '-') {
				if(preg_match('/^(.+)=(?:\"|\\\')?(.+)(?:\"|\\\')?/', $arg, $matches)) {
					$arg   = $matches[1];
					$value = $matches[2];
				}
				if(!$flag = @$this->flags[$arg]) {
					return;
				}
				unset($args[$pos]);
				if($flag->hasValue) {
					if(!isset($value)) {
						$value = $args[$pos + 1];
						unset($args[$pos + 1]);
					}
				} else {
					$value = true;
				}
				if(null !== $flag->callback) {
					call_user_func_array($flag->callback, array(&$value));
				}
				// Set the reference given as the flag's 'var'.
				$flag->var = $this->parsedFlags[$flag->name] = $value;
			}
		}
		foreach($this->flags as $flag) {
			if(!array_key_exists($flag->name, $this->parsedFlags)) {
				$flag->var = $this->parsedFlags[$flag->name] = $flag->defaultValue;
			}
		}
		$this->parsedArgs = $args = array_values($args);
		$pos              = 0;
		foreach($this->args as $arg) {
			if($arg->required and !isset($args[$pos])) {
				return;
			}
			if(isset($args[$pos])) {
				if($arg->vararg) {
					$value = array_slice($args, $pos);
					$pos   += count($value);
				} else {
					$value = $args[$pos];
					$pos ++;
				}
			} else {
				$value = $arg->defaultValue;
			}
			$this->parsedNamedArgs[$arg->name] = $value;
		}
	}

	/**
	 * Add Flag
	 * @param $name
	 * @param array $options
	 * @param null $callback
	 * @return $this
	 */
	function addFlag($name, $options = array(), $callback = null) {
		$flag = new Flag($name, $options, $callback);
		foreach($flag->aliases as $alias) {
			$this->flags[$alias] = $flag;
		}

		return $this;
	}

	/**
	 * Add flag var
	 * @param $name
	 * @param $var
	 * @param array $options
	 * @return Argv
	 */
	function addFlagVar($name, &$var, $options = array()) {
		$options["var"] =& $var;

		return $this->addFlag($name, $options);
	}

	/**
	 * Add Argument
	 * @param $name
	 * @param array $options
	 * @return $this
	 */
	function addArgument($name, $options = array()) {
		$arg          = new Argument($name, $options);
		$this->args[] = $arg;

		return $this;
	}

	/**
	 * Get arguments
	 * @return array
	 */
	function args() {
		return $this->parsedArgs;
	}

	/**
	 * Count arguments
	 * @return int
	 */
	function count() {
		return count($this->args());
	}

	/**
	 * Get flag or argument
	 * @param $name
	 * @return mixed
	 */
	function get($name) {
		return $this->flag($name) ? : $this->arg($name);
	}

	/**
	 * Get argument from position
	 * @param $pos
	 * @return mixed
	 */
	function arg($pos) {
		if(array_key_exists($pos, $this->parsedNamedArgs)) {
			return $this->parsedNamedArgs[$pos];
		}
		if(array_key_exists($pos, $this->parsedArgs)) {
			return $this->parsedArgs[$pos];
		}
	}

	/**
	 * Get flag
	 * @param $name
	 * @return mixed
	 */
	function flag($name) {
		if(array_key_exists($name, $this->parsedFlags)) {
			return $this->parsedFlags[$name];
		}
	}

	/**
	 * Usage
	 * @return string
	 */
	function usage() {
		$flags  = join(' ', array_unique(array_values($this->flags)));
		$args   = join(' ', $this->args);
		$script = $this->name ? : 'php ' . basename($_SERVER["SCRIPT_NAME"]);
		$usage  = "Usage: $script $flags $args";
		if($this->examples) {
			$usage .= "\n\nExamples\n\n" . join("\n", $this->examples);
		}
		if($this->description) {
			$usage .= "\n\n{$this->description}";
		}

		return $usage;
	}

	function slice($start, $length = null) {
		return array_slice($this->parsedArgs, $start, $length);
	}

	function offsetGet($offset) {
		return $this->get($offset);
	}

	function offsetExists($offset) {
		return null !== $this->get($offset);
	}

	function offsetSet($offset, $value) {
	}

	function offsetUnset($offset) {
	}
}

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

/*
 * @BEGIN Scanner
 * Script
 */

$version = __VERSION__;
$header  = <<<EOD


 █████╗ ███╗   ███╗██╗    ██╗███████╗ ██████╗ █████╗ ███╗   ██╗
██╔══██╗████╗ ████║██║    ██║██╔════╝██╔════╝██╔══██╗████╗  ██║
███████║██╔████╔██║██║ █╗ ██║███████╗██║     ███████║██╔██╗ ██║
██╔══██║██║╚██╔╝██║██║███╗██║╚════██║██║     ██╔══██║██║╚██╗██║
██║  ██║██║ ╚═╝ ██║╚███╔███╔╝███████║╚██████╗██║  ██║██║ ╚████║
╚═╝  ╚═╝╚═╝     ╚═╝ ╚══╝╚══╝ ╚══════╝ ╚═════╝╚═╝  ╚═╝╚═╝  ╚═══╝
Github: https://github.com/marcocesarato/PHP-Antimalware-Scanner

                      version $version

EOD;
Console::display($header, "green");
Console::display(PHP_EOL);
Console::display("                                                               ", 'black', 'green');
Console::display(PHP_EOL);
Console::display("                   PHP Antimalware Scanner                     ", 'black', 'green');
Console::display(PHP_EOL);
Console::display("                  Created by Marco Cesarato                    ", 'black', 'green');
Console::display(PHP_EOL);
Console::display("                                                               ", 'black', 'green');
Console::display(PHP_EOL2);

// Define Arguments
$_ARGV = new Argv();
$_ARGV->addFlag("agile", ["alias" => "-a", "default" => false]);
$_ARGV->addFlag("help", ["alias" => "-h", "default" => false]);
$_ARGV->addFlag("log", ["alias" => "-l", "default" => false]);
$_ARGV->addFlag("scan", ["alias" => "-s", "default" => false]);
$_ARGV->addFlag("exploits", ["default" => false, "has_value" => true]);
$_ARGV->addFlag("functions", ["default" => false, "has_value" => true]);
$_ARGV->addFlag("only-exploits", ["alias" => "-e", "default" => false]);
$_ARGV->addFlag("only-functions", ["alias" => "-f", "default" => false]);
$_ARGV->addFlag("whitelist-only-path", ["default" => false]);
$_ARGV->addArgument("path", ["var_args" => true, "default" => ""]);
$_ARGV->parse();

// Help
if(isset($_ARGV['help']) && $_ARGV['help']) {
	Console::helper();
}

// Check if only scanner
if(isset($_ARGV['scan']) && $_ARGV['scan']) {
	$_REQUEST['scan'] = true;
} else {
	$_REQUEST['scan'] = false;
}

// Write logs
if(isset($_ARGV['log']) && $_ARGV['log']) {
	$_REQUEST['log'] = true;
} else {
	$_REQUEST['log'] = false;
}

// Check on whitelist only file path and not line number
if(isset($_ARGV['whitelist-only-path']) && $_ARGV['whitelist-only-path']) {
	$_REQUEST['whitelist-only-path'] = true;
} else {
	$_REQUEST['whitelist-only-path'] = false;
}

// Check Filter exploits
if(isset($_ARGV['exploits']) && $_ARGV['exploits']) {
	if(is_string($_ARGV['exploits'])){
		$filtered = str_replace(array("\n","\r","\t"," ") ,"", $_ARGV['exploits']);
		$filtered = @explode(',', $filtered);
		if(!empty($filtered) && count($filtered) > 0){
			foreach($_DEFAULT_EXPLOITS as $key => $value){
				if(in_array($key, $filtered)){
					$_EXPLOITS[$key] = $value;
				}
			}
			if(!empty($_EXPLOITS) && count($_EXPLOITS) > 0) {
				Console::write("Exploit to search: " . implode(', ', array_keys($_EXPLOITS)) . PHP_EOL);
			} else {
				$_EXPLOITS = array();
            }
		}
	}
}

// Check if exploit mode is enabled
if(isset($_ARGV['only-exploits']) && $_ARGV['only-exploits']) {
	$_REQUEST['exploits'] = true;
} else {
	$_REQUEST['exploits'] = false;
}

// Check functions to search
if(isset($_ARGV['functions']) && $_ARGV['functions']) {
	if(is_string($_ARGV['functions'])){
		$_FUNCTIONS = str_replace(array("\n","\r","\t"," ") ,"", $_ARGV['functions']);
		$_FUNCTIONS = @explode(',', $_FUNCTIONS);
		if(!empty($_FUNCTIONS) && count($_FUNCTIONS) > 0){
			Console::write("Functions to search: " . implode(', ', $_FUNCTIONS) . PHP_EOL);
		} else {
			$_FUNCTIONS = array();
		}
	}
}

// Check if functions mode is enabled
if(isset($_ARGV['only-functions']) && $_ARGV['only-functions']) {
    $_REQUEST['functions'] = true;
} else {
	$_REQUEST['functions'] = false;
}

if($_REQUEST['functions'] && $_REQUEST['exploits']){
	Console::write('Can\'t be set both flags --only-functions and --only-functions together!');
	die(PHP_EOL2);
}

// Check if agile scan is enabled
if(isset($_ARGV['agile']) && $_ARGV['agile']){
	$_REQUEST['exploits'] = true;
	$_EXPLOITS['execution'] = '/\b(eval|assert|passthru|exec|include|system|pcntl_exec|shell_exec|`|array_map|ob_start|call_user_func(_array)?)\s*\(\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/';
	$_EXPLOITS['concat_vars_with_spaces'] = '/(\$([a-zA-Z0-9]+)[\s\r\n]*\.[\s\r\n]*){8}/';  // concatenation of more than 8 words, with spaces
	$_EXPLOITS['concat_vars_array'] = '/(\$([a-zA-Z0-9]+)(\{|\[)([0-9]+)(\}|\])[\s\r\n]*\.[\s\r\n]*){8}.*?(?=\})\}/i'; // concatenation of more than 8 words, with spaces
	unset($_EXPLOITS['nano'], $_EXPLOITS['double_var2']);
}

// Check if logs and scan at the same time
if(isset($_ARGV['log']) && $_ARGV['log'] && isset($_ARGV['scan']) && $_ARGV['scan']) {
	unset($_REQUEST['log']);
}

// Start scanning
Console::display("Start scanning..." . PHP_EOL, 'green');

// Check for path or functions as first argument
if(!empty($_ARGV->arg(0))) {
	$path = trim($_ARGV->arg(0));
	if(file_exists(realpath($path))) {
		$_SCAN_PATH = realpath($path);
	}
}

// Check path
if(!is_dir($_SCAN_PATH)) {
	$_SCAN_PATH = pathinfo($_SCAN_PATH, PATHINFO_DIRNAME);
}

// Prepare whitelist
$_WHITELIST = CSV::read(__PATH_WHITELIST__);
// Remove logs
@unlink(__PATH_LOGS__);

Console::write("Scan date: " . date("d-m-Y H:i:s") . PHP_EOL);
Console::write("Scanning $_SCAN_PATH" . PHP_EOL2);

// Malware Definitions
if($_REQUEST['functions'] || !$_REQUEST['exploits'] && empty($_FUNCTIONS)) {
	// Functions to search
	$_FUNCTIONS = $_DEFAULT_FUNCTIONS;
	if($_REQUEST['functions']){
		$_EXPLOITS = array();
	}
} else if($_REQUEST['exploits']){
	$_FUNCTIONS = array();
	Console::write("Exploits mode enabled" . PHP_EOL);
} else {
	Console::write("No functions to search" . PHP_EOL);
}
// Exploits to search
if(!$_REQUEST['functions'] && empty($_EXPLOITS)) {
	$_EXPLOITS = $_DEFAULT_EXPLOITS;
}

if($_ARGV['agile']) {
	Console::write("Agile mode enabled" . PHP_EOL);
}

if($_REQUEST['scan']) {
	Console::write("Scan mode enabled" . PHP_EOL);
}

Console::write("Mapping files..." . PHP_EOL);

// Mapping files
$directory                  = new \RecursiveDirectoryIterator($_SCAN_PATH);
$files                      = new \RecursiveIteratorIterator($directory);
$iterator                   = new CallbackFilterIterator($files, function($cur, $key, $iter) {
	return ($cur->isFile() && in_array($cur->getExtension(), $GLOBALS['_SCAN_EXTENSIONS'] ));
});

// Counting files
$files_count = iterator_count($iterator);
Console::write("Found " . $files_count . " files" . PHP_EOL2);
Console::write("Checking files..." . PHP_EOL2);
Console::progress(0, $files_count);

// Scanning
foreach($iterator as $info) {

	Console::progress($summary_scanned, $files_count);

	$_FILE_NAME      = $info->getFilename();
	$_FILE_PATH      = $info->getPathname();
	$_FILE_EXTENSION = $info->getExtension();

	// Case favicon_[random chars].ico
	$is_favicon = (((strpos($_FILE_NAME, 'favicon_') === 0) && ($_FILE_EXTENSION === 'ico') && (strlen($_FILE_NAME) > 12)) || preg_match('/^\.[\w]+\.ico/i', trim($_FILE_NAME)));
	if((in_array($_FILE_EXTENSION, $_SCAN_EXTENSIONS)
	    && (!file_exists(__PATH_QUARANTINE__) || strpos(realpath($_FILE_PATH), realpath(__PATH_QUARANTINE__)) === false)
		   /*&& (strpos($filename, '-') === FALSE)*/)
	   || $is_favicon) {

		$found         = false;
		$pattern_found = array();

		$fc          = file_get_contents($_FILE_PATH);
		$fc_clean    = php_strip_whitespace($_FILE_PATH);
		$fc_filtered = preg_replace("/<\?php(.*?)(?!\B\"[^\"]*)\?>(?![^\"]*\"\B)/si", "$1", $fc_clean); // Only php code
		$fc_filtered = preg_replace("/(\\'|\\\")[\s\r\n]*\.[\s\r\n]*('|\")/si", "", $fc_filtered); // Remove "ev"."al"
		$fc_filtered = preg_replace("/([\s]+)/i", " ", $fc_filtered); // Remove multiple spaces

		// Convert hex
		$fc_filtered = preg_replace_callback('/\\\\x[A-Fa-f0-9]{2}/si', function($match) {
			return @hex2bin(str_replace('\\x', '', $match));
		}, $fc_filtered);

		// Convert dec and oct
		$fc_filtered = preg_replace_callback('/\\\\[0-9]{3}/si', function($match) {
			return chr(intval($match));
		}, $fc_filtered);

		// Decode strings
		$decoders = array(
			'str_rot13',
			'gzinflate',
			'base64_decode',
			'rawurldecode',
			'gzuncompress',
			'strrev',
            'convert_uudecode',
            'urldecode'
		);
		$pattern_decoder = array();
		foreach($decoders as $decoder){
			$pattern_decoder[] = preg_quote($decoder, '/');
		}
		$last_match = null;
		$recursive_loop = true;
		do {
		    // Check decode functions
			$regex_pattern = '/((' . implode($pattern_decoder, '|') . ')[\s\r\n]*\((([^()]|(?R))*)?\))/si';
			preg_match($regex_pattern, $fc_filtered, $match);
			// Get value inside function
			if($recursive_loop && preg_match('/(\((?:\"|\\\')(([^\\\'\"]|(?R))*?)(?:\"|\\\')\))/si', $match[0], $encoded_match)) {
			    $value          = $encoded_match[3];
			    $last_match     = $match;
				$decoders_found = array_reverse(explode('(', $match[0]));
				foreach($decoders_found as $decoder) {
					if(in_array($decoder, $decoders)) {
						if(is_string($value) && !empty($value)) {
							$value = $decoder($value); // Decode
						}
					}
				}
				if(is_string($value) && !empty($value)) {
					$value = str_replace('"', "'", $value);
					$value = '"' . $value . '"';
					$fc_filtered = str_replace($match[0], $value, $fc_filtered);
				} else {
					$recursive_loop = false;
				}
			} else {
                $recursive_loop = false;
			}
		} while(!empty($match[0]) && $recursive_loop);
		unset($last_match, $recursive_loop, $value, $match, $decoders_found, $decoders, $pattern_decoder, $encoded_match);

		// Scan exploits
		foreach($_EXPLOITS as $key => $pattern) {
			$last_match = null;
			$match_description = null;
			if(@preg_match($pattern, $fc, $match, PREG_OFFSET_CAPTURE) || // Original
			   @preg_match($pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE) || // No comments
			   @preg_match($pattern, $fc_filtered, $match, PREG_OFFSET_CAPTURE)) { // Filtered
				$found      = true;
				$last_match = $match[0][0];
				$match_description = $key . "\n => ".$last_match;
			}
			if(!empty($last_match) && @preg_match('/'.preg_quote($last_match, '/').'/i', $fc, $match, PREG_OFFSET_CAPTURE)) {
				$lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
				$match_description = $key . " [line " . $lineNumber . "]\n => ".$last_match;
			}
			if(!empty($match_description)){
				$pattern_found[$match_description] = $pattern;
			}
		}
		unset($last_match, $match_description, $lineNumber, $match);

		// Scan php commands
		foreach($_FUNCTIONS as $_func) {
			$last_match           = null;
			$match_description    = null;
			$func                 = preg_quote(trim($_func), '/');
			// Search on filtered content
			$regex_pattern        = "/(?:^|[^a-zA-Z0-9_]+)(" . $func . "[\s\r\n]*\((?<=\().*?(?=\))\))/si";
			$regex_pattern_base64 = "/" . base64_encode($_func) . "/s";
			if(@preg_match($regex_pattern, $fc_filtered, $match, PREG_OFFSET_CAPTURE) ||
			   @preg_match($regex_pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE) ||
			   @preg_match($regex_pattern_base64, $fc_filtered, $match, PREG_OFFSET_CAPTURE) ||
			   @preg_match($regex_pattern_base64, $fc_clean, $match, PREG_OFFSET_CAPTURE)) {
				$found      = true;
				$last_match = explode($_func, $match[0][0]);
				$last_match = $_func . $last_match[1];
				$match_description = $func . "\n => ".$last_match;
			}
			if(!empty($last_match) && @preg_match('/'.preg_quote($last_match,'/').'/', $fc, $match, PREG_OFFSET_CAPTURE)) {
				$lineNumber = count(explode("\n", substr($fc, 0, $match[0][1])));
				$match_description = $func . " [line " . $lineNumber . "]\n => ".$last_match;
			}
			if(!empty($match_description)){
				$pattern_found[$match_description] = $regex_pattern;
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
		unset($fc_filtered, $fc_clean);

		// Check whitelist
		$pattern_found = array_unique($pattern_found);
		$in_whitelist = 0;
		foreach($_WHITELIST as $item) {
			foreach($pattern_found as $key => $pattern) {
				$exploit           = preg_replace("/^(\S+) \[line [0-9]+\].*/si", "$1", trim($key));
				$exploit_whitelist = preg_replace("/^(\S+).*/si", "$1", trim($item[1]));
				$lineNumber        = preg_replace("/^\S+ \[line ([0-9]+)\].*/si", "$1", trim($key));
				if(realpath($_FILE_PATH) == realpath($item[0]) && $exploit == $exploit_whitelist &&
				   ($_REQUEST['whitelist-only-path'] || !$_REQUEST['whitelist-only-path'] && $lineNumber == $item[2])) {
				    $in_whitelist ++;
				}
			}
		}

		if($is_favicon) {
			$pattern_found['infected_icon'] = '';
		}

		// Scan finished
		$summary_scanned ++;
		usleep(10);

		if(realpath($_FILE_PATH) != realpath(__FILE__) && ($is_favicon || $found) && ($in_whitelist === 0 || $in_whitelist != count($pattern_found))) {
			$summary_detected ++;
			// Scan mode only
			if($_REQUEST['scan']) {
				$summary_ignored[] = $_FILE_PATH;
				continue;
			// Scan with code check
			} else {
				$_WHILE = true;
				$last_command = '0';
				Console::display(PHP_EOL2);
				Console::write(PHP_EOL);
				Console::write("PROBABLE MALWARE FOUND!", 'red');
				while($_WHILE) {
				    $preview_lines = explode(PHP_EOL, trim($fc));
					$preview = implode(PHP_EOL, array_slice($preview_lines,0,1000));
					if(!in_array($last_command, array('4','5','7'))) {
						Console::write(PHP_EOL . "$_FILE_PATH", 'yellow');
						Console::write(PHP_EOL2);
						Console::write("========================================== PREVIEW ===========================================", 'white', 'red');
						Console::write(PHP_EOL2);
						Console::code($preview, $pattern_found);
						if(count($preview_lines) > 1000) {
							Console::write(PHP_EOL2);
							Console::write('  [ ' . (count($preview_lines) - 1000) . ' More lines ]');
						}
						Console::write(PHP_EOL2);
						Console::write("==============================================================================================", 'white', 'red');
					}
                    Console::write(PHP_EOL2);
					Console::write("File path: " . $_FILE_PATH, 'yellow');
					Console::write("\n");
					Console::write("Exploit: " . PHP_EOL . implode(PHP_EOL, array_keys($pattern_found)), 'red');
					Console::display(PHP_EOL2);
					Console::display("OPTIONS:" . PHP_EOL2);
					Console::display("    [1] Delete file" . PHP_EOL);
					Console::display("    [2] Move to quarantine" . PHP_EOL);
					Console::display("    [3] Try remove evil code" . PHP_EOL);
					Console::display("    [4] Open with vim" . PHP_EOL);
					Console::display("    [5] Open with nano" . PHP_EOL);
					Console::display("    [6] Add to whitelist" . PHP_EOL);
					Console::display("    [7] Show source" . PHP_EOL);
					Console::display("    [-] Ignore" . PHP_EOL2);
					$confirmation = Console::read("What is your choice? ", "purple");
					Console::display(PHP_EOL);

					$last_command = $confirmation;
					unset($preview_lines, $preview);

					// Remove file
					if(in_array($confirmation, array('1'))) {
						Console::write("File path: " . $_FILE_PATH. PHP_EOL, 'yellow');
						$confirm2 = Console::read("Want delete this file [y|N]? ", "purple");
						Console::display(PHP_EOL);
						if($confirm2 == 'y') {
							unlink($_FILE_PATH);
							$summary_removed[] = $_FILE_PATH;
							Console::write("File '$_FILE_PATH' removed!" . PHP_EOL2, 'green');
							$_WHILE = false;
						}
					// Move to quarantine
					} else if(in_array($confirmation, array('2'))) {
						$quarantine = __PATH_QUARANTINE__ . str_replace(realpath(__DIR__), '', $_FILE_PATH);

						if(!is_dir(dirname($quarantine))) {
							mkdir(dirname($quarantine), 0755, true);
						}
						rename($_FILE_PATH, $quarantine);
						$summary_quarantine[] = $quarantine;
						Console::write("File '$_FILE_PATH' moved to quarantine!" . PHP_EOL2, 'green');
						$_WHILE = false;
					// Remove evil code
					} else if(in_array($confirmation, array('3')) && count($pattern_found) > 0) {
						foreach($pattern_found as $pattern) {
							preg_match($pattern, $fc, $string_match);
							preg_match('/(<\?php)(.*?)(' . preg_quote($string_match[0], '/') . '\s*\;?)(.*?)((?!\B"[^"]*)\?>(?![^"]*"\B)|.*?$)/si', $fc, $match);
							if(!empty(trim($match[2])) || !empty(trim($match[4]))) {
								$fc = str_replace($match[0], $match[1] . $match[2] . $match[4] . $match[5], $fc);
							} else {
								$fc = str_replace($match[0], '', $fc);
							}
							$fc = preg_replace('/<\?php[\s\r\n]*\?\>/si', '', $fc);
						}
						Console::write(PHP_EOL);
						Console::write("========================================== SANITIZED ==========================================", 'black', 'green');
						Console::write(PHP_EOL2);
						Console::code($fc);
						Console::write(PHP_EOL2);
						Console::write("===============================================================================================", 'black', 'green');
						Console::display(PHP_EOL2);
						Console::display("File sanitized, now you must verify if has been fixed correctly." . PHP_EOL2, "yellow");
						$confirm2 = Console::read("Confirm and save [y|N]? ", "purple");
						Console::display(PHP_EOL);
						if($confirm2 == 'y') {
							Console::write("File '$_FILE_PATH' sanitized!" . PHP_EOL2, 'green');
							file_put_contents($_FILE_PATH, $fc);
							$summary_removed[] = $_FILE_PATH;
							$_WHILE = false;
						} else {
							$summary_ignored[] = $_FILE_PATH;
						}
					// Edit with vim
					} else if(in_array($confirmation, array('4'))) {
						$descriptors = array(
							array('file', '/dev/tty', 'r'),
							array('file', '/dev/tty', 'w'),
							array('file', '/dev/tty', 'w')
						);
						$process     = proc_open("vim '$_FILE_PATH'", $descriptors, $pipes);
						while(true) {
							if(proc_get_status($process)['running'] == false) {
								break;
							}
						}
						$summary_edited[] = $_FILE_PATH;
						Console::write("File '$_FILE_PATH' edited with vim!" . PHP_EOL2, 'green');
						$summary_removed[] = $_FILE_PATH;
					// Edit with nano
					} else if(in_array($confirmation, array('5'))) {
						$descriptors = array(
							array('file', '/dev/tty', 'r'),
							array('file', '/dev/tty', 'w'),
							array('file', '/dev/tty', 'w')
						);
						$process     = proc_open("nano -c '$_FILE_PATH'", $descriptors, $pipes);
						while(true) {
							if(proc_get_status($process)['running'] == false) {
								break;
							}
						}
						$summary_edited[] = $_FILE_PATH;
						Console::write("File '$_FILE_PATH' edited with nano!" . PHP_EOL2, 'green');
						$summary_removed[] = $_FILE_PATH;
					// Add to whitelist
					} else if(in_array($confirmation, array('6'))) {
						foreach($pattern_found as $key => $pattern) {
							$exploit      = preg_replace("/^(\S+) \[line [0-9]+\].*/si", "$1", $key);
							$lineNumber   = preg_replace("/^\S+ \[line ([0-9]+)\].*/si", "$1", $key);
							$_WHITELIST[] = array(realpath($_FILE_PATH), $exploit, $lineNumber);
						}
						$_WHITELIST = array_map("unserialize", array_unique(array_map("serialize", $_WHITELIST)));
						if(CSV::write(__PATH_WHITELIST__, $_WHITELIST)) {
							$summary_whitelist[] = $_FILE_PATH;
							Console::write("Exploits of file '$_FILE_PATH' added to whitelist!" . PHP_EOL2, 'green');
							$_WHILE = false;
						} else {
							Console::write("Exploits of file '$_FILE_PATH' failed adding file to whitelist! Check write permission of '".__PATH_WHITELIST__ . "' file!" . PHP_EOL2, 'red');
                        }
					// Show source code
					} else if(in_array($confirmation, array('7'))) {
						Console::write(PHP_EOL . "$_FILE_PATH", 'yellow');
                        Console::write(PHP_EOL2);
                        Console::write("=========================================== SOURCE ===========================================", 'white', 'red');
                        Console::write(PHP_EOL2);
                        Console::code($fc, $pattern_found);
                        Console::write(PHP_EOL2);
                        Console::write("==============================================================================================", 'white', 'red');
					// None
					} else {
						Console::write("File '$_FILE_PATH' skipped!" . PHP_EOL2, 'green');
						$summary_ignored[] = $_FILE_PATH;
						$_WHILE            = false;
					}

					Console::write(PHP_EOL);
				}
				unset($fc);
			}
		}
	}
}

Console::write(PHP_EOL2);
Console::write("Scan finished!", 'green');
Console::write(PHP_EOL3);

// Statistics
Console::write("                SUMMARY                ", 'black', 'cyan');
Console::write(PHP_EOL2);
Console::write("Files scanned: " . $summary_scanned . PHP_EOL);
if(!$_REQUEST['scan']) {
	$summary_ignored = array_unique($summary_ignored);
	$summary_edited  = array_unique($summary_edited);
	Console::write("Files edited: " . count($summary_edited) . PHP_EOL);
	Console::write("Files quarantined: " . count($summary_quarantine) . PHP_EOL);
	Console::write("Files whitelisted: " . count($summary_whitelist) . PHP_EOL);
	Console::write("Files ignored: " . count($summary_ignored) . PHP_EOL2);
}
Console::write("Malware detected: " . $summary_detected . PHP_EOL);
if(!$_REQUEST['scan']) {
	Console::write("Malware removed: " . count($summary_removed) . PHP_EOL);
}

if($_REQUEST['scan']) {
	Console::write(PHP_EOL . "Files infected: '" . __PATH_LOGS_INFECTED__ . "'" . PHP_EOL, 'red');
	file_put_contents(__PATH_LOGS_INFECTED__, "Log date: " . date("d-m-Y H:i:s") . PHP_EOL . implode(PHP_EOL, $summary_ignored));
	Console::write(PHP_EOL2);
} else {
	if(count($summary_removed) > 0) {
		Console::write(PHP_EOL . "Files removed:" . PHP_EOL, 'red');
		foreach($summary_removed as $un) {
			Console::write($un . PHP_EOL);
		}
	}
	if(count($summary_edited) > 0) {
		Console::write(PHP_EOL . "Files edited:" . PHP_EOL, 'green');
		foreach($summary_edited as $un) {
			Console::write($un . PHP_EOL);
		}
	}
	if(count($summary_quarantine) > 0) {
		Console::write(PHP_EOL . "Files quarantined:" . PHP_EOL, 'yellow');
		foreach($summary_ignored as $un) {
			Console::write($un . PHP_EOL);
		}
	}
	if(count($summary_whitelist) > 0) {
		Console::write(PHP_EOL . "Files whitelisted:" . PHP_EOL, 'cyan');
		foreach($summary_whitelist as $un) {
			Console::write($un . PHP_EOL);
		}
	}
	if(count($summary_ignored) > 0) {
		Console::write(PHP_EOL . "Files ignored:" . PHP_EOL, 'cyan');
		foreach($summary_ignored as $un) {
			Console::write($un . PHP_EOL);
		}
	}
	Console::write(PHP_EOL2);
}

/*
 * @END Scanner
 * Script
 */