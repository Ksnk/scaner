<?php

/**
 * Class Autoload. предназначен как для сольного использования, так и для
 * замены штатного autoload микрофреймворка ENGINE.
 */
class Autoload
{
    private $dir = array();
    private static $instance = null;

    private static
        /** @var array прямое перенаправление классов Имя класса->его представитель */
        $map = array(),
        /** @var string root-dir для autoload */
        $index = '',
        $phar = '';

    static function map($array)
    {
        self::$map = array_merge($array, self::$map);
    }

    /**
     * @param array|string $dir - список каталогов для перебора в процессе поиска
     * @param array $aliaces - подмена частичного имени в классе
     */
    static function register($dir, $aliaces = [])
    {
        static $loader;
        if (defined('INDEX_DIR')) {
            self::$index = INDEX_DIR;
        } else {
            self::$index = dirname(__FILE__);
        }
        // коррекция index в случае старта в phar
        if (0 === strpos(self::$index, 'phar://')) {
            // находимся внутри PHAR файла, берем его dirname
            self::$phar = self::$index;
            self::$index = dirname(str_replace('phar://', '', self::$index));
        }
        //echo '$$$'.self::$index.'$$$';
        if (empty($loader)) {
            $loader = new self();
            self::$instance=$loader;
            if (PHP_VERSION < 50300) {
                spl_autoload_register(array($loader, '__invoke'));
            } else {
                spl_autoload_register($loader);
            }
        }
        if (!empty($dir)) {
            if (!is_array($dir)) $dir = array($dir);
            foreach ($dir as $dd)
                foreach (explode(';', $dd) as $d) {
                    $loader->dir[] = str_replace('~', self::$index, $d);
                    if (!empty(self::$phar)) {
                        $loader->dir[] = str_replace('~', self::$phar, $d);
                    }
                }
            $loader->dir = array_unique($loader->dir);
        }
        if (!empty($aliaces)) {
            self::map($aliaces);
        }
    }

    static function find($file)
    {
        $f = str_replace('~', self::$index, $file);
        if (file_exists($f)) return $f;
        if (!empty(self::$phar)) {
            $f = str_replace('~', self::$phar, $file);
            if (file_exists($f)) return $f;
        }
        // ничего не найдено - ищем в пути поиска
        if(!preg_match('~^(/|\\|\w:)~',$f)){
            $filename = self::$index . '/' . $f;
            $idx=0;$found=false;
            while(!($found=file_exists($filename)) && $idx<count(self::$instance->dir)){
               $filename = self::$instance->dir[$idx++] . '/' . $f;
            }
            return $found?$filename:false;
        }
        return false;
    }

    public function __invoke($classname)
    {
        $classname = strtr($classname, self::$map);

        foreach ($this->dir as $d) {

            $filename = $d . '/' . str_replace('\\', '/', $classname) . '.php';
            //echo('>>>'.$classname . ' --- ' . $filename . "\n");
            if (!file_exists($filename)) {
                continue;
            }
            //echo('>>>'.$classname . ' ' . $filename . "\n");
            require_once($filename);
            return true;
        }
       // echo('>>' . $classname . ' ' . getcwd() . ' ' . json_encode($this->dir) . "\n");
        return false;
    }
}

if ('' == ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

if ($x = Autoload::find('~/vendor.phar'))
    include_once 'phar://' . $x . '/vendor/autoload.php';
else if ($x = Autoload::find('~/vendor/autoload.php'))
    include_once $x;
