<?php

/**
 * Antimalware Scanner
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2018
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link https://github.com/marcocesarato/PHP-Antimalware-Scanner
 * @version 0.4.0.33
 */

namespace marcocesarato\amwscan;

/**
 * Class Argument
 * @package marcocesarato\amwscan
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
