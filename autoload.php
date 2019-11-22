<?php

/**
 * Class Autoload. предназначен как для сольного использования, так и для
 * замены штатного autoload микрофреймворка ENGINE.
 */
class Autoload
{
    private $dir = array();
    private $aliaces = array();

    private static
        /** @var array прямое перенаправление классов Имя класса->его представитель  */
        $map = array(),
        /** @var string root-dir для autoload  */
        $index = '';

    static function map($array)
    {
        self::$map = array_merge($array, self::$map);
    }

    /**
     * @param array|string $dir - список каталогов для перебора в процессе поиска
     * @param array $aliaces - подмена частичного имени в классе
     */
    static function register($dir,$aliaces=[])
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
        if(!empty($dir)) {
            if (!is_array($dir)) $dir = array($dir);
            foreach ($dir as $dd)
                foreach (explode(';', $dd) as $d) {
                    $loader->dir[] = str_replace('~', self::$index, $d);
                }
            $loader->dir = array_unique($loader->dir);
        }
        if(!empty($aliaces)){
            if (!is_array($aliaces)) $aliaces = array($aliaces);
            foreach ($aliaces as $k=>$v)
                $loader->aliaces[$k] = $v;
           // $loader->aliaces = $loader->aliaces;
        }
    }

    public function __invoke($classname)
    {
        $classname = strtr($classname, self::$map);

        foreach ($this->dir as $d) {

            foreach($this->aliaces as $k=>$v){
                if(0===strpos($classname,$k)){
                    $classname=str_replace($k,$v,$classname);
                }
            }
            $filename = $d . '/' . str_replace('\\', '/', $classname) . '.php';
            //echo('>>>'.$classname . ' --- ' . $filename . "\n");
            if (!file_exists($filename)) {
                continue;
            }
            //echo('>>>'.$classname . ' ' . $filename . "\n");
            require_once($filename);
            return true;
        }
       // echo('>>'.$classname . ' ' . getcwd() . ' ' . json_encode($this->dir) . "\n");
        return false;
    }
}
if(file_exists(__DIR__.'/vendor/autoload.php'))
    include_once __DIR__.'/vendor/autoload.php';

Autoload::register(['~/core', '~/libs'],
    [
        'Ksnk\\scaner\\'=>'/',
        'Ksnk\\'=>'/',
     ]
);