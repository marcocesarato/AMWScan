<?php

/**
 * PHP Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2019
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace marcocesarato\amwscan;

/**
 * Class Definitions
 * @package marcocesarato\amwscan
 */
class Definitions {
	// Default exploits definitions
	public static $EXPLOITS = array(
		"eval_chr"                => '/chr[\s\r\n]*\([\s\r\n]*101[\s\r\n]*\)[\s\r\n]*\.[\s\r\n]*chr[\s\r\n]*\([\s\r\n]*118[\s\r\n]*\)[\s\r\n]*\.[\s\r\n]*chr[\s\r\n]*\([\s\r\n]*97[\s\r\n]*\)[\s\r\n]*\.[\s\r\n]*chr[\s\r\n]*\([\s\r\n]*108[\s\r\n]*\)/i',
		"eval_preg"               => '/(preg_replace(_callback)?|mb_ereg_replace|preg_filter)[\s\r\n]*\(.+(\/|\\\\x2f)(e|\\\\x65)[\\\'\"].*?(?=\))\)/i',
		"eval_base64"             => '/eval[\s\r\n]*\([\s\r\n]*base64_decode[\s\r\n]*\((?<=\().*?(?=\))\)/i',
		"eval_comment"            => '/(eval|preg_replace|system|assert|passthru|(pcntl_)?exec|shell_exec|call_user_func(_array)?)\/\*[^\*]*\*\/\((?<=\().*?(?=\))\)/',
		"eval_execution"          => '/(eval\(\$[a-z0-9_]+\((?<=\()@?\$_(GET|POST|SERVER|COOKIE|REQUEST).*?(?=\))\)/si',
		"align"                   => '/(\$\w+=[^;]*)*;\$\w+=@?\$\w+\((?<=\().*?(?=\))\)/si',
		"b374k"                   => '/(\\\'|\")ev(\\\'|\")\.(\\\'|\")al(\\\'|\")\.(\\\'|\")\(\"\?>/i', // b374k shell
		"weevely3"                => '/\$\w=\$[a-zA-Z]\(\'\',\$\w\);\$\w\(\);/i', // weevely3 launcher
		"c99_launcher"            => '/;\$\w+\(\$\w+(,\s?\$\w+)+\);/i', // http://bartblaze.blogspot.fr/2015/03/c99shell-not-dead.html
		"too_many_chr"            => '/(chr\([\d]+\)\.){8}/i', // concatenation of more than eight `chr()`
		"concat"                  => '/(\$[\w\[\]\\\'\"]+\\.[\n\r]*){10}/i', // concatenation of vars array
		"concat_vars_with_spaces" => '/(\$([a-zA-Z0-9]+)[\s\r\n]*\.[\s\r\n]*){6}/', // concatenation of more than 6 words, with spaces
		"concat_vars_array"       => '/(\$([a-zA-Z0-9]+)(\{|\[)([0-9]+)(\}|\])[\s\r\n]*\.[\s\r\n]*){6}.*?(?=\})\}/i', // concatenation of more than 6 words, with spaces
		"var_as_func"             => '/\$_(GET|POST|COOKIE|REQUEST|SERVER)[\s\r\n]*\[[^\]]+\][\s\r\n]*\((?<=\().*?(?=\))\)/i',
		"global_var_string"       => '/\$\{[\s\r\n]*(\\\'|\")_(GET|POST|COOKIE|REQUEST|SERVER)(\\\'|\")[\s\r\n]*\}/i',
		"extract_global"          => '/extract\([\s\r\n]*\$_(GET|POST|COOKIE|REQUEST|SERVER).*?(?=\))\)/i',
		"escaped_path"            => '/(\\\\x[0-9abcdef]{2}[a-z0-9.-\/]{1,4}){4,}/i',
		"include_icon"            => '/@?include[\s\r\n]*(\([\s\r\n]*)?("|\\\')([^"\\\']*)(\.|\\\\056\\\\046\\\\2E)(\i|\\\\151|\\\\x69|\\\\105)(c|\\\\143\\\\099\\\\x63)(o|\\\\157\\\\111|\\\\x6f)(\"|\\\')((?=\))\))?/mi',  // Icon inclusion
		"backdoor_code"           => '/eva1fYlbakBcVSir/i',
		"infected_comment"        => '/\/\*[a-z0-9]{5}\*\//i', // usually used to detect if a file is infected yet
		"hex_char"                => '/\\\\[Xx](5[Ff])/i',
		"hacked_by"               => '/hacked[\s\r\n]*by/i',
		"killall"                 => '/killall[\s\r\n]*\-9/i',
		"globals_concat"          => '/\$GLOBALS\[[\s\r\n]*\$GLOBALS[\\\'[a-z0-9]{4,}\\\'\]/i',
		"globals_assign"          => '/\$GLOBALS\[\\\'[a-z0-9]{5,}\\\'\][\s\r\n]*=[\s\r\n]*\$[a-z]+\d+\[\d+\]\.\$[a-z]+\d+\[\d+\]\.\$[a-z]+\d+\[\d+\]\.\$[a-z]+\d+\[\d+\]\./i',
		"base64_long"             => '/[\\\'\"][A-Za-z0-9+\/]{260,}={0,3}[\\\'\"]/',
		"base64_inclusion"        => '/@?include[\s\r\n]*(\([\s\r\n]*)?("|\\\')data\:text/plain;base64[\s\r\n]*\,[\s\r\n]*\$_GET\[[^\]]+\](\\\'|")[\s\r\n]*((?=\))\))?/si',
		"clever_include"          => '/@?include[\s\r\n]*(\([\s\r\n]*)?("|\\\')[\s\r\n]*[^\.]+\.(png|jpe?g|gif|bmp|ico).*?("|\\\')[\s\r\n]*((?=\))\))?/i',
		"basedir_bypass"          => '/curl_init[\s\r\n]*\([\s\r\n]*[\"\\\']file:\/\/.*?(?=\))\)/i',
		"basedir_bypass2"         => '/file\:file\:\/\//i', // https://www.intelligentexploit.com/view-details.html?id=8719
		"non_printable"           => '/(function|return|base64_decode).{,256}[^\\x00-\\x1F\\x7F-\\xFF]{3}/i',
		"double_var"              => '/\${[\s\r\n]*\${.*?}(.*)?}/i',
		"double_var2"             => '/\${\$[0-9a-zA-z]+}/i',
		"global_save"             => '/\[\s\r\n]*=[\s\r\n]*\$GLOBALS[\s\r\n]*\;[\s\r\n]*\$[\s\r\n]*\{/i',
		"hex_var"                 => '/\$\{[\s\r\n]*(\\\'|\")\\\\x.*?(?=\})\}/i', // check for ${"\xFF"}, IonCube use this method ${"\x
		"register_function"       => '/register_[a-z]+_function[\s\r\n]*\([\s\r\n]*[\\\'\"][\s\r\n]*(eval|assert|passthru|exec|include|system|shell_exec|`).*?(?=\))\)/i',  // https://github.com/nbs-system/php-malware-finder/issues/41
		"safemode_bypass"         => '/\x00\/\.\.\/|LD_PRELOAD/i',
		"ioncube_loader"          => '/IonCube\_loader/i',
		"nano"                    => '/\$[a-z0-9-_]+\[[^]]+\]\((?<=\().*?(?=\))\)/', //https://github.com/UltimateHackers/nano
		"ninja"                   => '/base64_decode[^;]+getallheaders/',
		"execution"               => '/\b(eval|assert|passthru|exec|include|system|pcntl_exec|shell_exec|base64_decode|`|array_map|ob_start|call_user_func(_array)?)[\s\r\n]*\([\s\r\n]*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\\\?@?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/',  // function that takes a callback as 1st parameter
		"execution2"              => '/\b(array_filter|array_reduce|array_walk(_recursive)?|array_walk|assert_options|uasort|uksort|usort|preg_replace_callback|iterator_apply)[\s\r\n]*\([\s\r\n]*[^,]+,[\s\r\n]*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\\\?@?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/',  // functions that takes a callback as 2nd parameter
		"execution3"              => '/\b(array_(diff|intersect)_u(key|assoc)|array_udiff)[\s\r\n]*\([\s\r\n]*([^,]+[\s\r\n]*,?)+[\s\r\n]*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\\\?@?\$_(GET|REQUEST|POST|COOKIE|SERVER))[\s\r\n]*\[[^]]+\][\s\r\n]*\)+[\s\r\n]*;/',  // functions that takes a callback as 2nd parameter
		"shellshock"              => '/\(\)[\s\r\n]*{[\s\r\n]*[a-z:][\s\r\n]*;[\s\r\n]*}[\s\r\n]*;/',
		"silenced_eval"           => '/@eval[\s\r\n]*\((?<=\().*?(?=\))\)/',
		"silence_inclusion"       => '/@(include|include_once|require|require_once)[\s\r\n]+([\s\r\n]*\()?("|\\\')([^"\\\']*)(\\\\x[0-9a-f]{2,}.*?){2,}([^"\\\']*)("|\\\')[\s\r\n]*((?=\))\))?/si',
		"silence_inclusion2"      => '/@(include|include_once|require|require_once)[\s\r\n]+([\s\r\n]*\()?("|\\\')([^"\\\']*)(\\[0-9]{3,}.*?){2,}([^"\\\']*)("|\\\')[\s\r\n]*((?=\))\))?/si',
		"ssi_exec"                => '/\<\!\-\-\#exec[\s\r\n]*cmd\=/i', //http://www.w3.org/Jigsaw/Doc/User/SSI.html#exec
		"htaccess_handler"        => '/SetHandler[\s\r\n]*application\/x\-httpd\-php/i',
		"htaccess_type"           => '/AddType\s+application\/x-httpd-(php|cgi)/i',
		"file_prepend"            => '/php_value[\s\r\n]*auto_prepend_file/i',
		"iis_com"                 => '/IIS\:\/\/localhost\/w3svc/i',
		"reversed"                => '/(noitcnuf\_etaerc|metsys|urhtssap|edulcni|etucexe\_llehs|ecalper\_rts|ecalper_rts)/i',
		"rawurlendcode_rot13"     => '/rawurldecode[\s\r\n]*\(str_rot13[\s\r\n]*\((?<=\().*?(?=\))\)/i',
		"serialize_phpversion"    => '/\@serialize[\s\r\n]*\([\s\r\n]*(Array\(|\[)(\\\'|\")php(\\\'|\")[\s\r\n]*\=\>[\s\r\n]*\@phpversion[\s\r\n]*\((?<=\().*?(?=\))\)/si',
		"md5_create_function"     => '/\$md5[\s\r\n]*=[\s\r\n]*.*create_function[\s\r\n]*\(.*?\);[\s\r\n]*\$.*?\)[\s\r\n]*;/si',
		"god_mode"                => '/\/\*god_mode_on\*\/eval\(base64_decode\([\"\\\'][^\"\\\']{255,}[\"\\\']\)\);[\s\r\n]*\/\*god_mode_off\*\//si',
		"wordpress_filter"        => '/\$md5[\s\r\n]*=[\s\r\n]*[\"|\\\']\w+[\"|\\\'];[\s\r\n]*\$wp_salt[\s\r\n]*=[\s\r\n]*[\w\(\),\"\\\'\;$]+[\s\r\n]*\$wp_add_filter[\s\r\n]*=[\s\r\n]*create_function\(.*?\);[\s\r\n]*\$wp_add_filter\(.*?\);/si',
		"password_protection_md5" => '/md5[\s\r\n]*\([\s\r\n]*@?\$_(GET|REQUEST|POST|COOKIE|SERVER)[^)]+\)[\s\r\n]*===?[\s\r\n]*[\\\'\"][0-9a-f]{32}[\\\'\"]/si',
		"password_protection_sha" => '/sha1[\s\r\n]*\([\s\r\n]*@?\$_(GET|REQUEST|POST|COOKIE|SERVER)[^)]+\)[\s\r\n]*===?[\s\r\n]*[\\\'\"][0-9a-f]{40}[\\\'\"]/si',
		"custom_math"             => '/%\(\d+\-\d+\+\d+\)==\(\-\d+\+\d+\+\d+\)/si',
		"custom_math2"            => '/\(\$[a-zA-Z0-9]+%\d==\(\d+\-\d+\+\d+\)/si',
		"uncommon_function"       => 'function\s+_[0-9]{8,}\((?<=\().*?(?=\))\)',
		"download_remote_code"    => '/file_get_contents[\s\r\n]*\([\s\r\n]*base64_url_decode[\s\r\n]*\([\s\r\n]*@*\$_(GET|POST|SERVER|COOKIE|REQUEST).*?(?=\))\)/i',
		"download_remote_code2"   => '/fwrite[\s\r\n]*(\(\w+\((?<=\().*?(?=\))\))?[^\)]*\$_(GET|POST|SERVER|COOKIE|REQUEST).*?(?=\))\)/si',
		"download_remote_code3"   => '/(file_get_contents|fwrite)[\s\r\n]*\([\s\r\n]*@?*\$_(GET|POST|SERVER|COOKIE|REQUEST).*?(?=\))\)/si',
		"php_uname"               => '/php_uname\(["\'asrvm]+\)/si',
		"etc_passwd"              => '/(\/)*etc\/+passwd\/*/si',
		"etc_shadow"              => '/(\/)*etc\/+shadow\/*/si',
		"explode_chr"             => '/explode[\s\r\n]*\(chr[\s\r\n]*\([\s\r\n]*\(?\d{3}([\s\r\n]*-[\s\r\n]*\d{3})?[\s\r\n]*\).*?(?=\))\)/si',
	);

	// Default functions definitions
	public static $FUNCTIONS = array(
		"il_exec",
		"shell_exec",
		"eval",
		"system",
		"create_function",
		"exec",
		"assert",
		"syslog",
		"passthru",
		"define_syslog_variables",
		/*"dl",
		"debugger_off",
		"debugger_on",
		"parse_ini_file",
		"show_source",
		"symlink",
		"popen",*/
		"posix_kill",/*
        "posix_getpwuid",
        "posix_mkfifo",
        "posix_setpgid",
        "posix_setsid",
        "posix_setuid",*/
        "posix_uname",
		"proc_close",
		"proc_get_status",
		"proc_nice",
		"proc_open",
        "proc_terminate",/*
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
        "escapeshellcmd",*/
	);
}