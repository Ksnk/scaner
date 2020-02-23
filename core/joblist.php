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
    const STORE_FILE = 'store.json';
    const ACTION_FILE = 'action.json';
    var $mtime = 0, $classes = array();

    var $list = array(), $_timestart = 0,
        $cdir = '', $cclass = '', $data = array(),
        $outstream = self::OUTSTREAM_PRE,
        $outstream_parameter = '',
        $result = [];

    function __construct()
    {
        parent::__construct();
        $this->_timestart = microtime(true);
        $this->load();
    }

    function __destruct()
    {
        // $this->store();
    }

    function outstream($streamcns, $parameter)
    {
        $this->outstream = $streamcns;
        $this->outstream_parameter = $parameter;
    }

    /**
     * Записать аварийный флаг, чтобы повлиять на donext
     * @param $action
     */
    static function action($action)
    {
        $a = self::getAction();
        $a[$action] = time();
        self::storeAction($a);
    }

    private function store($force = false, $data = null)
    {
        $x = -1;
        // echo '1';
        if (file_exists(self::STORE_FILE)) {
            $x = filemtime(self::STORE_FILE);
        }
        if (!$force && $this->mtime == $x) return;
        //echo '2';
        $x = @json_encode(array(
            'ver' => '1.0',
            'jobs' => $this->list,
            'cdir' => $this->cdir,
            'cclass' => $this->cclass,
            'data' => $data,
        ), JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT);
        if (!empty($x)) {
            file_put_contents(self::STORE_FILE, $x);
            $this->mtime = filemtime(self::STORE_FILE);
        } else {
            echo 'json error :' . json_last_error();
        }
    }

    private static function getAction()
    {
        if (!is_readable(self::ACTION_FILE)) return false;
        return json_decode(file_get_contents(self::ACTION_FILE), true);
    }

    private static function storeAction($action)
    {
        if (empty($action) && is_readable(self::ACTION_FILE)) {
            unlink(self::ACTION_FILE);
            return;
        }
        $x = @json_encode($action);
        if (!empty($x)) {
            file_put_contents(self::ACTION_FILE, $x);
        } else {
            echo 'json error :' . json_last_error();
        }
    }

    private function load($force = false)
    {
        //echo '1';
        if (!is_readable(self::STORE_FILE)) return;
        $x = filemtime(self::STORE_FILE);
        if ($x == $this->mtime && !$force) return;
        //echo '2';
        $this->mtime = $x;
        $x = json_decode(file_get_contents(self::STORE_FILE), true);
        if (!empty($x)) {
            if (isset($x['ver']) && $x['ver'] == '1.0') {
                $this->list = $x['jobs'];
                $this->cdir = $x['cdir'];
                $this->cclass = $x['cclass'];
                $this->data = $x['data'];
            } else {
                $this->list = $x;
            }
        }
    }

    function append($type, $args, $top = false)
    {
        $store = [$this->cclass, $this->cdir];
        $this->load();
        $this->cclass = $store[0];
        $this->cdir = $store[1];
        if (isset($args[0]) && gettype($args[0]) == 'string') {
            // расширяем сценарий на метод того сценария, который сейчас выполняется.
            $method = $args[0];
            if (!isset($args[1])) array_push($args, array());
            $args[0] = array(
                'class' => $this->cclass,
                'dir' => $this->cdir,
                'method' => $method,
            );
        }
        if (!$top) {
            $this->list[] = array($type, $args);
        } else {
            array_unshift($this->list, array($type, $args));
        }
        $this->store(true);
        return $this;
    }

    function append_scenario()
    {
        $x = func_get_args();
        return $this->append('scenario', $x);
    }

    function jobcount()
    {
        return count($this->list);
    }

    function getResult()
    {
        return $this->result;
    }

    /**
     * @return bool
     */
    function donext($til = 5)
    {
        $starttime = time();
        while (true) {
            if (count($this->list) == 0 && !empty($this->classes)) {
                foreach ($this->classes as $class) {
                    if (method_exists($class, 'handle')) {
                        ob_start();
                        call_user_func(array($class, 'handle'), 'complete');
                        $result = trim(ob_get_contents());
                        if ('' != trim($result)) {
                            $this->result[] = [$this->outstream, $this->outstream_parameter, $result];
                        }
                        ob_end_clean();
                    }
                }
            }
            if (!empty($action = self::getAction())) {
                if (isset($action['pause']) && $starttime >= $action['pause']) {
                    unset($action['pause']);
                    $this->result[] = [self::OUTSTREAM_PRE, '', '...paused...'];
                    self::storeAction($action);
                    return false;
                }
                if (isset($action['stop']) && $starttime >= $action['stop']) {
                    unset($action['stop']);
                    $this->list = [];
                    $this->store(true);
                    $this->result[] = [self::OUTSTREAM_PRE, '', '...stopped...'];
                    self::storeAction($action);
                    return false;
                }
                $action = [];
                self::storeAction($action);
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
                    if (!class_exists($scn['class'], false))
                        if (isset($scn['dir']))
                            include_once($scn['dir']);
                    $cname = $scn['class'];
                    $par = null;
                    if (isset($this->data[$scn['class']])) {
                        $par = $this->data[$scn['class']];
                    }
                    $this->cclass = $scn['class'];
                    if (method_exists($scn['class'], 'get')) {
                        $class = call_user_func(array($cname, 'get'), $this, $par);
                        $this->classes[$scn['class']] = $class;
                    } else {
                        $class = new $cname($this, $par);
                    }

                    if (method_exists($class, $scn['method'])) {
                        ob_start();
                        call_user_func_array(array($class, $scn['method']), $task[1][0]);
                        $result = trim(ob_get_contents());
                        if ('' != trim($result)) {
                            $this->result[] = [$this->outstream, $this->outstream_parameter, $result];
                        }
                        ob_end_clean();
                    }
                    break;
            }

        }
        return true;
    }

}