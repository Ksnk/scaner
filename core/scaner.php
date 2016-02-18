<?php

/**
 * простой сканер разнобольших текстовых файлов, можно в гнузипе
 * Предназначен как родитель для транспортных классов - spider/mailer
 * так и для сольного использования - анализ логов.
 * Class scaner
 */
class scaner
{

    const MAX_BUFSIZE=60000; // максимальный размер буфера чтения
    const MIN_BUFSIZE=30000; // минимальный размер строки буфера.


    /** @var string */
    private $buf;

    /** @var string */
    private $tail='';

    /** @var boolean */
    var $found = false;

    /** @var integer */
    private
        $result,

        $filestart = 0,

        $till = -1,

        $start;

    var $finish=0;

    /**
     * Выдать результат работы функций сканирования.
     * При этом чистится сохраненный результат
     * @return array|int
     */
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
        $this->finish=strlen($buf);
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
                $_handle = fopen($handle, "rb");
                fseek($_handle, filesize($handle) - 4);
                $x = unpack("L", fread($_handle, 4));
                $this->finish=$x[1];
                fclose($_handle);
                $handle = gzopen(
                    $handle, 'r'
                );
            } else {
                $this->finish=filesize($handle);
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
     * дочитываем буфер, если надо
     * @return bool - последний ли это препаре или нет todo: непонятно накой такой результат нужен
     */
    protected function prepare()
    {
        if (!empty($this->handle)) {
            //if ($this->start > strlen($this->buf) - 4096) {
                if (!feof($this->handle)) {
                    if($this->start>=strlen($this->buf)){
                        $this->buf = $this->tail;
                    } else
                        $this->buf = substr($this->buf, $this->start+1).$this->tail ;
                    $this->buf .= fread($this->handle, 40000);
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
     * Построчное чтение файла
     */
    function line(){
        $this->found = true;
        $move=false;
        if(strlen($this->buf)>=$this->start || strlen($this->buf)-4096>$this->start)
            $move=$this->prepare();
        $x = strpos($this->buf, "\n",$this->start);
        if (strlen($this->buf)>=$this->start && !$move) {
            $this->found = false;
        } elseif (false === $x) {
            $this->result[]=substr($this->buf,$this->start);
            $this->start = strlen($this->buf);
        } else {
            $this->result[]=substr($this->buf,$this->start,$x-$this->start);
            $this->start = $x;
        }
        return $this;
    }

    /**
     * установить курсор чтения в позицию $pos
     * @param $pos
     * @return $this
     */
    function position($pos){
        if (!empty($this->handle)) {
            if($this->filestart<=$pos && (strlen($this->buf)+$this->filestart)>$pos){
                $this->start=$pos-$this->filestart;
            } else {
                fseek($this->handle,$pos);
                $this->filestart=$pos;
                $this->buf='';
                $this->tail='';
                $this->start=0;
            }
        } else {
            $this->start=$pos;
        }
        return $this;
    }

    /**
     * scan buffer till pattern not found
     * @param $reg
     * @return $this
     */
    function scan($reg)
    {
        if(strlen($this->buf)-4096<$this->start)
            $this->prepare();

        do {
            $this->found = false;

            if ($reg{0} == '/' || $reg{0} == '~') { // so it's a regular expresion
                $res = preg_match($reg, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start);
                if ($res) {
                    $this->found = true;
                    if ($this->till > 0 && $this->filestart+$m[0][1] + strlen($m[0][0]) > $this->till) {
                        $this->found = false;
                        break;
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
                $y = stripos($this->buf, trim($reg), $this->start);
                if (false !== $y) {
                    if ($this->till > 0 && $this->filestart+$y + strlen($reg) > $this->till) {
                        $this->position($this->till);
                        break;
                    }
                    $this->found = true;
                    $x = strpos($this->buf, "\n", $y + strlen($reg));
                    if(false===$x)
                        $this->start=strlen($this->buf);
                    else
                        $this->start = $x-1;
                    $xx = strrpos($this->buf, "\n", $y-strlen($this->buf));
                   // echo $xx,' ',$y,' ',$this->start,"\n";
                    $this->result[] = substr($this->buf,$xx,$this->start-$xx);
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
     * Получить строку, вокруг позиции Start
     */
    function getline(){
        if($this->start==0) $x=0;
        else $x=strrpos($this->buf, "\n",$this->start-strlen($this->buf));
        $y=strpos($this->buf, "\n",$this->start);
        if(false===$x) $x=0; else $x++;
        if(false===$y) return substr($this->buf,$x);
        return substr($this->buf,$x,$y-$x);
    }

    /**
     * Установить нижнюю границу для выполнения doscan
     * @param $reg
     * @return $this
     */
    function until($reg='')
    {
        if(empty($reg)){
            $this->till=-1;
            return $this;
        }
        $oldstart = $this->filestart+$this->start;
        $res=$this->result;$f=$this->found;

        $this->scan($reg);

        if($this->found){
            $this->till = $this->filestart+$this->start;
            $this->position($oldstart);
        }
        $this->found=$f;$this->result=$res;
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
            call_user_func_array(array($this, 'scan'), $arg);
        } while ($this->found);
        $this->till = -1;
        return $this;
    }

}
