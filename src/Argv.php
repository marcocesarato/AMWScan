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
 * Class Argv
 * @package marcocesarato\amwscan
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