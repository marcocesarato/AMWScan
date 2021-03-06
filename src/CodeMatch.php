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

class CodeMatch
{
    const DANGEROUS = 'danger';
    const WARNING = 'warn';

    /**
     * Get PHP code parts contained on the text.
     *
     * @param $content
     *
     * @return array
     */
    public static function getCode($content)
    {
        preg_match_all("/<\?(?:php)?(.*)(?!\B\"[^\"]*)(\?>(?=([^\"]*\"[^\"]*\")*[^\"]*$)|$)/six", $content, $codeParts, PREG_SET_ORDER);

        return empty($codeParts) ? [] : $codeParts;
    }

    /**
     * Get match line number.
     *
     * @param $lastMatch
     * @param $contentRaw
     *
     * @return int|null
     */
    public static function getLineNumber($lastMatch, $contentRaw)
    {
        $lineNumber = null;
        if (empty($lastMatch)) {
            return null;
        }
        @preg_match('/' . preg_quote($lastMatch, '/') . '/', $contentRaw, $lineMatch, PREG_OFFSET_CAPTURE);
        if (!empty($lineMatch[0][1])) {
            $lineNumber = count(explode("\n", substr($contentRaw, 0, $lineMatch[0][1])));
        }

        return $lineNumber;
    }

    /**
     * Get console pattern found match output text.
     *
     * @param $type
     * @param $name
     * @param $description
     * @param $match
     * @param null $line
     *
     * @return string
     */
    public static function getText($type, $name, $description, $match, $line = null)
    {
        $maxLengthMatch = 500;
        $prefix = ucfirst($type) . ' (' . $name . ')';
        if (!empty($line)) {
            $prefix .= ' [line ' . $line . ']';
        }
        $shortMatch = trim($match);
        $shortMatch = str_replace(PHP_EOL, ' ', $shortMatch);
        $shortMatch = strlen($shortMatch) > $maxLengthMatch ? substr($shortMatch, 0, $maxLengthMatch) . '...' : $match;
        $shortMatch = preg_replace('/[\s]+/', ' ', $shortMatch);

        return $matchDescription = '[!] ' . trim($prefix) . "\n    - " . $description . "\n      => " . $shortMatch;
    }

    /**
     * Generate regex for function pattern.
     *
     * @param $func
     *
     * @return string
     */
    public static function patternFunction($func)
    {
        return "/(?:^|[\s\r\n]+|[^a-zA-Z0-9_>]+)(" . preg_quote($func, '/') . "[\s\r\n]*\((?<=\().*(?=\))\))/si";
    }

    /**
     * Clean function match result.
     *
     * @param $match
     *
     * @return string|null
     */
    public static function cleanFunctionResult($match)
    {
        return preg_replace("/(?:.*?)([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*[\s\r\n]*\((?<=\().*(?=\))\))(?:.*)/si", '$1', $match);
    }
}
