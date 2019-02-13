<?php

/**
 * Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2019
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

require 'vendor/autoload.php';

$input  = 'src/scanner';
$output = 'dist/scanner';

$jc               = new JuggleCode();
$jc->masterfile   = $input;
$jc->outfile      = $output;
$jc->mergeScripts = true;
$jc->run();

if(empty($argv[1])) {
	$version = "0.4.0";
} else {
	$version = $argv[1];
}

$year = date("Y");
$comment = <<<EOD
<?php

/**
 * Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) $year
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 * @version $version
 */
 
EOD;


$file = php_strip_whitespace($output);
$file = preg_replace('/\s*\<\?php/si', $comment, $file);
$file = preg_replace('/namespace marcocesarato\\\\amwscan\;\s*/si', '', $file);
$file = preg_replace('/public\s*static\s*\$VERSION\s*=\s*(\"|\\\')0\.4\.0(\"|\\\')\s*\;/si', 'public static $VERSION = "'.$version.'";', $file);
$file = "#!/usr/bin/php" . PHP_EOL . $file;
$file = file_put_contents($output, $file);