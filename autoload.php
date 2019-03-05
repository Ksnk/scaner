<?php

/**
 * Class Autoload. PSR-0. Can't you hear about PSR-0? Now you can see.
 */
class Autoload
{
    private $dir = array();

    private static
        $map = array(),
        $index = '';

    static function map($array)
    {
        self::$map = array_merge($array, self::$map);
    }

    static function register($dir)
    {
        static $loader;
        if (defined('INDEX_DIR')) {
            self::$index = INDEX_DIR;
        } else {
            self::$index = dirname(__FILE__);
        }
        if (empty($loader)) {
            $loader = new self();
            if (PHP_VERSION < 50300) {
                spl_autoload_register(array($loader, '__invoke'));
            } else {
                spl_autoload_register($loader);
            }
        }

        if (!is_array($dir)) $dir = array($dir);
        foreach ($dir as $dd)
            foreach (explode(';', $dd) as $d) {
                $loader->dir[] = str_replace('~', self::$index, $d);
            }
        $loader->dir = array_unique($loader->dir);
    }

    public function __invoke($classname)
    {
        $classname = strtr($classname, self::$map);

        foreach ($this->dir as $d) {

            $filename = $d . '/' . str_replace('\\', '/', $classname) . '.php';
            if (!file_exists($filename)) {
                continue;
            }
            require_once($filename);
            return true;
        }
        echo($classname . ' ' . getcwd() . ' ' . json_encode($this->dir) . "\n");
        return false;
    }
}

Autoload::register(array('~/core', '~/libs'));