<?php

/**
 * PHP Antimalware Scanner.
 *
 * @author Marco Cesarato <cesarato.developer@gmail.com>
 * @copyright Copyright (c) 2019
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 *
 * @see https://github.com/marcocesarato/PHP-Antimalware-Scanner
 */

namespace marcocesarato\amwscan;

/**
 * Class Argument.
 */
class Argument
{
    public $name;
    public $vararg = false;
    public $required = false;
    public $defaultValue;
    public $help;

    public function __construct($name, $options = array())
    {
        $this->name = $name;
        $this->vararg = (bool)@$options['var_arg'];
        $this->required = (bool)@$options['required'];
        $this->defaultValue = @$options['default'];
        $this->help = @$options['help'];
    }

    public function __toString()
    {
        $arg = "<{$this->name}>";
        if ($this->vararg) {
            $arg = "$arg ...";
        }
        if (!$this->required) {
            return "[$arg]";
        }

        return $arg;
    }
}
