<?php

/**
 * Class Autoload. PSR-0. Can't you hear about PSR-0? Now you can see.
 */
class Autoload
{
    var $dir = array();

    static function register($dir)
    {
        static $loader;
        if (empty($loader)) {
            $loader = new self();
            if (PHP_VERSION < 50300) {
                spl_autoload_register(array($loader, '__invoke'));
            } else {
                spl_autoload_register($loader);
            }
        }

        if (!is_array($dir)) $dir = array($dir);
        foreach ($dir as $d) {
            $loader->dir = array_merge($loader->dir, explode(';', $d));
        }

    }

    public function __invoke($classname)
    {
        //echo($classname.' '.getcwd().' '.json_encode($this->dir)."\n");
        foreach ($this->dir as $d) {
            $filename = $d . '/' . str_replace('\\', '/', $classname) . '.php';
            if (!file_exists($filename)) {
                // echo('!exists '.$filename."\n");
                continue;
            }
            require_once($filename);
        }
        return true;
    }
}

Autoload::register(dirname(__FILE__).'/core',dirname(__FILE__));