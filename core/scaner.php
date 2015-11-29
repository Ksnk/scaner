<?php

/**
 * простой сканер небольших файлов
 * Class scaner
 */
class scaner extends base
{

    /** @var string */
    var $buf;

    /** @var mixed */

    var $found = false;

    /** @var integer */
    private
        $result,

        $filestart = 0,

        $till = -1,

        $start;

    private $tail='';

    function getresult(){
        if(empty($this->result)){
            $x=array();
        } else {
            $x=$this->result;
        }
        $this->result=array();
        return $x;
    }

    /**
     * Строка для анализа
     * @param $buf
     * @return $this
     */
    function newbuf($buf)
    {
        $this->buf = $buf; // run the new scan
        $this->start = 0;
        $this->till = -1;
        $this->result = array();
        $this->filestart=0;
        return $this;
    }

    function __destruct()
    {
        if (!empty($this->handle)) {
            fclose($this->handle);
        }
    }

    /**
     * Файл для анализа. Можно gz.
     * @param $handle
     * @return $this
     */
    function newhandle($handle)
    {

        if (!empty($this->handle)) {
            fclose($this->handle);
        }
        if(is_string($handle)){
            if(preg_match('/\.gz$/',$handle)){
                $handle = gzopen(
                    $handle, 'r'
                );
            } else {
                $handle = fopen($handle, 'r+');
            }
        }

        $this->handle = $handle; // run the new scan
        $this->start = 0;
        $this->till = -1;
        $this->filestart = 0;
        $this->result = array();
        return $this;
    }

    /**
     * @return bool
     */
    function prepare()
    {
        if (!empty($this->handle)) {
            //if ($this->start > strlen($this->buf) - 4096) {
                if (!feof($this->handle)) {
                    $this->buf = $this->tail.substr($this->buf, $this->start+1) . fread($this->handle, 40000);
                    $this->tail='';
                    if (!feof($this->handle)){
                        $x = strrpos($this->buf, "\n");
                        if(false!==$x){
                        $this->tail=substr($this->buf,$x+1);
                        $this->buf=substr($this->buf,0,$x);
                        }
                    }

                    $this->filestart += $this->start;
                    $this->start = 0;
                    return true;
                }
            //}
        }
        return false;
    }

    /**
     * scan buffer till pattern not found
     * @param $reg
     * @return $this
     */
    function scan($reg)
    {
        $this->prepare();
        do {
            if ($reg{0} == '/' || $reg{0} == '~') { // so it's a regular expresion
                $this->found = false;
                $res = preg_match($reg, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start);
                if ($res) {
                    $this->found = true;
                    if ($this->till > 0 && $m[0][1] + strlen($m[0][0]) > $this->till) {

                    } else {
                        $this->start = $m[0][1] + strlen($m[0][0]);
                        $args = func_get_args();
                        array_shift($args);
                        while (count($args) > 0) {
                            $x = array_shift($args);
                            $name = '';
                            if (count($args) > 0) $name = array_shift($args);
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
                $y = stripos($this->buf, $reg, $this->start);
                if (false !== $y) {
                    $this->found = true;
                    $this->start = $y + strlen($reg);
                }
            }
            if (!$this->found && !empty($this->handle) && !feof($this->handle)) { //3940043
                // $this->start=strlen($this->buf);
                $x = strrpos($this->buf, "\n");
                if (false === $x) {
                    $this->start = strlen($this->buf);
                } else {
                    $this->start = $x;
                }
                if ($this->prepare()) {
                    continue;
                } else {
                    break;
                }
            } else
                break;
        } while (true);
        return $this;
    }

    /**
     * Установить нижнюю границу для выполнения doscan
     * @param $reg
     * @return $this
     */
    function until($reg)
    {
        $oldstart = $this->start;
        $this->scan($reg);
        $this->till = $this->start;
        $this->start = $oldstart;
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
        do {
            $oldstart = $this->start;
            call_user_func_array(array($this, 'scan'), $arg);
        } while ($oldstart != $this->start);
        $this->till = -1;
        return $this;
    }

}
