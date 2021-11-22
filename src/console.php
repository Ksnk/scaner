<?php
/**
 * Created by PhpStorm.
 * базовый функционал unix консоли
 * Date: 09.11.17
 * Time: 11:25
 */

namespace Ksnk\scaner;

class console extends scaner
{

    var $cwd = null, $cmd = '', $cmds = array(), $wrap = '', $current_dir = '', $success = false;

    var $ontextevents=[];

    static function _(array $args)
    {
        $cmd = array();
        $programName = array_shift($args);
        foreach ($args as $arg) {
            if (is_array($arg)) {
                foreach ($arg as $key => $value) {
                    $_c = '';
                    if (is_string($key)) {
                        $_c = "$key ";
                    }
                    $cmd[] = $_c . escapeshellarg($value);
                }
            } elseif (is_scalar($arg) && !is_bool($arg)) {
                $cmd[] = escapeshellarg($arg);
            }
        }
        return "$programName " . implode(' ', $cmd);
    }

    function run($cmd)
    {
        if(is_array($cmd)){
            $this->cmds[] = $cmd;
        } else {
            $args = func_get_args();
            $cmd = self::_($args);
            $this->success = true;
            //      if(PHP_OS=='WINNT'){
            $this->cmds[] = $cmd;
            //       } else {
            //          $this->cmds[] = $cmd . ' 2>&1';
            //      }
        }
        return $this;
    }

    /**
     * @return self
     */
    function begin($wrap = '')
    {
        if ($this->cwd === NULL) // TODO: good idea??
        {
            $this->cwd = getcwd();
            if(!empty($this->current_dir))
                $this->cmds[] = 'cd ' . $this->current_dir;
            //chdir($this->current_dir);
        }
        if (!empty($wrap)) {
            $this->wrap = $wrap;
        }
        return $this;
    }

    /**
     * @return self
     */
    function end()
    {
        if (is_string($this->cwd)) {
            chdir($this->cwd);
        }

        $cmd = implode('; ', $this->cmds);

        if (!empty($this->wrap)) {
            $cmd = $this->wrap . ' "' . str_replace('"', '\\"', $cmd) . '"';
        }

        $this->cmd = $cmd;
        exec($cmd, $output, $ret);
        if ($ret !== 0) {
            $this->success = false;
            $output[] = '';
            $output[] = sprintf('Command "%s" failed (exit-code %s).', $cmd, $ret);
        }
        $this->newbuf($output);
        $this->cwd = NULL;
        $this->cmds = array();
        $this->wrap = '';
        return $this;
    }

    /**
     * новые функции интерфейса
     */

    /**
     * обработчик реакции на попытку запросить данные консолью
     * @param $pattern
     * @param $callable
     */
    function ontext($pattern, $callable){
        $this->ontextevents[]=['pattern'=>$pattern, 'callable'=>$callable];
        return $this;
    }

    private function non_block_read(&$pipes, $v=false) {
        $starttime=microtime(true);
        while(microtime(true)-$starttime<1) {
            $read = array($pipes[1]);
            $write = array();
            $except = array();
            $result = stream_select($read, $write, $except, 0);
            if($result === false) {
                throw new \Exception('stream_select failed');
            }
            if($result === 0) {
                continue;
            };
            if(!feof($pipes[1])) {
                stream_set_blocking($pipes[1], false);
                $x = stream_get_contents($pipes[1], 1);
   //             $x = stream_get_contents($pipes[1], 1);
            } else {
                $x = '';
            }
            if($x=='') continue;
            $pos=$this->getpos();
            $this->appendbuf($x);
            if(!empty($v)){
                if ( $this->scan($v['pattern'])->found){
                    if(is_callable($v['callable'])){
                        $x=call_user_func($v['callable'],$this);
                    } else {
                        $x=$v['callable'];
                    }
                    if(empty($v['pattern'])){
                        array_shift($this->ontextevents);
                    }
                    if(!empty($x)) {
                        fwrite($pipes[0],$x);
                        break;
                    }
                }
                $this->position($pos);
            }
        }
    }

    /**
     * выполнить команду в итерактивном режиме, с вводом-выводом на лету
     * @param $cmd
     * @return console
     * @throws \Exception
     */
    function exec($cmd){
        $this->cmd = $cmd;

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
            1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
            2 => array("pipe", "w") // stderr - файл для записи
        );

        $cwd = '/tmp';
        $env = array('some_option' => 'aeiou');
        $process = proc_open($this->cmd, $descriptorspec, $pipes, getcwd(), $env);
        if (is_resource($process)) {
            foreach($this->cmds as $c) {
                if(is_array($c)){
                    if(isset($c['pattern'])) {
                        $this->non_block_read($pipes, $c);
                    } else
                        fwrite($pipes[0], $c[0]);
                } else {
                    fwrite($pipes[0], $c . "\n");
                }
            }
        }
        //$this->non_block_read($pipes);

        fclose($pipes[0]);
        $err= stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if(strlen($err)>0){
            $this->error($err);
        }
        $this->appendbuf(stream_get_contents($pipes[1], -1));
        fclose($pipes[1]);
        $return_value = proc_close($process);
        $this->found= $return_value;
        return $this;
    }
}