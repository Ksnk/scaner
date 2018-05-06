<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 05.05.2018
 * Time: 22:14
 */

namespace Ksnk;

/**
 * Class fileHandle - класс - файловый хендл для
 * @package Ksnk
 */
class fileHandle extends stringHandle
{
    /**
     * Читаем и дочитываем из файла вот такими кусками
     */
    const BUFSIZE = 40000; // максимальный размер буфера чтения

    /**
     * Гарантируем такое пространство от курсора чтения до конца буфера.
     * Фактически - ограничение сверху на длину строки
     */
    const GUARD_STRLEN = 4096;

    /**
     * @var resource
     */
    //private $handle=null;

    public function __construct($handle=''){
        parent::__construct();
        if(is_readable($handle)) {
            $this->finish = filesize($handle);
            $this->handle = fopen($handle, 'r+'); // run the new scan
        }
    }

    public function pos($pos=null) {
        if(is_null($pos))
            return $this->filestart+$this->start;
        else if($pos==='till')
            $this->pos($this->till);
        else if (!empty($this->handle)) {
            if ($this->filestart <= $pos && (strlen($this->buf) + $this->filestart) > $pos) {
                $this->start = $pos - $this->filestart;
            } else {
                fseek($this->handle, $pos);
                $this->filestart = $pos;
                $this->buf = '';
                $this->tail = '';
                $this->start = 0;
            }
        }
    }

    /**
     * дочитываем буфер, если надо
     * @param bool $force - проверять граничный размер
     * @return bool - последний ли это препаре или нет todo: непонятно накой такой результат нужен
     */
    public function prepare($force = true)
    {
        if (!$force && strlen($this->buf) - self::GUARD_STRLEN >= $this->start)
            return false;

        if (!empty($this->handle)) {
            if (!feof($this->handle)) {
                if ($this->start >= strlen($this->buf)) {
                    $this->buf = $this->tail;
                } else
                    $this->buf = substr($this->buf, $this->start + 1) . $this->tail;
                $this->buf .= fread($this->handle, self::BUFSIZE);
                $this->tail = '';
                if (!feof($this->handle)) {
                    $x = strrpos($this->buf, "\n");
                    if (false !== $x) {
                        $this->tail = substr($this->buf, $x + 1);
                        $this->buf = substr($this->buf, 0, $x);
                    }
                }

                $this->filestart += $this->start;
                $this->start = 0;
                return true;
            }
        }
        return false;
    }

    public function feof() {
        return empty($this->handle) || feof($this->handle);
    }

    public function movetolastline()
    {
        if($this->feof()) return false;
        $x = strrpos($this->buf, "\n");
        if (false === $x) {
            $this->start = strlen($this->buf);
        } else {
            $this->start = $x;
        }
        return $this->prepare();
    }

}