<?php
/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace AMWScan\Console;

use AMWScan\Exploits;
use AMWScan\Functions;
use AMWScan\Scanner;

/**
 * Class Console
 * Console manager.
 */
class CLI
{
    /**
     * Font colors.
     *
     * @var array
     */
    public static $foregroundColors = [
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
    ];

    /**
     * Background colors.
     *
     * @var array
     */
    public static $backgroundColors = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'light_gray' => '47',
    ];

    /**
     * Max default line length.
     *
     * @var int
     */
    public static $maxLineLength = 80;

    /**
     * Get new line char.
     *
     * @param $n
     *
     * @return string
     */
    public static function eol($n)
    {
        $eol = '';
        for ($i = 0; $i < $n; $i++) {
            $eol .= PHP_EOL;
        }

        return $eol;
    }

    /**
     * Print header.
     */
    public static function header()
    {
        if (!Scanner::isCli()) {
            return;
        }

        $figlet = new Figlet();
        $figlet->loadRandomFont();
        $header = $figlet->render(Scanner::getName());
        $header .= "\n\nGithub: " . Scanner::getRepoUrl();

        $headerArray = explode("\n", $header);
        foreach ($headerArray as $key => $value) {
            $diff = strlen($value) - mb_strlen($value);
            $headerArray[$key] = str_pad($value, self::$maxLineLength + $diff, ' ', STR_PAD_BOTH);
        }
        $header = implode(PHP_EOL, $headerArray);
        $version = Scanner::getVersion();

        self::newLine();
        self::displayLine($header, 2, 'green');
        $title = self::title('version ' . $version);
        self::display($title, 'green');
        self::newLine(2);

        $title = self::title('');
        self::display($title, 'black', 'green');
        self::newLine();

        $title = self::title(Scanner::getFullName());
        self::display($title, 'black', 'green');
        self::newLine();

        $title = self::title('Created by ' . Scanner::getAuthor());
        self::display($title, 'black', 'green');
        self::newLine();

        $title = self::title('');
        self::display($title, 'black', 'green');
        self::newLine(2);
        $version = file_get_contents(Scanner::getLatestVersionUrl());
        if (!empty($version) && version_compare(Scanner::$version, $version, '<')) {
            self::write('New version', 'green');
            self::write(' ' . $version . ' ', 'green');
            self::writeLine('of the scanner available!', 2, 'green');
        }
    }

    /**
     * Print title.
     *
     * @param $text
     * @param string $char
     * @param int $length
     *
     * @return string
     */
    public static function title($text, $char = ' ', $length = null)
    {
        if ($length === null) {
            $length = self::$maxLineLength;
        }
        $result = '';
        $strLength = strlen($text);
        $spaces = $length - $strLength;
        $spacesLenHalf = $spaces / 2;
        $spacesLenLeft = round($spacesLenHalf);
        $spacesLenRight = round($spacesLenHalf);

        if ((round($spacesLenHalf) - $spacesLenHalf) >= 0.5) {
            $spacesLenLeft--;
        }

        for ($i = 0; $i < $spacesLenLeft; $i++) {
            $result .= $char;
        }

        $result .= $text;

        for ($i = 0; $i < $spacesLenRight; $i++) {
            $result .= $char;
        }

        return $result;
    }

    /**
     * Print progress.
     *
     * @param float $done
     * @param float $total
     * @param int $size
     */
    public static function progress($done, $total, $size = 30)
    {
        static $startTime;
        if ($done > $total || $total === 0) {
            return;
        }
        if (empty($startTime)) {
            $startTime = time();
        }
        $now = time();
        $perc = (float)($done / $total);
        $bar = floor($perc * $size);
        $statusBar = "\r[";
        $statusBar .= str_repeat('=', $bar);
        if ($bar < $size) {
            $statusBar .= '>';
            $statusBar .= str_repeat(' ', $size - $bar);
        } else {
            $statusBar .= '=';
        }
        $disp = number_format($perc * 100);
        $statusBar .= "] $disp%";
        $rate = $done !== 0.0 ? ($now - $startTime) / max(1, $done) : ($now - $startTime);
        $left = $total - $done;

        $eta = round($rate * $left, 2);
        $etaType = 'sec';
        $elapsed = $now - $startTime;
        $elapsedType = 'sec';

        if ($eta > 59) {
            $etaType = 'min';
            $eta = round($eta / 60);
        }

        if ($elapsed > 59) {
            $elapsedType = 'min';
            $elapsed = round($elapsed / 60);
        }

        self::display("$statusBar ", 'black', 'green');
        self::display(' ');
        self::display("$done/$total", 'green');
        self::display(' [' . number_format($elapsed) . ' ' . $elapsedType . '/' . number_format($eta) . ' ' . $etaType . ']');
        @ob_flush();
        @flush();
        if ($done === $total) {
            self::newLine();
        }
    }

    /**
     * Display title bar.
     *
     * @param $string
     * @param $foregroundColor
     * @param $backgroundColor
     */
    public static function displayTitle($string, $foregroundColor, $backgroundColor)
    {
        $title = self::title('');
        self::display($title, $foregroundColor, $backgroundColor);
        self::newLine();

        $title = self::title(strtoupper($string));
        self::display($title, $foregroundColor, $backgroundColor);
        self::newLine();

        $title = self::title('');
        self::display($title, $foregroundColor, $backgroundColor);
        self::newLine();
    }

    /**
     * Print break without writing logs.
     *
     * @param int $eol
     */
    public static function newLine($eol = 1)
    {
        self::write(self::eol($eol), 'white', null, false);
    }

    /**
     * Print message without writing logs.
     *
     * @param $string
     * @param int $eol
     * @param string $foregroundColor
     * @param null $backgroundColor
     * @param bool $escape
     */
    public static function displayLine($string, $eol = 1, $foregroundColor = 'white', $backgroundColor = null, $escape = true)
    {
        self::write($string . self::eol($eol), $foregroundColor, $backgroundColor, false, $escape);
    }

    /**
     * Choice.
     *
     * @param $question
     * @param $options
     *
     * @return string
     */
    public static function choice($question, $options)
    {
        foreach ($options as $key => $value) {
            self::displayOption($key, $value);
        }
        self::newLine(2);

        return self::read(trim($question) . ' ', 'purple');
    }

    /**
     * Print option.
     *
     * @param $num
     * @param $string
     * @param string $foregroundColor
     * @param null $backgroundColor
     * @param bool $escape
     */
    public static function displayOption($num, $string, $foregroundColor = 'white', $backgroundColor = null, $escape = true)
    {
        self::write('    [' . $num . '] ' . $string . self::eol(1), $foregroundColor, $backgroundColor, false, $escape);
    }

    /**
     * Print message without writing logs.
     *
     * @param $string
     * @param string $foregroundColor
     * @param null $backgroundColor
     */
    public static function display($string, $foregroundColor = 'white', $backgroundColor = null, $escape = true)
    {
        self::write($string, $foregroundColor, $backgroundColor, false, $escape);
    }

    /**
     * Print break.
     *
     * @param int $eol
     */
    public static function writeBreak($eol = 1)
    {
        self::write(self::eol($eol));
    }

    /**
     * Print message and print eol.
     *
     * @param string $string
     * @param int $eol
     * @param string $foregroundColor
     * @param null $backgroundColor
     * @param null $log
     * @param bool $escape
     */
    public static function writeLine($string, $eol = 1, $foregroundColor = 'white', $backgroundColor = null, $log = null, $escape = true)
    {
        self::write($string . self::eol($eol), $foregroundColor, $backgroundColor, $log, $escape);
    }

    /**
     * Print message.
     *
     * @param $string
     * @param string|null $foregroundColor
     * @param string|null $backgroundColor
     * @param null $log
     */
    public static function write($string, $foregroundColor = 'white', $backgroundColor = null, $log = null, $escape = true)
    {
        $returnString = $string;
        if (!Scanner::isColorEnabled()) {
            $foregroundColor = null;
            $backgroundColor = null;
        }
        if (Scanner::isLogEnabled() && $log === null) {
            $log = true;
        }
        if ($escape) {
            $returnString = self::escape($returnString);
        }
        $coloredString = '';
        if (isset(self::$foregroundColors[$foregroundColor])) {
            $coloredString .= "\033[" . self::$foregroundColors[$foregroundColor] . 'm';
        }
        if (isset(self::$backgroundColors[$backgroundColor])) {
            $coloredString .= "\033[" . self::$backgroundColors[$backgroundColor] . 'm';
        }
        $coloredString .= $returnString . "\033[0m";

        if (!Scanner::isSilentMode()) {
            if (Scanner::isColorEnabled()) {
                echo $coloredString;
            } else {
                echo $returnString;
            }
        }

        if ($log) {
            self::log($string, (!empty($foregroundColor) && $foregroundColor !== 'white' ? $foregroundColor : $backgroundColor));
        }
    }

    /**
     * Read input.
     *
     * @param string|null $string
     * @param string|null $foregroundColor
     * @param null $backgroundColor
     *
     * @return string
     */
    public static function read($string, $foregroundColor = 'white', $backgroundColor = null)
    {
        if (!Scanner::isColorEnabled()) {
            $foregroundColor = null;
            $backgroundColor = null;
        }
        $coloredString = '';
        if (isset(self::$foregroundColors[$foregroundColor])) {
            $coloredString .= "\033[" . self::$foregroundColors[$foregroundColor] . 'm';
        }
        if (isset(self::$backgroundColors[$backgroundColor])) {
            $coloredString .= "\033[" . self::$backgroundColors[$backgroundColor] . 'm';
        }
        $coloredString .= $string . "\033[0m";

        $read = null;

        if (!Scanner::isSilentMode()) {
            if (Scanner::isColorEnabled()) {
                echo Scanner::getLowerName() . ' > ' . trim($coloredString) . ' ';
            } else {
                echo Scanner::getLowerName() . ' > ' . trim($string) . ' ';
            }
        }

        if (self::isWindows()) {
            $read = stream_get_line(STDIN, 1024, PHP_EOL);
        } else {
            $in = [STDIN];
            $out = [];
            $oob = [];
            while (@stream_select($in, $out, $oob, 0)) {
                fgets(STDIN);
            }
            $read = rtrim(fgets(STDIN));
        }

        return (string)$read;
    }

    /**
     * Print code.
     *
     * @param $string
     * @param array $errors
     * @param bool $log
     */
    public static function code($string, $errors = [], $log = false)
    {
        $code = $string;
        foreach ($errors as $pattern) {
            $escaped = self::escape($pattern['match']);
            $code = str_replace($pattern['match'], "\033[" . self::$foregroundColors['red'] . 'm' . $escaped . "\033[" . self::$foregroundColors['white'] . 'm', $code);
        }
        $lines = explode("\n", $code);
        foreach ($lines as $i => $iValue) {
            if ($i !== 0) {
                self::newLine();
            }
            self::display('  ' . str_pad((string)($i + 1), strlen((string)count($lines)), ' ', STR_PAD_LEFT) . ' | ', 'yellow');
            self::display($iValue, 'white', null, false);
        }
        if ($log) {
            self::log($string);
        }
    }

    /**
     * Write logs.
     *
     * @param $string
     * @param string $color
     */
    public static function log($string, $color = '')
    {
        $string = trim($string);
        if (!empty($string)) {
            $string = trim($string, '.');
            $string = str_replace(self::eol(1), ' ', $string);
            $string = preg_replace("/[\s]+/m", ' ', $string);
            $type = 'INFO';
            switch ($color) {
                case 'green':
                    $type = 'SUCCESS';
                    break;
                case 'yellow':
                    $type = 'WARNING';
                    break;
                case 'red':
                    $type = 'DANGER';
                    break;
            }
            $string = '[' . date('Y-m-d H:i:s') . '] [' . $type . '] ' . $string . PHP_EOL;
            file_put_contents(Scanner::getPathLogs(), $string, FILE_APPEND);
        }
    }

    /**
     * Escape colors string.
     *
     * @param $string
     *
     * @return string
     */
    public static function escape($string)
    {
        return mb_convert_encoding(preg_replace('/(e|\x1B|[[:cntrl:]]|\033)\[(\d{1,2}(;\d{1,2})?)?[mGKc]/', '', $string), 'utf-8', 'auto');
    }

    /**
     * Print lists.
     *
     * @param null $type
     */
    public static function helplist($type = null)
    {
        if (!Scanner::isCli()) {
            return;
        }

        $list = '';
        if (empty($type) || $type === 'exploits') {
            $exploitList = implode(self::eol(1) . '- ', array_keys(Exploits::getAll()));
            $list .= self::eol(1) . 'Exploits:' . self::eol(1) . "- $exploitList";
        }
        if (empty($type)) {
            $list .= self::eol(1);
        }
        if (empty($type) || $type === 'functions') {
            $functionsList = implode(self::eol(1) . '- ', Functions::getDefault());
            $list .= self::eol(1) . 'Functions:' . self::eol(1) . "- $functionsList";
        }
        if (empty($type)) {
            $list .= self::eol(1);
        }
        if (empty($type) || $type === 'functions-encoded') {
            $functionsList = implode(self::eol(1) . '- ', Functions::getDangerous());
            $list .= self::eol(1) . 'Functions Encoded:' . self::eol(1) . "- $functionsList";
        }
        self::displayTitle(trim($type . ' List'), 'black', 'cyan');
        self::displayLine($list, 2);
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function wordWrap($text)
    {
        return wordwrap($text, self::$maxLineLength);
    }

    /**
     * Print Helper.
     */
    public static function helper()
    {
        if (!Scanner::isCli()) {
            return;
        }

        $name = Scanner::getLowerName();
        $argv = Scanner::getArgv();
        $argv->setExamples([
            'php ' . $name . ' ./mywebsite/http/ -l -s --only-exploits',
            'php ' . $name . ' -s --max-filesize="5MB"',
            'php ' . $name . ' -s -logs="/user/marco/scanner.log"',
            'php ' . $name . ' --lite --only-exploits',
            'php ' . $name . ' --exploits="double_var2" --functions="eval, str_replace"',
            'php ' . $name . ' --ignore-paths="/my/path/*.log,/my/path/*/cache/*"',
        ]);
        $help = $argv->usage();

        self::displayTitle('Help', 'black', 'cyan');
        self::newLine();
        self::displayLine(self::wordWrap('IMPORTANT: You will be solely responsible for any damage to your computer system or loss of data' .
            'that results from such activities. You are solely responsible for adequate protection and backup' .
            'of the data before execute the scanner.'));
        self::displayLine($help, 2);
        self::displayLine('Notes:');
        self::displayLine('For open files with nano or vim run the scripts with "php -d disable_functions=\'\'"', 2);
    }

    /**
     * Is windows environment.
     *
     * @return bool
     */
    public static function isWindows()
    {
        return stripos(PHP_OS, 'WIN') === 0;
    }
}
