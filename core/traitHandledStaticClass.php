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

    private static function _buildMess($args){
        if (count($args) <1) {
            return '';
        } if (count($args) > 1) {
            $format=array_shift($args);
            return vsprintf($format, $args);
        } else {
            return $args[0];
        }
    }

    /**
     * выбросить ашипку
     * @param $message
     * @return void
     * @throws \Exception
     */
    protected static function error($message)
    {
        $message=self::_buildMess(func_get_args());
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
        $message=self::_buildMess(func_get_args());
        if(is_callable(self::$_h_out)){
            call_user_func(self::$_h_out,$message);
        } else
            echo $message;
    }

}