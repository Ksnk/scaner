<?php
/**
 * helper class to check template modification time
 * ----------------------------------------------------------------------------
 * $Id: Templater engine v 2.0 (C) by Ksnk (sergekoriakin@gmail.com).
 *      based on Twig sintax,
 * ver: , Last build: 1410201429
 * GIT: $
 * ----------------------------------------------------------------------------
 * License MIT - Serge Koriakin - 2012
 * ----------------------------------------------------------------------------
 */

/**
 * helper function to check if value is empty
 */
if (!function_exists('pps')) {
    function pps(&$x, $default = '')
    {
        if (empty($x)) return $default; else return $x;
    }
}
if (!function_exists('ps')) {
    function ps($x, $default = '')
    {
        if (empty($x)) return $default; else return $x;
    }
}

class template_compiler
{

    static $filename = '';

    static private $opt = array(
        'templates_dir' => 'templates/',
        'TEMPLATE_EXTENSION' => 'jtpl'
    );

    static public function options($options = '', $val = null, $default = '')
    {
        if (is_array($options))
            self::$opt = array_merge(self::$opt, $options);
        else if (!is_null($val))
            self::$opt[$options] = $val;
        else if (isset(self::$opt[$options]))
            return self::$opt[$options];
        else return $default;
    }

    static function do_prepare()
    {
        static $done;
        if (!empty($done)) return;
        require_once 'nat2php.class.php';
        require_once 'template_parser.class.php';
        require_once 'compiler.php.php';
        $done = true;
    }

    /**
     * функция компиляции текста. Результатом будет
     * текст функции, для вставки в шаблон
     * @param string $tpl
     */
    static function compile_tpl($tpl, $name = 'compiler')
    {
        static $calc;
        if (empty($calc)) {
            $calc = new php_compiler();
        }
        //compile it;
        $result = '';
        try {
            $calc->makelex($tpl);
            $result = $calc->tplcalc($name);
        } catch (Exception $e) {
            echo $e->getMessage();
            //echo '<pre> filename:'.self::$filename.'<br>';print_r($calc);echo'</pre>';
            return null;
        }
        //execute it
        return $result;

    }

    /**
     * проверка даты изменения шаблона-образца
     */
    static function checktpl($options = '')
    {
        static $include_done;

        if (defined('TEMPLATE_PATH')) {
            self::options('TEMPLATE_PATH', TEMPLATE_PATH);
            self::options('PHP_PATH', TEMPLATE_PATH);
        }
        if (!empty($options))
            self::options($options);

        $ext = self::options('TEMPLATE_EXTENSION', null, 'jtpl');

        if (!class_exists('tpl_base'))
            include_once (template_compiler::options('templates_dir') . 'tpl_base.php');
//$time = microtime(true);
        $templates = glob(self::options('TEMPLATE_PATH') . DIRECTORY_SEPARATOR . '*.' . $ext);
        //print_r('xxx'.$templates);echo " !";
        $xtime = filemtime(__FILE__);
        $include_dir = dirname(__FILE__);

        if (!empty($templates)) {
            foreach ($templates as $v) {
                $name = basename($v, "." . $ext);
                $phpn = self::options('PHP_PATH') . DIRECTORY_SEPARATOR . 'tpl_' . $name . '.php';
                //echo($phpn.' '.$v);
                $force=self::options('FORCE');
                if (
                    !empty($force)
                    || !file_exists($phpn)
                    || (max($xtime, filemtime($v)) > filemtime($phpn))
                ) {
                    if (empty($include_done)) {
                        $include_done = true;
                        require_once $include_dir . '/nat2php.class.php';
                        require_once $include_dir . '/template_parser.class.php';
                        require_once $include_dir . '/compiler.php.php';
                    }
                    php_compiler::$filename = $v;
                    $x = self::compile_tpl(file_get_contents($v), $name);
                    if (!!$x)
                        file_put_contents($phpn, $x);
                }
            }
        }
        // $time = microtime(true) - $time; echo $time.' sec spent';
    }

}