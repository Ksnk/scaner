<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 01.02.2020
 * Time: 1:05
 */

/**
 * трейт для класса с переопределяемыми извне функциями вывода сообщенй-ошибок
 */
namespace Ksnk\scaner;
use \Exception;

trait traitHandledClass
{
    protected
        $_h_error=null,
        $_h_out = null;

    public function _error($callback){
        $this->_h_error=$callback;
        return $this;
    }
    public function _out($callback){
        $this->_h_out=$callback;
        return $this;
    }

    /**
     * выбросить ашипку
     * @param $message
     * @return void
     * @throws \Exception
     */
    protected function error($message)
    {
        $args = func_get_args();
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        if(is_callable($this->_h_error)){
            $c=$this->_h_error;
            $c($message);
        } else
            throw new Exception($message);
    }

    /**
     * вывести что-то кудато
     * @param $message
     * @throws Exception
     */
    protected function out($message)
    {
        $args = func_get_args();
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }
        if(is_callable($this->_h_out)){
            $c=$this->_h_out;
            $c($message);
        } else
            echo $message;
    }

}