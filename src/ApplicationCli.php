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
 * Class Application
 * @package marcocesarato\amwscan
 */
class ApplicationCli {

	public static $NAME = "amwscan";
	public static $VERSION = "0.5.0";
	public static $ROOT = "./";
	public static $PATH_QUARANTINE = "/quarantine/";
	public static $PATH_LOGS = "/scanner.log";
	public static $PATH_LOGS_INFECTED = "/scanner_infected.log";
	public static $PATH_WHITELIST = "/scanner_whitelist.csv";

	public static $MAX_FILESIZE = - 1;

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
	private function init() {
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

		Definitions::optimizeSig(Definitions::$SIGNATURES);
	}

	/**
	 * Run application
	 */
	public function run() {
		try {
			if(function_exists('gc_enable') && (function_exists('gc_enable') && !gc_enabled())) {
				gc_enable();
			}
			// Print header
			Console::header();
			// Initialize
			$this->init();
			// Initialize arguments
			$this->arguments();
			// Initialize modes
			$this->modes();

			// Start scanning
			Console::displayLine("Start scanning...");

			Console::writeLine("Scan date: " . date("d-m-Y H:i:s"));
			Console::writeLine("Scanning " . self::$SCAN_PATH, 2);

			// Mapping files
			Console::writeLine("Mapping files...");
			$iterator = $this->mapping();

			// Counting files
			$files_count = iterator_count($iterator);
			Console::writeLine("Found " . $files_count . " files", 2);
			Console::writeLine("Checking files...", 2);
			Console::progress(0, $files_count);

			// Scan all files
			$this->scan($iterator);

			// Scan finished
			Console::writeBreak(2);
			Console::write("Scan finished!", 'green');
			Console::writeBreak(3);

			// Print summary
			$this->summary();
		} catch(\Exception $e) {
			Console::writeBreak();
			Console::writeLine($e->getMessage(), 1, 'red');
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
		self::$ARGV->addFlag("log", array("alias" => "-l", "default" => null, "has_value" => true));
		self::$ARGV->addFlag("report", array("alias" => "-r", "default" => false));
		self::$ARGV->addFlag("version", array("alias" => "-v", "default" => false));
		self::$ARGV->addFlag("update", array("alias" => "-u", "default" => false));
		self::$ARGV->addFlag("only-signatures", array("alias" => "-s", "default" => false));
		self::$ARGV->addFlag("only-exploits", array("alias" => "-e", "default" => false));
		self::$ARGV->addFlag("only-functions", array("alias" => "-f", "default" => false));
		self::$ARGV->addFlag("list", array("default" => false));
		self::$ARGV->addFlag("list-exploits", array("default" => false));
		self::$ARGV->addFlag("list-functions", array("default" => false));
		self::$ARGV->addFlag("exploits", array("default" => false, "has_value" => true));
		self::$ARGV->addFlag("functions", array("default" => false, "has_value" => true));
		self::$ARGV->addFlag("whitelist-only-path", array("default" => false));
		self::$ARGV->addFlag("max-filesize", array("default" => - 1, "has_value" => true));
		self::$ARGV->addArgument("path", array("var_args" => true, "default" => ""));
		self::$ARGV->parse();

		// Version
		if(isset(self::$ARGV['version']) && self::$ARGV['version']) {
			die();
		}

		// Help
		if(isset(self::$ARGV['help']) && self::$ARGV['help']) {
			Console::helper();
		}

		// List exploits
		if(isset(self::$ARGV['list']) && self::$ARGV['list']) {
			Console::helplist();
		}

		// List exploits
		if(isset(self::$ARGV['list-exploits']) && self::$ARGV['list-exploits']) {
			Console::helplist("exploits");
		}

		// List functions
		if(isset(self::$ARGV['list-functions']) && self::$ARGV['list-functions']) {
			Console::helplist("functions");
		}

		// Update
		if(isset(self::$ARGV['update']) && self::$ARGV['update']) {
			self::update();
		}

		// Check if only scanner
		if(isset(self::$ARGV['report']) && self::$ARGV['report']) {
			$_REQUEST['report'] = true;
		} else {
			$_REQUEST['report'] = false;
		}

		// Max filesize
		if(isset(self::$ARGV['max-filesize']) && self::$ARGV['max-filesize']) {
			self::$MAX_FILESIZE = trim(self::$ARGV['max-filesize']);
			if(!is_numeric(self::$ARGV['max-filesize'])) {
				self::$MAX_FILESIZE = self::convertToBytes(self::$MAX_FILESIZE);
			}
		}

		// Write logs
		if(isset(self::$ARGV['log']) && !empty(self::$ARGV['log'])) {
			$_REQUEST['log'] = true;
			if(is_string(self::$ARGV['log'])) {
				self::$PATH_LOGS = self::$ARGV['log'];
			}
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
						Console::writeLine("Exploit to search: " . implode(', ', array_keys(self::$EXPLOITS)));
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
					Console::writeLine("Functions to search: " . implode(', ', self::$FUNCTIONS));
				} else {
					self::$FUNCTIONS = array();
				}
			}
		}

		// Check if functions mode is enabled
		if(isset(self::$ARGV['only-functions']) && self::$ARGV['only-functions']) {
			$_REQUEST['functions'] = true;
		} else {
			$_REQUEST['functions'] = false;
		}

		// Check if only signatures mode is enabled
		if(isset(self::$ARGV['only-signatures'])) {
			$_REQUEST['signatures'] = true;
			$_REQUEST['exploits']   = false;
			$_REQUEST['functions']  = false;
		} else {
			$_REQUEST['signatures'] = false;
		}

		// Check if agile scan is enabled
		if(isset(self::$ARGV['agile']) && self::$ARGV['agile']) {
			$_REQUEST['agile'] = true;
			self::$EXPLOITS                            = Definitions::$EXPLOITS;
			$_REQUEST['exploits']                      = true;
			self::$EXPLOITS['execution']               = '/\b(eval|assert|passthru|exec|include|system|pcntl_exec|shell_exec|`|array_map|ob_start|call_user_func(_array)?)\s*\(\s*(base64_decode|php:\/\/input|str_rot13|gz(inflate|uncompress)|getenv|pack|\\?\$_(GET|REQUEST|POST|COOKIE|SERVER)).*?(?=\))\)/';
			self::$EXPLOITS['concat_vars_with_spaces'] = '/(\$([a-zA-Z0-9]+)[\s\r\n]*\.[\s\r\n]*){8}/';  // concatenation of more than 8 words, with spaces
			self::$EXPLOITS['concat_vars_array']       = '/(\$([a-zA-Z0-9]+)(\{|\[)([0-9]+)(\}|\])[\s\r\n]*\.[\s\r\n]*){8}.*?(?=\})\}/i'; // concatenation of more than 8 words, with spaces
			unset(self::$EXPLOITS['nano'], self::$EXPLOITS['double_var2'], self::$EXPLOITS['base64_long']);
		} else {
			$_REQUEST['agile'] = false;
		}

		// Check if logs and scan at the same time
		if(isset(self::$ARGV['log']) && self::$ARGV['log'] && isset(self::$ARGV['report']) && self::$ARGV['report']) {
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

		if($_REQUEST['functions'] && $_REQUEST['signatures'] && $_REQUEST['exploits']) {
			Console::writeLine('Can\'t be set flags --only-signatures, --only-functions and --only-exploits together!', 2);
			die();
		}

		if($_REQUEST['functions'] && $_REQUEST['signatures']) {
			Console::writeLine('Can\'t be set both flags --only-signatures and --only-functions together!', 2);
			die();
		}

		if($_REQUEST['signatures'] && $_REQUEST['exploits']) {
			Console::writeLine('Can\'t be set both flags --only-signatures and --only-exploits together!', 2);
			die();
		}

		if($_REQUEST['functions'] && $_REQUEST['exploits']) {
			Console::writeLine('Can\'t be set both flags --only-functions and --only-exploits together!', 2);
			die();
		}

		// Malware Definitions
		if($_REQUEST['functions'] || !$_REQUEST['exploits'] && empty(self::$FUNCTIONS)) {
			// Functions to search
			self::$FUNCTIONS = Definitions::$FUNCTIONS;
		} else if($_REQUEST['exploits']) {
			self::$FUNCTIONS = array();
			if(!$_REQUEST['agile']) {
				Console::writeLine("Exploits mode enabled");
			}
		} else {
			Console::writeLine("No functions to search");
		}

		if(self::$ARGV['max-filesize'] > 0) {
			Console::writeLine("Max filesize: ".self::$MAX_FILESIZE . " bytes", 2);
		}

		// Exploits to search
		if(!$_REQUEST['functions'] && empty(self::$EXPLOITS)) {
			self::$EXPLOITS = Definitions::$EXPLOITS;
		}

		if($_REQUEST['agile']) {
			Console::writeLine("Agile mode enabled");
		}

		if($_REQUEST['signatures']) {
			Console::writeLine("Signatures mode enabled");
		}

		if($_REQUEST['report']) {
			Console::writeLine("Report scan mode enabled");
		}

		if($_REQUEST['functions']) {
			self::$EXPLOITS = array();
		}

		if($_REQUEST['exploits']) {
			self::$FUNCTIONS = array();
		}

		if($_REQUEST['signatures']) {
			self::$EXPLOITS  = array();
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

		$mime_type = 'text/php';
		if(function_exists('mime_content_type')) {
			$mime_type = mime_content_type($_FILE_PATH);
		} elseif(function_exists('finfo_open')) {
			$finfo     = finfo_open(FILEINFO_MIME);
			$mime_type = finfo_file($finfo, $_FILE_PATH);
			finfo_close($finfo);
		}

		if(preg_match("/^text/i", $mime_type)) {

			$deobfuctator = new Deobfuscator();

			$fc              = file_get_contents($_FILE_PATH);
			$fc_clean        = php_strip_whitespace($_FILE_PATH);
			$fc_deobfuscated = $deobfuctator->deobfuscate($fc);
			$fc_decoded      = $deobfuctator->decode($fc_deobfuscated);

			// Scan exploits
			$last_match = null;
			foreach(self::$EXPLOITS as $key => $pattern) {
				$match_description = null;
				$lineNumber        = null;
				if(@preg_match($pattern, $fc, $match, PREG_OFFSET_CAPTURE) || // Original
				   @preg_match($pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE) || // No comments
				   @preg_match($pattern, $fc_decoded, $match, PREG_OFFSET_CAPTURE)) { // Decoded
					$last_match        = $match[0][0];
					$match_description = $key . "\n => " . $last_match;
					if(!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/i', $fc, $match, PREG_OFFSET_CAPTURE)) {
						$lineNumber        = count(explode("\n", substr($fc, 0, $match[0][1])));
						$match_description = $key . " [line " . $lineNumber . "]\n => " . $last_match;
					}
					if(!empty($match_description)) {
						//$pattern_found[$match_description] = $pattern;
						$pattern_found[$match_description] = array(
							"key"     => $key,
							"line"    => $lineNumber,
							"pattern" => $pattern,
							"match"   => $last_match
						);
					}
				}
			}
			unset($last_match, $match_description, $lineNumber, $match);

			// Scan php commands
			$last_match = null;
			foreach(self::$FUNCTIONS as $_func) {
				$match_description = null;
				$func              = preg_quote(trim($_func), '/');
				// Basic search
				$regex_pattern = "/(?:^|[\s\r\n]+|[^a-zA-Z0-9_>]+)(" . $func . "[\s\r\n]*\((?<=\().*?(?=\))\))/si";
				if(@preg_match($regex_pattern, $fc_decoded, $match, PREG_OFFSET_CAPTURE) ||
				   @preg_match($regex_pattern, $fc_clean, $match, PREG_OFFSET_CAPTURE)) {
					$last_match        = explode($_func, $match[0][0]);
					$last_match        = $_func . $last_match[1];
					$match_description = $_func . "\n => " . $last_match;
					if(!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
						$lineNumber        = count(explode("\n", substr($fc, 0, $match[0][1])));
						$match_description = $_func . " [line " . $lineNumber . "]\n => " . $last_match;
					}
					if(!empty($match_description)) {
						$pattern_found[$match_description] = array(
							"key"     => $_func,
							"line"    => $lineNumber,
							"pattern" => $regex_pattern,
							"match"   => $last_match
						);
					}
				}
				// Check of base64
				$regex_pattern_base64 = "/" . base64_encode($_func) . "/s";
				if(@preg_match($regex_pattern_base64, $fc_decoded, $match, PREG_OFFSET_CAPTURE) ||
				   @preg_match($regex_pattern_base64, $fc_clean, $match, PREG_OFFSET_CAPTURE)) {
					$last_match        = explode($_func, $match[0][0]);
					$last_match        = $_func . $last_match[1];
					$match_description = $_func . "_base64\n => " . $last_match;

					if(!empty($last_match) && @preg_match('/' . preg_quote($last_match, '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
						$lineNumber        = count(explode("\n", substr($fc, 0, $match[0][1])));
						$match_description = $_func . "_base64 [line " . $lineNumber . "]\n => " . $last_match;
					}
					if(!empty($match_description)) {
						$pattern_found[$match_description] = array(
							"key"     => $_func . "_base64",
							"line"    => $lineNumber,
							"pattern" => $regex_pattern_base64,
							"match"   => $last_match
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

			foreach(Definitions::$SIGNATURES as $key => $pattern) {
				$regex_pattern = '#' . $pattern . '#smiS';
				if(preg_match($regex_pattern, $fc_deobfuscated, $match, PREG_OFFSET_CAPTURE)) {
					$last_match = $match[0][0];
					if(!empty($last_match) && @preg_match('/' . preg_quote($match[0][0], '/') . '/', $fc, $match, PREG_OFFSET_CAPTURE)) {
						$lineNumber        = count(explode("\n", substr($fc, 0, $match[0][1])));
						$match_description = "Sign " . $key . " [line " . $lineNumber . "]\n => " . $last_match;
					}
					if(!empty($match_description)) {
						$pattern_found[$match_description] = array(
							"key"     => $key,
							"line"    => $lineNumber,
							"pattern" => $regex_pattern,
							"match"   => $last_match
						);
					}
				}
			}

			unset($fc, $fc_decoded, $fc_clean, $fc_deobfuscated);
		}

		if($is_favicon) {
			$pattern_found['infected_icon'] = array(
				"key"     => "infected_icon",
				"line"    => "",
				"pattern" => "",
				"match"   => "",
			);
		}

		return $pattern_found;
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
			$_FILE_SIZE      = filesize($_FILE_PATH);

			$is_favicon = self::isInfectedFavicon($info);

			if((in_array($_FILE_EXTENSION, self::$SCAN_EXTENSIONS) &&
			    (self::$MAX_FILESIZE < 1 || $_FILE_SIZE <= self::$MAX_FILESIZE) &&
			    (!file_exists(self::$PATH_QUARANTINE) || strpos(realpath($_FILE_PATH), realpath(self::$PATH_QUARANTINE)) === false)
				   /*&& (strpos($filename, '-') === FALSE)*/) ||
			   $is_favicon) {

				$pattern_found = $this->scanFile($info);

				// Check whitelist
				$in_whitelist = 0;
				foreach(self::$WHITELIST as $item) {
					foreach($pattern_found as $key => $pattern) {
						$lineNumber        = $pattern["line"];
						$exploit           = $pattern["key"];
						$exploit_whitelist = preg_replace("/^(\S+)./si", "$1", trim($item[1]));
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
					if($_REQUEST['report']) {

						// Scan mode only

						self::$summary_ignored[] = $_FILE_PATH;
						continue;
					} else {

						// Scan with code check

						$_WHILE       = true;
						$last_command = '0';
						Console::displayBreak(2);
						Console::writeBreak();
						Console::writeLine("PROBABLE MALWARE FOUND!", 1, 'red');

						while($_WHILE) {
							$fc            = file_get_contents($_FILE_PATH);
							$preview_lines = explode(Console::eol(1), trim($fc));
							$preview       = implode(Console::eol(1), array_slice($preview_lines, 0, 1000));
							if(!in_array($last_command, array('4', '5', '7'))) {
								Console::displayLine("$_FILE_PATH", 2, 'yellow');
								Console::display(Console::title(" PREVIEW ", "="), 'white', 'red');
								Console::displayBreak(2);
								Console::code($preview, $pattern_found);
								if(count($preview_lines) > 1000) {
									Console::displayBreak(2);
									Console::display('  [ ' . (count($preview_lines) - 1000) . ' rows more ]');
								}
								Console::displayBreak(2);
								Console::display(Console::title("", "="), 'white', 'red');
							}
							Console::displayBreak(2);
							Console::writeLine("File path: " . $_FILE_PATH, 1, 'yellow');
							Console::writeLine("Exploits found: " . Console::eol(1) . implode(Console::eol(1), array_keys($pattern_found)), 2, 'red');
							Console::displayLine("OPTIONS:", 2);
							Console::displayOption(1, "Delete file");
							Console::displayOption(2, "Move to quarantine");
							Console::displayOption(3, "Try remove evil code");
							Console::displayOption(4, "Try remove evil line code");
							Console::displayOption(5, "Open/Edit with vim");
							Console::displayOption(6, "Open/Edit with nano");
							Console::displayOption(7, "Add to whitelist");
							Console::displayOption(8, "Show source");
							Console::displayOption("-", "Ignore");
							Console::displayBreak(2);
							$confirmation = Console::read("What is your choice? ", "purple");
							Console::displayBreak();

							$last_command = $confirmation;
							unset($preview_lines, $preview);

							if(in_array($confirmation, array('1'))) {

								// Remove file

								Console::writeLine("File path: " . $_FILE_PATH, 1, 'yellow');
								$confirm2 = Console::read("Want delete this file [y|N]? ", "purple");
								Console::displayBreak();
								if($confirm2 == 'y') {
									unlink($_FILE_PATH);
									self::$summary_removed[] = $_FILE_PATH;
									Console::writeLine("File '$_FILE_PATH' removed!", 2, 'green');
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
								Console::writeLine("File '$_FILE_PATH' moved to quarantine!", 2, 'green');
								$_WHILE = false;
							} else if(in_array($confirmation, array('3')) && count($pattern_found) > 0) {

								// Remove evil code

								foreach($pattern_found as $pattern) {
									preg_match('/(<\?php)(.*?)(' . preg_quote($pattern["match"], '/') . '[\s\r\n]*;?)/si', $fc, $match);
									$match[2] = trim($match[2]);
									$match[4] = trim($match[4]);
									if(!empty($match[2]) || !empty($match[4])) {
										$fc = str_replace($match[0], $match[1] . $match[2] . $match[4] . $match[5], $fc);
									} else {
										$fc = str_replace($match[0], '', $fc);
									}
									$fc = preg_replace('/<\?php[\s\r\n]*\?\>/si', '', $fc);
								}
								Console::displayBreak();
								Console::display(Console::title(" SANITIZED ", "="), 'black', 'green');
								Console::displayBreak(2);
								Console::code($fc);
								Console::displayBreak(2);
								Console::display(Console::title("", "="), 'black', 'green');
								Console::displayBreak(2);
								Console::displayLine("File sanitized, now you must verify if has been fixed correctly.", 2, "yellow");
								$confirm2 = Console::read("Confirm and save [y|N]? ", "purple");
								Console::displayBreak();
								if($confirm2 == 'y') {
									Console::writeLine("File '$_FILE_PATH' sanitized!", 2, 'green');
									file_put_contents($_FILE_PATH, $fc);
									self::$summary_removed[] = $_FILE_PATH;
									$_WHILE                  = false;
								} else {
									self::$summary_ignored[] = $_FILE_PATH;
								}
							} else if(in_array($confirmation, array('4')) && count($pattern_found) > 0) {

								// Remove evil line code

								$fc_expl = explode(PHP_EOL, $fc);
								foreach($pattern_found as $pattern) {
									unset($fc_expl[intval($pattern["line"]) - 1]);
								}
								$fc = implode(PHP_EOL, $fc_expl);

								Console::displayBreak();
								Console::display(Console::title(" SANITIZED ", "="), 'black', 'green');
								Console::displayBreak(2);
								Console::code($fc);
								Console::displayBreak(2);
								Console::display(Console::title("", "="), 'black', 'green');
								Console::displayBreak(2);
								Console::displayLine("File sanitized, now you must verify if has been fixed correctly.", 2, "yellow");
								$confirm2 = Console::read("Confirm and save [y|N]? ", "purple");
								Console::displayBreak();
								if($confirm2 == 'y') {
									Console::writeLine("File '$_FILE_PATH' sanitized!", 2, 'green');
									file_put_contents($_FILE_PATH, $fc);
									self::$summary_removed[] = $_FILE_PATH;
									$_WHILE                  = false;
								} else {
									self::$summary_ignored[] = $_FILE_PATH;
								}
							} else if(in_array($confirmation, array('5'))) {

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
								Console::writeLine("File '$_FILE_PATH' edited with vim!", 2, 'green');
								self::$summary_removed[] = $_FILE_PATH;
							} else if(in_array($confirmation, array('6'))) {

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
								Console::writeLine("File '$_FILE_PATH' edited with nano!", 2, 'green');
								self::$summary_removed[] = $_FILE_PATH;
							} else if(in_array($confirmation, array('7'))) {

								// Add to whitelist

								foreach($pattern_found as $key => $pattern) {
									//$exploit           = preg_replace("/^(\S+) \[line [0-9]+\].*/si", "$1", $key);
									//$lineNumber        = preg_replace("/^\S+ \[line ([0-9]+)\].*/si", "$1", $key);
									$exploit           = $pattern["key"];
									$lineNumber        = $pattern["line"];
									self::$WHITELIST[] = array(realpath($_FILE_PATH), $exploit, $lineNumber);
								}
								self::$WHITELIST = array_map("unserialize", array_unique(array_map("serialize", self::$WHITELIST)));
								if(CSV::write(self::$PATH_WHITELIST, self::$WHITELIST)) {
									self::$summary_whitelist[] = $_FILE_PATH;
									Console::writeLine("Exploits of file '$_FILE_PATH' added to whitelist!", 2, 'green');
									$_WHILE = false;
								} else {
									Console::writeLine("Exploits of file '$_FILE_PATH' failed adding file to whitelist! Check write permission of '" . self::$PATH_WHITELIST . "' file!", 2, 'red');
								}
							} else if(in_array($confirmation, array('8'))) {

								// Show source code

								Console::displayBreak();
								Console::displayLine("$_FILE_PATH", 2, 'yellow');
								Console::display(Console::title(" SOURCE ", "="), 'white', 'red');
								Console::displayBreak(2);
								Console::code($fc, $pattern_found);
								Console::displayBreak(2);
								Console::display(Console::title("", "="), 'white', 'red');
								Console::displayBreak(2);
							} else {

								// None

								Console::writeLine("File '$_FILE_PATH' skipped!", 2, 'green');
								self::$summary_ignored[] = $_FILE_PATH;
								$_WHILE                  = false;
							}

							Console::writeBreak();
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
		Console::displayTitle("SUMMARY", 'black', 'cyan');
		Console::writeBreak();
		Console::writeLine("Files scanned: " . self::$summary_scanned);
		if(!$_REQUEST['report']) {
			self::$summary_ignored = array_unique(self::$summary_ignored);
			self::$summary_edited  = array_unique(self::$summary_edited);
			Console::writeLine("Files edited: " . count(self::$summary_edited));
			Console::writeLine("Files quarantined: " . count(self::$summary_quarantine));
			Console::writeLine("Files whitelisted: " . count(self::$summary_whitelist));
			Console::writeLine("Files ignored: " . count(self::$summary_ignored), 2);
		}
		Console::writeLine("Malware detected: " . self::$summary_detected);
		if(!$_REQUEST['report']) {
			Console::writeLine("Malware removed: " . count(self::$summary_removed));
		}

		if($_REQUEST['report']) {
			Console::writeLine(Console::eol(1) . "Files infected: '" . self::$PATH_LOGS_INFECTED . "'", 1, 'red');
			file_put_contents(self::$PATH_LOGS_INFECTED, "Log date: " . date("d-m-Y H:i:s") . Console::eol(1) . implode(Console::eol(1), self::$summary_ignored));
			Console::writeBreak(2);
		} else {
			if(count(self::$summary_removed) > 0) {
				Console::writeBreak();
				Console::writeLine("Files removed:", 1, 'red');
				foreach(self::$summary_removed as $un) {
					Console::writeLine($un);
				}
			}
			if(count(self::$summary_edited) > 0) {
				Console::writeBreak();
				Console::writeLine("Files edited:", 1, 'green');
				foreach(self::$summary_edited as $un) {
					Console::writeLine($un);
				}
			}
			if(count(self::$summary_quarantine) > 0) {
				Console::writeBreak();
				Console::writeLine("Files quarantined:", 1, 'yellow');
				foreach(self::$summary_ignored as $un) {
					Console::writeLine($un);
				}
			}
			if(count(self::$summary_whitelist) > 0) {
				Console::writeBreak();
				Console::writeLine("Files whitelisted:", 1, 'cyan');
				foreach(self::$summary_whitelist as $un) {
					Console::writeLine($un);
				}
			}
			if(count(self::$summary_ignored) > 0) {
				Console::writeBreak();
				Console::writeLine("Files ignored:", 1, 'cyan');
				foreach(self::$summary_ignored as $un) {
					Console::writeLine($un);
				}
			}
			Console::writeBreak(2);
		}
	}

	/**
	 * Update scanner to last version
	 */
	static function update() {
		Console::writeLine('Checking update...');
		$new_version = file_get_contents('https://raw.githubusercontent.com/marcocesarato/PHP-Antimalware-Scanner/master/dist/scanner');
		if(!empty($new_version)) {
			preg_match('/public\s*static\s*\$VERSION\s*=\s*(?:\"|\\\')(.*?)(?:\"|\\\')\s*\;/si', $new_version, $match);
			$version = trim($match[1]);
			if(version_compare(self::$VERSION, $version, '<')) {
				Console::write('New version');
				Console::write(' ' . $version . ' ');
				Console::writeLine('of the scanner available!', 2);
				$confirm = Console::read('You sure you want update the scanner to the last version [y|N]? ', 'purple');
				Console::writeBreak();
				if(strtolower($confirm) == "y") {
					file_put_contents(__FILE__, $new_version);
					Console::write('Updated to last version');
					Console::write(' (' . self::$VERSION . ' => ' . $version . ') ');
					Console::writeLine('with SUCCESS!', 2);
				} else {
					Console::writeLine('Updated SKIPPED!', 2);
				}
			} else {
				Console::writeLine('You have the last version of the scanner yet!', 2);
			}
		} else {
			Console::writeLine('Update FAILED!', 2, 'red');
		}
		die();
	}

	/**
	 * Convert to Bytes
	 * @param string $from
	 * @return int|null
	 */
	private function convertToBytes($from) {
		$units  = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
		$number = substr($from, 0, - 2);
		$suffix = strtoupper(substr($from, - 2));

		if(is_numeric(substr($suffix, 0, 1))) {
			return preg_replace('/[^\d]/', '', $from);
		}
		$pow = array_flip($units)[$suffix] ? : null;
		if($pow === null) {
			return null;
		}

		return $number * pow(1024, $pow);
	}
}