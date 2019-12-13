<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 24.11.15
 * Time: 18:53
 */

namespace Ksnk\scaner;
/**
 * список задач. Его можно сохранять, восстанавливать и толкать
 * Class joblist
 */
class joblist extends base
{
    const STORE_FILE='store.json';
    var $mtime=0, $classes=array();

    var $list = array(), $_timestart=0,
        $cdir='',$cclass='',$data=array();

    function __construct(){
        parent::__construct();
        $this->_timestart=microtime(true);
        $this->load();
    }

    function __destruct(){
       // $this->store();
    }

    private function store($force=false,$data=null){
        $x=-1;
       // echo '1';
        if(file_exists(self::STORE_FILE)) {;
            $x=filemtime(self::STORE_FILE);
        }
        if(!$force && $this->mtime==$x) return;
        //echo '2';
        $x=@json_encode(array(
            'ver'=>'1.0',
            'jobs'=>$this->list,
            'cdir'=>$this->cdir,
            'cclass'=>$this->cclass,
            'data'=>$data,
        ));
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
            if(isset($x['ver']) && $x['ver']=='1.0'){
                $this->list=$x['jobs'];
                $this->cdir=$x['cdir'];
                $this->cclass=$x['cclass'];
                $this->data=$x['data'];
            } else {
                $this->list=$x;
            }
        }
    }

    function append($type, $args,$top=false)
    {
        $this->load();
        if(isset($args[0]) && gettype($args[0])=='string'){
            // расширяем сценарий на метод того сценария, который сейчас выполняется.
            $method=$args[0];
            if(!isset($args[1]))array_push($args,array());
            $args[0]=array(
                'class'=>$this->cclass,
                'dir'=>$this->cdir,
                'method'=>$method,
            );
        }
        if(!$top){
            $this->list[] = array($type, $args);
        } else {
            array_unshift($this->list,array($type, $args));
        }
        $this->store(true);
        return $this;
    }

    function append_scenario()
    {
        $x=func_get_args();
        return $this->append('scenario', $x);
    }

    function jobcount(){
        return count($this->list);
    }

    /**
     * @return bool
     */
    function donext($til=20)
    {
      while(true) {
        if (count($this->list) == 0 && !empty($this->classes)) {
          foreach ($this->classes as $class) {
            if (method_exists($class, 'handle')) {
              call_user_func(array($class, 'handle'), 'complete');
            }
          }
        }
        if (!is_null($til) && microtime(true) - $this->_timestart > $til) {
          if (!empty($this->classes)) {
            $data = array();
            foreach ($this->classes as $name => $class) {
              if (method_exists($class, 'handle')) {
                $res = call_user_func(array($class, 'handle'), 'store');
                if (!empty($res)) {
                  $data[$name] = $res;
                }
              }
            }
            if (!empty($data))
              $this->store(true, $data);
          }
          return false;
        }

        if (count($this->list) == 0) {
          return false;
        }
        $this->load();
        $task = array_shift($this->list);
        $this->store(true);
        $scn = array_shift($task[1]);
        switch ($task[0]) {
          case "scenario":
            $this->cdir = \UTILS::val($scn, 'dir');
            $this->cclass = \UTILS::val($scn, 'class');
            if (!class_exists($scn['class'], false))
              if (isset($scn['dir']))
                include_once($scn['dir']);
            $cname = $scn['class'];
            $par = null;
            if (isset($this->data[$scn['class']])) {
              $par = $this->data[$scn['class']];
            }
            if (method_exists($scn['class'], 'get')) {
              $class = call_user_func(array($cname, 'get'), $this, $par);
              $this->classes[$scn['class']] = $class;
            } else {
              $class = new $cname($this, $par);
            }

            if (method_exists($class, $scn['method'])) {
              call_user_func_array(array($class, $scn['method']), $task[1][0]);
            }
            break;
        }
      }
      return true;
    }

}