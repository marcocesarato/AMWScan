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
 * Class Flag.
 */
class Flag
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var null
     */
    public $callback;
    /**
     * @var array
     */
    public $aliases = [];
    /**
     * @var bool
     */
    public $hasValue = false;
    /**
     * @var string
     */
    public $valueName;
    /**
     * @var mixed
     */
    public $defaultValue;
    /**
     * @var mixed
     */
    public $var;
    /**
     * @var mixed
     */
    public $help;

    /**
     * Flag constructor.
     *
     * @param $name
     * @param array $options
     * @param null $callback
     */
    public function __construct($name, $options = [], $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
        $this->aliases = array_merge(["--$name"], (array)@$options['alias']);
        $this->defaultValue = @$options['default'];
        $this->hasValue = (bool)@$options['has_value'];
        $this->valueName = @$options['value_name'];
        $this->help = @$options['help'];
        if (array_key_exists('var', $options)) {
            $this->var = &$options['var'];
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $s = implode('|', $this->aliases);
        if ($this->hasValue) {
            $name = empty($this->valueName) ? $this->name : $this->valueName;
            $s = "$s <{$name}>";
        }

        return "[$s]";
    }
}
