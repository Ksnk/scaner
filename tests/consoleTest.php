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
            echo '!!!' . $mess . PHP_EOL;
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
            if(file_exists('c:\Program Files\Git\bin\bash.exe'))
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
        $dir='git status -u all';

        $cmd = $this->getScaner();
        $result=$cmd->begin($wrap)->run($dir)->end()
            ->scan('/On branch\s+(\S+)/',1,'branch')
            ->scan('/Your branch is up to date with .*$/',0,'state')
            ->getResult();
        // print_r($result);
        echo $cmd->getbuf();

        $this->assertEquals(!empty($result['branch']), true);
    }


    function testExec(){
        $console=$this->getScaner();
        $phps=[
//    'xx',
//    '"c:\Program Files\Git\bin\bash.exe"',
            'D:\download\openserver\OpenServer\modules\php\PHP_7.3\php.exe',
            'D:\tmp\OpenServer\modules\php\PHP_7.3\php.exe'
        ];
        if(!file_exists($phps[0])) array_shift($phps);

        $php= <<<'PHP'
$stdin = fopen('php://stdin', 'r');
stream_set_blocking($stdin, false);
echo 'Press enter to force run command...' . PHP_EOL;
echo fgets($stdin);
echo "OK! let's go." . PHP_EOL;
PHP;
        $console
            ->run('ls -all')
            ->run('git status -u all')
            ->run('"'.$phps[0].'"')
            ->run(['<?php '.$php.' ?'.'>'])
            ->run(['pattern'=>'~command\.\.\.~s', 'callable'=>'Hello'."\n"])
            ->run('exit')
            ->exec('"c:\Program Files\Git\bin\bash.exe"');
        echo $console->getbuf();
        $this->assertEquals(true, true);
    }


    function testSidek(){
         //[sudo] password for saidek:
        $host='saidek@192.168.2.24';
        $pass='RAUMkDbQS4GxibhPu3';
        $console=$this->getScaner();
        $dir=getcwd();
        chdir('c:\Program Files\Git');
        $console
            ->run('ssh '.$host.'  /bin/bash  -c echo "These commands will be run on: $( uname -a )"')//.$host)
            //->run('ssh -o StrictHostKeyChecking=no '.$host.' uptime  /bin/bash  -c echo "These commands will be run on: $( uname -a )"')//.$host)
            //->run('ssh -tt -o GlobalKnownHostsFile="/c/Users/korjakin_s/.ssh/known_hosts" '.$host.' "echo \'Hello\'"')//.$host)
            ->run([$pass])
            ->run('exit')
            ->run('exit')
            ->exec('bin\bash.exe -i');
        echo $console->getbuf();
        chdir($dir);
        $this->assertEquals(true, true);
    }

}

