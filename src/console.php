<?php

namespace Ksnk\scaner;

/**
 * Запуск консольных команд и аналииз получившегося результата.
 * Анализ делается обычными для сканера средствами,
 * все команды из параметров begin и run собираются в одну строку
 * и выполняются при end
 *
 * Class console
 *
 * @example
 * $gitname='/opt/git/'.$project.'.git';
 * $console
 *      ->cmd('ssh root@mysite.ru')
 *      ->cmd('git','init')
 *      ->cmd('git clone --bare . ', $gitname)
 *      ->cmd('git remote add shared', $gitname)
 *      ->cmd('git push shared master')
 *      ->cmd('chown -R git .git')
 *      ->run('chown -R git', $gitname);
 */

class console extends scaner
{

    var $cwd = null,
        $cmd = '',
        $cmds = array(),
        $wrap = '',
        $current_dir = '',
        $success = false;

    /**
     * подготовка массива параметров для использования в командной строке
     * @param array $args
     * @return string
     */
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
        return $programName . " " . implode(' ', $cmd);
    }

    /**
     * @return self
     */
    function run()
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
        $this->cwd = null;
        $this->cmds = array();
        $this->wrap = '';
        return $this;
    }

    function cmd()
    {
        if ($this->cwd === NULL) // TODO: good idea??
        {
            $this->cwd = getcwd();
            $this->cmds[] = 'cd ' . $this->current_dir;
        }
        if (!empty($wrap)) {
            $this->wrap = $wrap;
        }
        $cmd = self::_(func_get_args());
        $this->success = true;
        $this->cmds[] = $cmd . ' 2>&1';
        return $this;
    }

}

class ConsoleException extends \Exception
{
}