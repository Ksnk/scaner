<?php

namespace Ksnk;

/**
 * простой сканер разнобольших текстовых файлов, можно в гнузипе
 * Используется как родитель для транспортных классов - spider+mailer
 * так и для сольного использования - анализ логов.
 *
 * Читает файл в буфер. Анализ файла идет регулярками. когда "курсор" чтения
 * доходит до граница прочитанного буфера - файл дочитывается, буфер смещается.
 * Базовый сервис сканирования и парсинга с помощью регулярок и рудиментарно-простой синтаксический анализ
 *
 * Class scaner
 */
class scaner
{

    /** @var int - позиция начала совпадения регулярки для функции scan */
    protected $reg_begin;

    /** @var boolean - результат операции поиска */
    var $found = false;

    /** @var handleString */
    private $_handle = null;

    /** @var integer */
    private
        $result;

    /**
     * Выдать результат работы функций сканирования.
     * При этом чистится сохраненный результат
     * @return array|int
     */
    function getresult()
    {
        $x = empty($this->result)
            ? array()
            : $this->result;
        $this->result = array();
        return $x;
    }

    /**
     * Строка для анализа
     * @param $buf
     * @return $this
     */
    function newbuf($buf)
    {
        $this->_handle = new handleString($buf);
        $this->result = array();
        return $this;
    }

    /**
     * Файл для анализа. Можно gz.
     * @param $handle
     * @return $this
     */
    function newhandle($handle)
    {

        if (is_string($handle)) {
            if (preg_match('/\.gz$/', $handle)) {
                $this->_handle = new handleGZfile($handle);
            } else {
                $this->_handle = new handleFile($handle);
            }
        } else
            $this->_handle = $handle; // tod: а вот оно надо или нет?

        $this->result = array();
        return $this;
    }

    /**
     * Построчное чтение файла
     */
    function line()
    {
        $this->found = true;
        $move = false;
        $this->_handle->prepare(false);
        if (strlen($this->_handle->buf) <= $this->_handle->start && !$move) {
            $this->found = false;
            return $this;
        }

        $x = strpos($this->_handle->buf, "\n", $this->_handle->start);

        if (false === $x) {
            $this->result[] = substr($this->_handle->buf, $this->_handle->start);
            $this->_handle->start = strlen($this->_handle->buf);
        } else {
            if ($this->_handle->outofrange($x + 1)) {
                $this->found = false;
            } else {
                $this->result[] = substr($this->_handle->buf, $this->_handle->start, $x - $this->_handle->start);
                $this->_handle->start = $x + 1;
            }
        }
        return $this;
    }

    /**
     * Позиция курсора в файле
     * @return int
     */
    function getpos()
    {
        return $this->_handle->pos();
    }

    /**
     * установить курсор чтения в позицию $pos
     * @param $pos
     * @return $this
     */
    function position($pos)
    {
        $this->_handle->pos($pos);
        return $this;
    }

    /**
     * scan buffer till pattern not found
     * @param $reg
     * @param $args
     * @param callable $callback
     * @return $this
     */
    function scan($reg, $args = null, $callback = null)
    {
        $this->_handle->prepare(false);
        if (!is_array($args)) {
            $_args = func_get_args();
            $args = [];
            array_shift($_args);
            while (count($_args) > 1) {
                $args[array_shift($_args)] = array_shift($_args);
            }
            $callback = null;
        }
        do {
            $this->found = false;

            if ($reg{0} == '/' || $reg{0} == '~') { // so it's a regular expresion
                $res = preg_match($reg, $this->_handle->buf, $m, PREG_OFFSET_CAPTURE, $this->_handle->start);
                if ($res) {
                    if ($this->_handle->outofrange( $m[0][1] + strlen($m[0][0]))) {
                        $this->found = false;
                        break;
                    } else {
                        $this->result['_regstart']=$this->_handle->filestart + $m[0][1];
                        $this->found = true;
                        $this->_handle->start = $m[0][1] + strlen($m[0][0]);
                        foreach ($args as $x => $name) {
                            if (isset($m[$x])) {
                                if (empty($name))
                                    $this->result[] = $m[$x][0];
                                else
                                    $this->result[$name] = $m[$x][0];
                            }
                        }
                    }
                }
            } else { // it's a plain text
                $y = stripos($this->_handle->buf, trim($reg), $this->_handle->start);
                if (false !== $y) {
                    if ($this->_handle->outofrange( $y + strlen($reg))) {
                        $this->position('till');
                        break;
                    }
                    $this->found = true;
                    $x = strpos($this->_handle->buf, "\n", $y + strlen($reg));
                    if (false === $x)
                        $this->_handle->start = strlen($this->_handle->buf);
                    else
                        $this->_handle->start = $x - 1;
                    $xx = strrpos($this->_handle->buf, "\n", $y - strlen($this->_handle->buf));
                    // echo $xx,' ',$y,' ',$this->_handle->start,"\n";
                    $this->result[] = substr($this->_handle->buf, $xx, $this->_handle->start - $xx);
                }
            }
            if (!$this->found && $this->_handle->movetolastline()){
                continue;
            } else {
                break;
            }

        } while (true);
        if (is_callable($callback)) {
            $callback($this->result);
        }
        return $this;
    }

    /**
     * в случае неудачи возвращает указатель на начало
     * @return $this
     */
    function ifscan()
    {
        $pos = $this->getpos();
        $arg = func_get_args();
        call_user_func_array(array($this, 'scan'), $arg);
        if (!$this->found) {
            $this->position($pos);
        }
        return $this;
    }

    /**
     * Получить строку, вокруг позиции Start
     */
    function getline()
    {
        if ($this->_handle->start == 0) $x = 0;
        else $x = strrpos($this->_handle->buf, "\n", $this->_handle->start - strlen($this->_handle->buf));
        $y = strpos($this->_handle->buf, "\n", $this->_handle->start);
        if (false === $x) $x = 0; else $x++;
        if (false === $y) return substr($this->_handle->buf, $x);
        return substr($this->_handle->buf, $x, $y - $x);
    }

    /**
     * Установить нижнюю границу для выполнения doscan
     * @param $reg
     * @return $this
     */
    function until($reg = '')
    {
        if (empty($reg)) {
            $this->_handle->till = -1;
            return $this;
        }
        $oldstart = $this->_handle->filestart + $this->_handle->start;
        $res = $this->result;
        $f = $this->found;

        $this->scan($reg);

        if ($this->found) {
            $this->_handle->till = $this->result['_regstart'] + 1;
            $this->position($oldstart);
        }
        $this->found = $f;
        $this->result = $res;
        return $this;
    }

    /**
     * Циклический поиск в буфере
     * @param $reg
     * @return $this
     */
    function doscan($reg)
    {
        $arg = func_get_args();
        $old = $this->getResult();
        $r = array();
        do {
            call_user_func_array(array($this, 'scan'), $arg);
            if ($this->found)
                $r[] = $this->getResult();
        } while ($this->found);
        $this->result = $old;
        $this->result['doscan'] = $r;
        $this->_handle->till = -1;
        return $this;
    }

    function getbuf()
    {
        return $this->_handle->buf;
    }

    /**
     * syntax parsing
     * @param $tokens
     * @param $pattern
     * @param $callback
     */
    function syntax($tokens, $pattern, $callback = null)
    {
        // so lets build a reg
        $idx = array(0);
        while (preg_match('/:(\w+):/', $pattern, $m, PREG_OFFSET_CAPTURE)) {
            if (!isset($tokens[$m[1][0]])) break;
            $idx[] = $m[1][0];
            if (is_string($tokens[$m[1][0]])) $tokens[$m[1][0]] = array($tokens[$m[1][0]]);
            $pattern =
                substr($pattern, 0, $m[0][1]) .
                '(' . implode('|', $tokens[$m[1][0]]) . ')' .
                substr($pattern, $m[0][1] + strlen($m[0][0]));
        }
        $this->_handle->prepare(false);
        while (preg_match($pattern, $this->_handle->buf, $m, PREG_OFFSET_CAPTURE, $this->_handle->start)) {
            if ($this->_handle->outofrange( $m[0][1] + strlen($m[0][0]) )) {
                break;
            }
            $skiped = substr($this->_handle->buf, $this->_handle->start, $m[0][1] - $this->_handle->start);
            $this->_handle->start = $m[0][1] + strlen($m[0][0]);
            $r = array('_skiped' => trim($skiped));
            foreach ($idx as $i => $v) {
                if (isset($m[$i]) && !empty($i)) {
                    $r[$idx[$i]] = trim($m[$i][0], "\n\r ");
                }
            }
            if (is_callable($callback))
                $callback($r);
            $this->_handle->prepare(false);
        }
        // todo: добавить перечтение буфера, при последней неудаче
        $this->position('till');
    }

}
