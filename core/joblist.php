<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 24.11.15
 * Time: 18:53
 */

/**
 * список задач. Его можно сохранять, восстанавливать и толкать
 * Class joblist
 */
class joblist extends base
{
    const STORE_FILE='store.json';
    var $mtime=0;

    var $list = array();

    function __construct(){
        $this->load();
    }

    function __destruct(){
       // $this->store();
    }

    private function store($force=false){
        $x=-1;
       // echo '1';
        if(file_exists(self::STORE_FILE)) {;
            $x=filemtime(self::STORE_FILE);
        }
        if(!$force && $this->mtime==$x) return;
        //echo '2';
        $x=@json_encode($this->list);
        if(!empty($x)){
            file_put_contents(self::STORE_FILE,$x);
            $this->mtime=filemtime(self::STORE_FILE);
        } else {
            echo 'json error :'.json_last_error();
        }
    }

    private function load($force=false){
        //echo '1';
        if(!is_readable(self::STORE_FILE)) return;
        $x=filemtime(self::STORE_FILE);
        if($x==$this->mtime && !$force) return;
        //echo '2';
        $this->mtime=$x;
        $x=json_decode(file_get_contents(self::STORE_FILE),true);
        if(!empty($x)){
           // echo '3';
            $this->list=$x;
        }
    }

    private function append($type, $args)
    {
        $this->load();
        $this->list[] = array($type, $args);
        $this->store(true);
        return $this;
    }

    function append_scenario()
    {
        $x=func_get_args();
        $this->append('scenario', $x);
        return $this;
    }

    /**
     * @return bool
     */
    function donext()
    {
        if (count($this->list) == 0)
            return false;
        $this->load();
        $task = array_shift($this->list);
        $this->store(true);
        $scn = array_shift($task[1]);
        switch ($task[0]) {
            case "scenario":
                if(!class_exists($scn['class'],false))
                    if(isset($scn['dir']))
                        include_once($scn['dir']);
                $cname=$scn['class'];
                if (method_exists($scn['class'], 'get')) {
                    $class=call_user_func(array($cname, 'get'),$this);
                } else {
                    $class =new $cname($this);
                }
                if (method_exists($class, $scn['method'])) {
                    call_user_func_array(array($class, $scn['method']), $task[1][0]);
                }
                break;
        }
        return true;
    }

}