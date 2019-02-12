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
 * Class Application
 * @package marcocesarato\amwscan
 */
class Application {

	public static $NAME = "amwscan";
	public static $VERSION = "0.4.0.38";
	public static $ROOT = "./";
	public static $PATH_QUARANTINE = "/quarantine/";
	public static $PATH_LOGS = "/scanner.log";
	public static $PATH_WHITELIST = "/scanner_whitelist.csv";
	public static $PATH_LOGS_INFECTED = "/scanner_infected.log";

	public static $SCAN_PATH = "./";
	public static $SCAN_EXTENSIONS = array(
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
		'ico'
	);
	public static $ARGV = array();
	public static $WHITELIST = array();

	// Definitions
	public static $FUNCTIONS = array();
	public static $EXPLOITS = array();

	// Summaries
	public static $summary_scanned = 0;
	public static $summary_detected = 0;
	public static $summary_removed = array();
	public static $summary_ignored = array();
	public static $summary_edited = array();
	public static $summary_quarantine = array();
	public static $summary_whitelist = array();

	/**
	 * Application constructor.
	 */
	public function __construct() {

	}

	/**
	 * Initialize
	 */
	private function init(){
		if(self::$ROOT == "./") {
			self::$ROOT = dirname(__FILE__);
		}

		if(self::$SCAN_PATH == "./") {
			self::$SCAN_PATH = dirname(__FILE__);
		}
		self::$PATH_QUARANTINE    = self::$ROOT . self::$PATH_QUARANTINE;
		self::$PATH_LOGS          = self::$ROOT . self::$PATH_LOGS;
		self::$PATH_WHITELIST     = self::$ROOT . self::$PATH_WHITELIST;
		self::$PATH_LOGS_INFECTED = self::$ROOT . self::$PATH_LOGS_INFECTED;

		// Prepare whitelist
		self::$WHITELIST = CSV::read(self::$PATH_WHITELIST);

		// Remove logs
		@unlink(self::$PATH_LOGS);
	}

	/**
	 * Run application
	 */
	public function run() {
		try {
			// Print header
			Console::header();
			// Initialize
			$this->init();
			// Initialize modes
			$this->modes();
			// Initialize arguments
			$this->arguments();

			// Start scanning
			Console::display("Start scanning..." . Console::eol(1));

			Console::write("Scan date: " . date("d-m-Y H:i:s") . Console::eol(1));
			Console::write("Scanning " . self::$SCAN_PATH . Console::eol(2));

			// Mapping files
			Console::write(Console::eol(1) . "Mapping files..." . Console::eol(1));
			$iterator = $this->mapping();

			// Counting files
			$files_count = iterator_count($iterator);
			Console::write("Found " . $files_count . " files" . Console::eol(2));
			Console::write("Checking files..." . Console::eol(2));
			Console::progress(0, $files_count);

			// Scan all files
			$this->scan($iterator);

			// Scan finished
			Console::write(Console::eol(2));
			Console::write("Scan finished!", 'green');
			Console::write(Console::eol(3));

			// Print summary
			$this->summary();
		} catch(\Exception $e) {
			Console::write(Console::eol(1));
			Console::write($e->getMessage(), 'red');
			Console::write(Console::eol(1));
		}
	}

	/**
	 * Initialize application arguments
	 */
	private function arguments() {

		// Define Arguments
		self::$ARGV = new Argv();
		self::$ARGV->addFlag("agile", array("alias" => "-a", "default" => false));
		self::$ARGV->addFlag("help", array("alias" => "-h", "default" => false));
		self::$ARGV->addFlag("log", array("alias" => "-l", "default" => false));
		self::$ARGV->addFlag("scan", array("alias" => "-s", "default" => false));
		self::$ARGV->addFlag("exploits", array("default" => false, "has_value" => true));
		self::$ARGV->addFlag("functions", array("default" => false, "has_value" => true));
		self::$ARGV->addFlag("only-exploits", array("alias" => "-e", "default" => false));
		self::$ARGV->addFlag("only-functions", array("alias" => "-f", "default" => false));
		self::$ARGV->addFlag("whitelist-only-path", array("default" => false));
		self::$ARGV->addArgument("path", array("var_args" => true, "default" => ""));
		self::$ARGV->parse();

		// Help
		if(isset(self::$ARGV['help']) && self::$ARGV['help']) {
			Console::helper();
		}

		// Check if only scanner
		if(isset(self::$ARGV['scan']) && self::$ARGV['scan']) {
			$_REQUEST['scan'] = true;
		} else {
			$_REQUEST['scan'] = false;
		}

		// Write logs
		if(isset(self::$ARGV['log']) && self::$ARGV['log']) {
			$_REQUEST['log'] = true;
		} else {
			$_REQUEST['log'] = false;
		}

		// Check on whitelist only file path and not line number
		if(isset(self::$ARGV['whitelist-only-path']) && self::$ARGV['whitelist-only-path']) {
			$_REQUEST['whitelist-only-path'] = true;
		} else {
			$_REQUEST['whitelist-only-path'] = false;
		}

		// Check Filter exploits
		if(isset(self::$ARGV['exploits']) && self::$ARGV['exploits']) {
			if(is_string(self::$ARGV['exploits'])) {
				$filtered = str_replace(array("\n", "\r", "\t", " "), "", self::$ARGV['exploits']);
				$filtered = @explode(',', $filtered);
				if(!empty($filtered) && count($filtered) > 0) {
					foreach(Definitions::$EXPLOITS as $key => $value) {
						if(in_array($key, $filtered)) {
							self::$EXPLOITS[$key] = $value;
						}
					}
					if(!empty(self::$EXPLOITS) && count(self::$EXPLOITS) > 0) {
						Console::write("Exploit to search: " . implode(', ', array_keys(self::$EXPLOITS)) . Console::eol(1));
					} else {
						self::$EXPLOITS = array();
					}
				}
			}
		}

		// Check if exploit mode is enabled
		if(isset(self::$ARGV['only-exploits']) && self::$ARGV['only-exploits']) {
			$_REQUEST['exploits'] = true;
		} else {
			$_REQUEST['exploits'] = false;
		}

		// Check functions to search
		if(isset(self::$ARGV['functions']) && self::$ARGV['functions']) {
			if(is_string(self::$ARGV['functions'])) {
				self::$FUNCTIONS = str_replace(array("\n", "\r", "\t", " "), "", self::$ARGV['functions']);
				self::$FUNCTIONS = @explode(',', self::$FUNCTIONS);
				if(!empty(self::$FUNCTIONS) && count(self::$FUNCTIONS) > 0) {
					Console::write("Functions to search: " . implode(', ', self::$FUNCTIONS) . Console::eol(1));
				} else {
					$FUNCTIONS = array();
				}
			}
		}

		// Check if functions mode is enabled
		if(isset(self::$ARGV['only-functions']) && self::$ARGV['only-functions']) {
			$_REQUEST['functions'] = true;
		} else {
			$_REQUEST['functions'] = false;
		}

		// Check if agile scan is enabled
		if(isset(self::$ARGV['agile']) && self::$ARGV['agile']) {
			self::$EXPLOITS                            = Definitions::$EXPLOITS;
			$_REQUEST['exploits']                      = true;
			self::$EXPLOITS['execution']               = '/\b(eval|assert|passthru|exec|include|system|pcntl_exec|shell_exec|`|array_map|ob_start|call_user_func(_array)?)\s*\(\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/';
			self::$EXPLOITS['concat_vars_with_spaces'] = '/(\$([a-zA-Z0-9]+)[\s\r\n]*\.[\s\r\n]*){8}/';  // concatenation of more than 8 words, with spaces
			self::$EXPLOITS['concat_vars_array']       = '/(\$([a-zA-Z0-9]+)(\{|\[)([0-9]+)(\}|\])[\s\r\n]*\.[\s\r\n]*){8}.*?(?=\})\}/i'; // concatenation of more than 8 words, with spaces
			unset(self::$EXPLOITS['nano'], self::$EXPLOITS['double_var2']);
		}

		// Check if logs and scan at the same time
		if(isset(self::$ARGV['log']) && self::$ARGV['log'] && isset(self::$ARGV['scan']) && self::$ARGV['scan']) {
			unset($_REQUEST['log']);
		}

		// Check for path or functions as first argument
		$arg = self::$ARGV->arg(0);
		if(!empty($arg)) {
			$path = trim($arg);
			if(file_exists(realpath($path))) {
				self::$SCAN_PATH = realpath($path);
			}
		}

		// Check path
		if(!is_dir(self::$SCAN_PATH)) {
			self::$SCAN_PATH = pathinfo(self::$SCAN_PATH, PATHINFO_DIRNAME);
		}

	}

	/**
	 * Init application modes
	 */
	private function modes() {

		if($_REQUEST['functions'] && $_REQUEST['exploits']) {
			Console::write('Can\'t be set both flags --only-functions and --only-functions together!');
			die(Console::eol(2));
		}

		// Malware Definitions
		if($_REQUEST['functions'] || !$_REQUEST['exploits'] && empty(self::$FUNCTIONS)) {
			// Functions to search
			self::$FUNCTIONS = Definitions::$FUNCTIONS;
		} else if($_REQUEST['exploits']) {
			self::$FUNCTIONS = array();
			if(!self::$ARGV['agile']) {
				Console::write("Exploits mode enabled" . Console::eol(1));
			}
		} else {
			Console::write("No functions to search" . Console::eol(1));
		}

		// Exploits to search
		if(!$_REQUEST['functions'] && empty(self::$EXPLOITS)) {
			self::$EXPLOITS = Definitions::$EXPLOITS;
		}

		if(self::$ARGV['agile']) {
			Console::write("Agile mode enabled" . Console::eol(1));
		}

		if($_REQUEST['scan']) {
			Console::write("Scan mode enabled" . Console::eol(1));
		}

		if($_REQUEST['functions']) {
			self::$EXPLOITS = array();
		}

		if($_REQUEST['exploits']) {
			self::$FUNCTIONS = array();
		}
	}

	/**
	 * Map files
	 * @return \CallbackFilterIterator
	 */
	public function mapping() {
		// Mapping files
		$directory = new \RecursiveDirectoryIterator(self::$SCAN_PATH);
		$files     = new \RecursiveIteratorIterator($directory);
		$iterator  = new \CallbackFilterIterator($files, function($cur, $key, $iter) {
			return ($cur->isFile() && in_array($cur->getExtension(), Application::$SCAN_EXTENSIONS));
		});

		return $iterator;
	}

	/**
	 * Detect infected favicon
	 * @param $file
	 * @return bool
	 */
	public static function isInfectedFavicon($file) {
		// Case favicon_[random chars].ico
		$_FILE_NAME      = $file->getFilename();
		$_FILE_EXTENSION = $file->getExtension();

		return (((strpos($_FILE_NAME, 'favicon_') === 0) && ($_FILE_EXTENSION === 'ico') && (strlen($_FILE_NAME) > 12)) || preg_match('/^\.[\w]+\.ico/i', trim($_FILE_NAME)));
	}

	/**
	 * Scan file
	 * @param $info
	 * @return array
	 */
	public function scanFile($info) {

		$_FILE_PATH = $info->getPathname();

		$is_favicon    = self::isInfectedFavicon($info);
		$pattern_found = array();

		$fc          = file_get_contents($_FILE_PATH);
		$fc_clean    = php_strip_whitespace($_FILE_PATH);
		$fc_filtered = $this->filterCode($fc_clean);

		// Scan exploits
		foreach(self::$EXPLOITS as $key => $pattern) {
			$last_match        = null;
			$match_description = null;
			if(@preg_match($pattern, $fc, $match, PREG_OFFSET_CAPTURE) || // Original
			   @preg_match($pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE) || // No comments
			   @preg_match($pattern, $fc_filtered, $match, PREG_OFFSET_CAPTURE)) { // Filtered
				$last_match        = $match[0][0];
				$match_description = $key . "\n => " . $last_match;
			}
			if(!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/i', $fc, $match, PREG_OFFSET_CAPTURE)) {
				$lineNumber        = count(explode("\n", substr($fc, 0, $match[0][1])));
				$match_description = $key . " [line " . $lineNumber . "]\n => " . $last_match;
			}
			if(!empty($match_description)) {
				$pattern_found[$match_description] = $pattern;
			}
		}
		unset($last_match, $match_description, $lineNumber, $match);

		// Scan php commands
		foreach(self::$FUNCTIONS as $_func) {
			$last_match        = null;
			$match_description = null;
			$func              = preg_quote(trim($_func), '/');
			// Search on filtered content
			$regex_pattern        = "/(?:^|[^a-zA-Z0-9_]+)(" . $func . "[\s\r\n]*\((?<=\().*?(?=\))\))/si";
			$regex_pattern_base64 = "/" . base64_encode($_func) . "/s";
			if(@preg_match($regex_pattern, $fc_filtered, $match, PREG_OFFSET_CAPTURE) ||
			   @preg_match($regex_pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE) ||
			   @preg_match($regex_pattern_base64, $fc_filtered, $match, PREG_OFFSET_CAPTURE) ||
			   @preg_match($regex_pattern_base64, $fc_clean, $match, PREG_OFFSET_CAPTURE)) {
				$last_match        = explode($_func, $match[0][0]);
				$last_match        = $_func . $last_match[1];
				$match_description = $func . "\n => " . $last_match;
			}
			if(!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
				$lineNumber        = count(explode("\n", substr($fc, 0, $match[0][1])));
				$match_description = $func . " [line " . $lineNumber . "]\n => " . $last_match;
			}
			if(!empty($match_description)) {
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

		if($is_favicon) {
			$pattern_found['infected_icon'] = '';
		}

		return $pattern_found;
	}

	/**
	 * Filter clean and improve file content
	 * @param $fc
	 * @return string
	 */
	private function filterCode($fc) {
		$fc_filtered = preg_replace("/<\?php(.*?)(?!\B\"[^\"]*)\?>(?![^\"]*\"\B)/si", "$1", $fc); // Only php code
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
		$decoders        = array(
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
		foreach($decoders as $decoder) {
			$pattern_decoder[] = preg_quote($decoder, '/');
		}
		$last_match     = null;
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
					$value       = str_replace('"', "'", $value);
					$value       = '"' . $value . '"';
					$fc_filtered = str_replace($match[0], $value, $fc_filtered);
				} else {
					$recursive_loop = false;
				}
			} else {
				$recursive_loop = false;
			}
		} while(!empty($match[0]) && $recursive_loop);
		unset($last_match, $recursive_loop, $value, $match, $decoders_found, $decoders, $pattern_decoder, $encoded_match);

		return $fc_filtered;
	}

	/**
	 * Run scanner
	 * @param $iterator
	 */
	private function scan($iterator) {

		$files_count = iterator_count($iterator);

		// Scanning
		foreach($iterator as $info) {

			Console::progress(self::$summary_scanned, $files_count);

			$_FILE_PATH      = $info->getPathname();
			$_FILE_EXTENSION = $info->getExtension();

			$is_favicon = self::isInfectedFavicon($info);

			if((in_array($_FILE_EXTENSION, self::$SCAN_EXTENSIONS) &&
			    (!file_exists(self::$PATH_QUARANTINE) || strpos(realpath($_FILE_PATH), realpath(self::$PATH_QUARANTINE)) === false)
				   /*&& (strpos($filename, '-') === FALSE)*/)
			   || $is_favicon) {

				$pattern_found = $this->scanFile($info);

				// Check whitelist
				$pattern_found = array_unique($pattern_found);
				$in_whitelist  = 0;
				foreach(self::$WHITELIST as $item) {
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

				// Scan finished

				self::$summary_scanned ++;
				usleep(10);

				if(realpath($_FILE_PATH) != realpath(__FILE__) && ($is_favicon || !empty($pattern_found)) && ($in_whitelist === 0 || $in_whitelist != count($pattern_found))) {
					self::$summary_detected ++;
					if($_REQUEST['scan']) {

						// Scan mode only

						self::$summary_ignored[] = $_FILE_PATH;
						continue;
					} else {

						// Scan with code check

						$_WHILE       = true;
						$last_command = '0';
						Console::display(Console::eol(2));
						Console::write(Console::eol(1));
						Console::write("PROBABLE MALWARE FOUND!", 'red');

						while($_WHILE) {
							$fc            = file_get_contents($_FILE_PATH);
							$preview_lines = explode(Console::eol(1), trim($fc));
							$preview       = implode(Console::eol(1), array_slice($preview_lines, 0, 1000));
							if(!in_array($last_command, array('4', '5', '7'))) {
								Console::write(Console::eol(1) . "$_FILE_PATH", 'yellow');
								Console::write(Console::eol(2));
								Console::write("========================================== PREVIEW ===========================================", 'white', 'red');
								Console::write(Console::eol(2));
								Console::code($preview, $pattern_found);
								if(count($preview_lines) > 1000) {
									Console::write(Console::eol(2));
									Console::write('  [ ' . (count($preview_lines) - 1000) . ' More lines ]');
								}
								Console::write(Console::eol(2));
								Console::write("==============================================================================================", 'white', 'red');
							}
							Console::write(Console::eol(2));
							Console::write("File path: " . $_FILE_PATH, 'yellow');
							Console::write("\n");
							Console::write("Exploit: " . Console::eol(1) . implode(Console::eol(1), array_keys($pattern_found)), 'red');
							Console::display(Console::eol(2));
							Console::display("OPTIONS:" . Console::eol(2));
							Console::display("    [1] Delete file" . Console::eol(1));
							Console::display("    [2] Move to quarantine" . Console::eol(1));
							Console::display("    [3] Try remove evil code" . Console::eol(1));
							Console::display("    [4] Open with vim" . Console::eol(1));
							Console::display("    [5] Open with nano" . Console::eol(1));
							Console::display("    [6] Add to whitelist" . Console::eol(1));
							Console::display("    [7] Show source" . Console::eol(1));
							Console::display("    [-] Ignore" . Console::eol(2));
							$confirmation = Console::read("What is your choice? ", "purple");
							Console::display(Console::eol(1));

							$last_command = $confirmation;
							unset($preview_lines, $preview);

							if(in_array($confirmation, array('1'))) {

								// Remove file

								Console::write("File path: " . $_FILE_PATH . Console::eol(1), 'yellow');
								$confirm2 = Console::read("Want delete this file [y|N]? ", "purple");
								Console::display(Console::eol(1));
								if($confirm2 == 'y') {
									unlink($_FILE_PATH);
									self::$summary_removed[] = $_FILE_PATH;
									Console::write("File '$_FILE_PATH' removed!" . Console::eol(2), 'green');
									$_WHILE = false;
								}
							} else if(in_array($confirmation, array('2'))) {

								// Move to quarantine

								$quarantine = self::$PATH_QUARANTINE . str_replace(realpath(__DIR__), '', $_FILE_PATH);

								if(!is_dir(dirname($quarantine))) {
									mkdir(dirname($quarantine), 0755, true);
								}
								rename($_FILE_PATH, $quarantine);
								self::$summary_quarantine[] = $quarantine;
								Console::write("File '$_FILE_PATH' moved to quarantine!" . Console::eol(2), 'green');
								$_WHILE = false;
							} else if(in_array($confirmation, array('3')) && count($pattern_found) > 0) {

								// Remove evil code

								foreach($pattern_found as $pattern) {
									preg_match($pattern, $fc, $string_match);
									preg_match('/(<\?php)(.*?)(' . preg_quote($string_match[0], '/') . '\s*\;?)(.*?)((?!\B"[^"]*)\?>(?![^"]*"\B)|.*?$)/si', $fc, $match);
									$match[2] = trim($match[2]);
									$match[4] = trim($match[4]);
									if(!empty($match[2]) || !empty($match[4])) {
										$fc = str_replace($match[0], $match[1] . $match[2] . $match[4] . $match[5], $fc);
									} else {
										$fc = str_replace($match[0], '', $fc);
									}
									$fc = preg_replace('/<\?php[\s\r\n]*\?\>/si', '', $fc);
								}
								Console::write(Console::eol(1));
								Console::write("========================================== SANITIZED ==========================================", 'black', 'green');
								Console::write(Console::eol(2));
								Console::code($fc);
								Console::write(Console::eol(2));
								Console::write("===============================================================================================", 'black', 'green');
								Console::display(Console::eol(2));
								Console::display("File sanitized, now you must verify if has been fixed correctly." . Console::eol(2), "yellow");
								$confirm2 = Console::read("Confirm and save [y|N]? ", "purple");
								Console::display(Console::eol(1));
								if($confirm2 == 'y') {
									Console::write("File '$_FILE_PATH' sanitized!" . Console::eol(2), 'green');
									file_put_contents($_FILE_PATH, $fc);
									self::$summary_removed[] = $_FILE_PATH;
									$_WHILE                  = false;
								} else {
									self::$summary_ignored[] = $_FILE_PATH;
								}
							} else if(in_array($confirmation, array('4'))) {

								// Edit with vim

								$descriptors = array(
									array('file', '/dev/tty', 'r'),
									array('file', '/dev/tty', 'w'),
									array('file', '/dev/tty', 'w')
								);
								$process     = proc_open("vim '$_FILE_PATH'", $descriptors, $pipes);
								while(true) {
									$proc_status = proc_get_status($process);
									if($proc_status['running'] == false) {
										break;
									}
								}
								self::$summary_edited[] = $_FILE_PATH;
								Console::write("File '$_FILE_PATH' edited with vim!" . Console::eol(2), 'green');
								self::$summary_removed[] = $_FILE_PATH;
							} else if(in_array($confirmation, array('5'))) {

								// Edit with nano

								$descriptors = array(
									array('file', '/dev/tty', 'r'),
									array('file', '/dev/tty', 'w'),
									array('file', '/dev/tty', 'w')
								);
								$process     = proc_open("nano -c '$_FILE_PATH'", $descriptors, $pipes);
								while(true) {
									$proc_status = proc_get_status($process);
									if($proc_status['running'] == false) {
										break;
									}
								}
								$summary_edited[] = $_FILE_PATH;
								Console::write("File '$_FILE_PATH' edited with nano!" . Console::eol(2), 'green');
								self::$summary_removed[] = $_FILE_PATH;
							} else if(in_array($confirmation, array('6'))) {

								// Add to whitelist

								foreach($pattern_found as $key => $pattern) {
									$exploit           = preg_replace("/^(\S+) \[line [0-9]+\].*/si", "$1", $key);
									$lineNumber        = preg_replace("/^\S+ \[line ([0-9]+)\].*/si", "$1", $key);
									self::$WHITELIST[] = array(realpath($_FILE_PATH), $exploit, $lineNumber);
								}
								self::$WHITELIST = array_map("unserialize", array_unique(array_map("serialize", self::$WHITELIST)));
								if(CSV::write(self::$PATH_WHITELIST, self::$WHITELIST)) {
									self::$summary_whitelist[] = $_FILE_PATH;
									Console::write("Exploits of file '$_FILE_PATH' added to whitelist!" . Console::eol(2), 'green');
									$_WHILE = false;
								} else {
									Console::write("Exploits of file '$_FILE_PATH' failed adding file to whitelist! Check write permission of '" . self::$PATH_WHITELIST . "' file!" . Console::eol(2), 'red');
								}
							} else if(in_array($confirmation, array('7'))) {

								// Show source code

								Console::write(Console::eol(1) . "$_FILE_PATH", 'yellow');
								Console::write(Console::eol(2));
								Console::write("=========================================== SOURCE ===========================================", 'white', 'red');
								Console::write(Console::eol(2));
								Console::code($fc, $pattern_found);
								Console::write(Console::eol(2));
								Console::write("==============================================================================================", 'white', 'red');
							} else {

								// None

								Console::write("File '$_FILE_PATH' skipped!" . Console::eol(2), 'green');
								self::$summary_ignored[] = $_FILE_PATH;
								$_WHILE                  = false;
							}

							Console::write(Console::eol(1));
						}
						unset($fc);
					}
				}
			}
		}
	}

	/**
	 * Print summary
	 */
	private function summary() {
		// Statistics
		Console::write("                SUMMARY                ", 'black', 'cyan');
		Console::write(Console::eol(2));
		Console::write("Files scanned: " . self::$summary_scanned . Console::eol(1));
		if(!$_REQUEST['scan']) {
			self::$summary_ignored = array_unique(self::$summary_ignored);
			self::$summary_edited  = array_unique(self::$summary_edited);
			Console::write("Files edited: " . count(self::$summary_edited) . Console::eol(1));
			Console::write("Files quarantined: " . count(self::$summary_quarantine) . Console::eol(1));
			Console::write("Files whitelisted: " . count(self::$summary_whitelist) . Console::eol(1));
			Console::write("Files ignored: " . count(self::$summary_ignored) . Console::eol(2));
		}
		Console::write("Malware detected: " . self::$summary_detected . Console::eol(1));
		if(!$_REQUEST['scan']) {
			Console::write("Malware removed: " . count(self::$summary_removed) . Console::eol(1));
		}

		if($_REQUEST['scan']) {
			Console::write(Console::eol(1) . "Files infected: '" . __PATH_LOGS_INFECTED__ . "'" . Console::eol(1), 'red');
			file_put_contents(__PATH_LOGS_INFECTED__, "Log date: " . date("d-m-Y H:i:s") . Console::eol(1) . implode(Console::eol(1), self::$summary_ignored));
			Console::write(Console::eol(2));
		} else {
			if(count(self::$summary_removed) > 0) {
				Console::write(Console::eol(1) . "Files removed:" . Console::eol(1), 'red');
				foreach(self::$summary_removed as $un) {
					Console::write($un . Console::eol(1));
				}
			}
			if(count(self::$summary_edited) > 0) {
				Console::write(Console::eol(1) . "Files edited:" . Console::eol(1), 'green');
				foreach(self::$summary_edited as $un) {
					Console::write($un . Console::eol(1));
				}
			}
			if(count(self::$summary_quarantine) > 0) {
				Console::write(Console::eol(1) . "Files quarantined:" . Console::eol(1), 'yellow');
				foreach(self::$summary_ignored as $un) {
					Console::write($un . Console::eol(1));
				}
			}
			if(count(self::$summary_whitelist) > 0) {
				Console::write(Console::eol(1) . "Files whitelisted:" . Console::eol(1), 'cyan');
				foreach(self::$summary_whitelist as $un) {
					Console::write($un . Console::eol(1));
				}
			}
			if(count(self::$summary_ignored) > 0) {
				Console::write(Console::eol(1) . "Files ignored:" . Console::eol(1), 'cyan');
				foreach(self::$summary_ignored as $un) {
					Console::write($un . Console::eol(1));
				}
			}
			Console::write(Console::eol(2));
		}
	}
}