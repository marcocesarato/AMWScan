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

/**
 * Class Argument.
 */
class Argument
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var bool
     */
    public $vararg = false;
    /**
     * @var bool
     */
    public $required = false;
    /**
     * @var mixed
     */
    public $defaultValue;
    /**
     * @var mixed
     */
    public $help;

    /**
     * Argument constructor.
     *
     * @param $name
     * @param array $options
     */
    public function __construct($name, $options = [])
    {
        $this->name = $name;
        $this->vararg = (bool)@$options['var_arg'];
        $this->required = (bool)@$options['required'];
        $this->defaultValue = @$options['default'];
        $this->help = @$options['help'];
    }

    /**
     * @return string
     */
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
