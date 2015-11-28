<?php

/**
 * простой сканер небольших файлов
 * Class scaner
 */
class scaner extends base
{

    /** @var string */
    var $buf,

        /** @var [] */
        $result,

        /** @var integer */
        $till = -1,

        /** @var integer */
        $start;


    /**
     * @param $buf
     * @return $this
     */
    function newbuf($buf)
    {
        $this->buf = $buf; // run the new scan
        $this->start = 0;
        $this->till = -1;
        $this->result = array();
        return $this;
    }

    /**
     * scan buffer till pattern not found
     * @param $reg
     * @return $this
     */
    function scan($reg)
    {
        if ($reg{0} == '/' || $reg{0} == '~') { // so it's a regular expresion
            $res = preg_match($reg, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start);
            if ($res) {
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
                $this->start = $y + strlen($reg);
            }
        }
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
