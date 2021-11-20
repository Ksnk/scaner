<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 01.02.2020
 * Time: 1:05
 */

/**
 * трейт для класса с переопределяемыми извне функциями вывода сообщенй-ошибок
 * + статистика и опции, не, ну а чо ?
 */

namespace Ksnk\scaner;

use \Exception;

trait traitHandledClass
{
    protected
        $_h_error = null,
        $_h_debug = null,
        $_h_out = null;

    public function _error($callback = false)
    {
        if ($callback === false) return $this->_h_error;
        $this->_h_error = $callback;
        return $this;
    }

    public function _debug($callback = false)
    {
        if ($callback === false) return $this->_h_debug;
        $this->_h_debug = $callback;
        return $this;
    }

    public function _out($callback = false)
    {
        if ($callback === false) return $this->_h_out;
        $this->_h_out = $callback;
        return $this;
    }

    // поставить все обработчики скопом
    public function _all($func)
    {
        if (is_callable($func)) {
            $this
                ->_error($func)
                ->_debug($func)
                ->_out($func);
        }
        return $this;
    }

    private function _buildMess($args)
    {
        if (count($args) < 1) {
            return '';
        }
        foreach ($args as &$a) {
            if (is_array($a)) $a = print_r($a, true);
        }
        if (count($args) > 1) {
            $format = array_shift($args);
            return vsprintf($format, $args);
        } else {
            return $args[0];
        }
    }

    /**
     * выбросить ашипку
     * @param $message
     * @return bool
     * @throws \Exception
     */
    protected function error($message)
    {
        $message = $this->_buildMess(func_get_args());
        if (is_callable($this->_h_error)) {
            call_user_func($this->_h_error, $message);
        } else
            throw new Exception($message);
        return true;
    }

    /**
     * отладка
     * @param $message
     * @return bool
     */
    protected function debug($message = '')
    {
        if (is_callable($this->_h_debug) && !empty($message)) {
            $message = $this->_buildMess(func_get_args());
            call_user_func($this->_h_debug, $message);
        }
        return is_callable($this->_h_debug);
        //else do nothing;
    }

    /**
     * вывести что-то кудато
     * @param $message
     * @return bool
     * @throws Exception
     */
    protected function out($message)
    {
        $message = $this->_buildMess(func_get_args());
        if (is_callable($this->_h_out)) {
            call_user_func($this->_h_out, $message);
        } else
            echo $message;
        return true;
    }

    /**
     * собираем статистику
     * @param $prop
     * @param string $value
     * @param string $subval
     * @return |null
     */
    function stat($prop='', $value = '+1', $subval = '')
    {
        if(!isset($this->_stat)) return null;
        if(empty($prop)) return $this->_stat;
        if ('+1' === $value) {
            $this->_stat[$prop] = 1 + ($this->_stat[$prop] ?: 0);
        } else if ('+' === $value) {
            if (!isset($this->_stat[$prop])) {
                $this->_stat[$prop] = $subval;
            } else {
                $this->_stat[$prop] .= ' ' . $subval;
            }
        } else {
            $this->_stat[$prop] = $value;
        }
        return null;
    }

    /**
     * установить параметры. Параметры ставятся в виде строки со словами через
     * пробел.
     * параметр - внутреняя логичесская переменная класса с именем `_параметр`
     * если нужно поставить ему значение true  -'параметр', false - 'noпараметр'
     * Фича приехала из ENGINE, хотя там она более навороченная
     *
     * @param string|array $option строка с параметрами, через пробел
     * @example nolines par=30 write
     *
     */
    function set_option($option)
    {
        $prop = array();
        if (is_array($option)) {
            $prop = $option;
        } else if (is_string($option)) {
            foreach (explode(' ', $option) as $o) {
                if (strpos($o, 'no') === 0) {
                    $o = substr($o, 2);
                    $val = false;
                } else if (strpos($o, '=') !== false) {
                    $o = explode('=', $o, 2);
                    $val = (int)$o[1];
                    $o = $o[0];
                } else {
                    $val = true;
                }
                $prop[$o] = $val;
            }
        }

        if (!empty($prop)) {
            foreach ($prop as $o => $val) {
                if (property_exists($this, $o = '_' . $o) && ($this->$o != $val)) {
                    $this->$o = $val;
                }
            }
        }
    }

}