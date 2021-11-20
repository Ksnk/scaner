<?php

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk;

class consoleTest extends TestCase
{

    function getScaner()
    {
        $scaner = new Ksnk\scaner\console();
        $scaner->_all(function ($mess) {
            echo '>>>' . $mess . PHP_EOL;
        });
        return $scaner;
    }

    /**
     * консоль выполняет команду dir и анализирует вывод
     */
    public function testDirCommand()
    {
        // выполняем dir для Windows

        // находим собственное имя в потоке вывода
        // 20.11.2021  10:53             1 036 consoleTest.php

        if(PHP_OS=='WINNT'){
            $dir='dir';
            $dir_pattern='/^\s*:datetime:\s+:size:\s+:name:.*?$/m';
        } else {
            echo 'Тест расчитан только для выполнения на WINNT - тут '.PHP_OS;
            return ;
        }

        $cmd = $this->getScaner();
        $found=false;
        $cmd->begin()
            ->run($dir)
            ->end()
            ->syntax([
                'datetime'=>'.+?',
                'size'=>'\d\S+',
                'name'=>preg_quote(basename(__FILE__))
            ],$dir_pattern,function($res)use(&$found){
                if(isset($res['name'])) {
                    $t=date_create_from_format('d.m.Y H:i', $res['datetime']);
                    $found=true;
                    printf('date: %s, size: %s, name:%s' . PHP_EOL,
                        $t->format('Y-m-d H:i'),
                        preg_replace('/\D/', '', $res['size']),
                        $res['name']
                    );
                }
            });

            $this->assertEquals($found, true);
    }

    /**
     * выполняет ls из под git bash
     *
     */
    public function testDirGitBash()
    {
        if(PHP_OS=='WINNT'){
            $wrap='"c:\Program Files\Git\bin\bash.exe" -c';
        } else {
            $wrap='';
        }
        $dir='ls -all';
        $dir_pattern='/^:right:\s+\d\s+:user:\s+\d+\s+:size:\s+:datetime:\s+:name:$/ium';

        $cmd = $this->getScaner();
        $found=false;
        $cmd->begin($wrap)->run($dir)->end()
            ->syntax([
                'line'=>'.+?',
                'right'=>'[-drwx]+',
                'user'=>'\w+',
                'datetime'=>'.+?',
                'size'=>'\d\S+',
                'name'=>preg_quote(basename(__FILE__))
            ],$dir_pattern,function($res)use(&$found){
                if(isset($res['name'])) {
                    $found=true;
                    $t=date_create_from_format('M j H:i', $res['datetime']);
                    printf('date: %s, size: %s, name:%s' . PHP_EOL,
                        $t->format('Y-m-d H:i'),
                        preg_replace('/\D/', '', $res['size']),
                        $res['name']
                    );
                } else if (isset($res['line'])){
                    echo $res['line'].PHP_EOL;
                    //echo json_encode($res['line']).PHP_EOL;
                }
            });


        $this->assertEquals($found, true);
    }

    /**
     * git status текущего проекта
     *
     */
    public function testGitStatus()
    {
        if(PHP_OS=='WINNT'){
            $wrap='"c:\Program Files\Git\bin\bash.exe" -c';
        } else {
            $wrap='';
        }
        $dir='git status';
        $dir_pattern='/^:right:\s+\d\s+:user:\s+\d+\s+:size:\s+:datetime:\s+:name:$/ium';

        $cmd = $this->getScaner();
        $found=false;
        $cmd->begin($wrap)->run($dir)->end()
            ->syntax([
                'line'=>'.+?',
                'right'=>'[-drwx]+',
                'user'=>'\w+',
                'datetime'=>'.+?',
                'size'=>'\d\S+',
                'name'=>preg_quote(basename(__FILE__))
            ],'/^:line:$/imu', //$dir_pattern,
                function($res)use(&$found){
                if(isset($res['name'])) {
                    $found=true;
                    $t=date_create_from_format('M j H:i', $res['datetime']);
                    printf('date: %s, size: %s, name:%s' . PHP_EOL,
                        $t->format('Y-m-d H:i'),
                        preg_replace('/\D/', '', $res['size']),
                        $res['name']
                    );
                } else if (isset($res['line'])){
                    echo $res['line'].PHP_EOL;
                    //echo json_encode($res['line']).PHP_EOL;
                }
            });


        $this->assertEquals($found, true);
    }


}

