<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 05.05.2018
 * Time: 23:57
 */

namespace Ksnk\scaner;


class handleString
{

    // буфер для хранения строки
    public $buf = '';

    // @var resource
    public $handle = null;

    public $start = 0;

    public $filestart = 0;

    /** @var string */
    public $tail = '';

    public $till = -1;

    var $finish = 0;

    public function __construct($buf = '')
    {
        if (is_array($buf)) { // is it source from `file` function
            $buf = implode("\n", $buf);
        }
        $this->buf = $buf; // run the new scan
        $this->till = -1;
        $this->start = 0;
        $this->finish = strlen($buf);
        $this->filestart = 0;

    }

    public function outofrange($pos)
    {
        if ($this->till <= 0 || $this->finish < $this->till)
            $till = $this->finish;
        else
            $till = $this->till;
        if ($this->filestart + $pos > $till)
            return true;
        return false;
    }

    public function pos($pos = null)
    {
        if (is_null($pos))
            return $this->start;
        else if ($pos === 'till')
            $this->pos($this->till == -1 ? $this->finish : $this->till);
        else
            $this->start = $pos;
        return true;
    }

    /**
     * All file readed completelly?
     * @return bool
     */
    public function feof()
    {
        return true;
    }

    public function movetolastline()
    {
        return false;
    }

    /**
     * дочитываем буфер, если надо
     * @param bool $force - проверять граничный размер
     * @return bool - последний ли это препаре или нет todo: непонятно накой такой результат нужен
     */
    public function prepare($force = true)
    {
        return false;
    }

}