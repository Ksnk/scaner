<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 01.02.2020
 * Time: 1:05
 */

/**
 * трейт для статического класса с переопределяемыми извне функциями вывода сообщенй-ошибок
 */
namespace Ksnk\scaner;
use \Exception;

trait traitHandledStaticClass
{
    static
    /** @var string */
        $_h_error=null,
    /** @var string */
        $_h_out = null;

    public static function _error($callback){
        self::$_h_error=$callback;
    }
    public static function _out($callback){
        self::$_h_out=$callback;
    }

    /**
     * выбросить ашипку
     * @param $message
     * @return void
     * @throws \Exception
     */
    protected static function error($message)
    {
        $args = func_get_args();
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        if(is_callable(self::$_h_error)){
            call_user_func(self::$_h_error,$message);
        } else
            throw new Exception($message);
    }

    /**
     * вывести что-то кудато
     * @param $message
     * @throws Exception
     */
    protected static function out($message)
    {
        $args = func_get_args();
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        if(is_callable(self::$_h_out)){
            call_user_func(self::$_h_out,$message);
        } else
            echo $message;
    }

}