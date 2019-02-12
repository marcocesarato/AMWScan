<?php

/**
 * Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 * @version 0.4.0.39
 */

namespace marcocesarato\amwscan;

/**
 * Class Flag
 * @package marcocesarato\amwscan
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