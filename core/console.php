<?php
/**
 * Created by PhpStorm.
 * базовый функционал unix консоли
 * Date: 09.11.17
 * Time: 11:25
 */

class console extends scaner{

    var  $cwd=null,$cmd='',$cmds=array(),$wrap='',$current_dir='',$success=false;

    static function _(array $args)
    {
        $cmd = array();
        $programName = array_shift($args);
        foreach($args as $arg)
        {
            if(is_array($arg))
            {
                foreach($arg as $key => $value)
                {
                    $_c = '';
                    if(is_string($key))
                    {
                        $_c = "$key ";
                    }
                    $cmd[] = $_c . escapeshellarg($value);
                }
            }
            elseif(is_scalar($arg) && !is_bool($arg))
            {
                $cmd[] = escapeshellarg($arg);
            }
        }
        return "$programName " . implode(' ', $cmd);
    }

    function run($cmd){
        $args = func_get_args();
        $cmd = self::_($args);
        $this->success=true;
        $this->cmds[]=$cmd . ' 2>&1';
        return $this;
    }

    /**
     * @return self
     */
    function begin($wrap='')
    {
        if($this->cwd === NULL) // TODO: good idea??
        {
            $this->cwd = getcwd();
            $this->cmds[]='cd '.$this->current_dir;
           //chdir($this->current_dir);
        }
        if(!empty($wrap)){
            $this->wrap=$wrap;
        }
        return $this;
    }
    /**
     * @return self
     */
    function end()
    {
        if(is_string($this->cwd))
        {
            chdir($this->cwd);
        }

        $cmd=implode('; ',$this->cmds );

        if(!empty($this->wrap)){
            $cmd=$this->wrap.' "'.str_replace('"','\\"',$cmd).'"';
        }

        $this->cmd=$cmd;
        exec($cmd , $output, $ret);
        if($ret !== 0)
        {
            $this->success=false;
            $output[]='';
            $output[]=sprintf('Command "%s" failed (exit-code %s).',$cmd, $ret);
        }
        $this->newbuf($output);
        $this->cwd = NULL;
        $this->cmds=array();
        $this->wrap='';
        return $this;
    }

}

class ConsoleException extends \Exception
{
}