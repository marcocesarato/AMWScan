<?php

namespace marcocesarato\amwscan\Abstracts;

abstract class SingletonAbstract
{
    protected static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Private clone method to prevent cloning of the instance of the
     * *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Private unserialize method to prevent unserializing of the *Singleton*
     * instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
}
