<?php

$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin - канал, из которого дочерний процесс будет читать
    1 => array("pipe", "w"),  // stdout - канал, в который дочерний процесс будет записывать
    2 => array("pipe", "w") // stderr - файл для записи
);

$cwd = '/tmp';
$env = array('some_option' => 'aeiou');
$php=[
//    'xx',
//    '"c:\Program Files\Git\bin\bash.exe"',
    'D:\download\openserver\OpenServer\modules\php\PHP_7.3\php.exe',
    'D:\tmp\OpenServer\modules\php\PHP_7.3\php.exe'
];
if(!file_exists($php[0])) array_shift($php);
$process = proc_open($php[0], $descriptorspec, $pipes, $cwd, $env);
//socket_set_nonblock($pipes[1]);
stream_set_blocking($pipes[1],false);
if (is_resource($process)) {
    // $pipes теперь выглядит так:
    // 0 => записывающий обработчик, подключённый к дочернему stdin
    // 1 => читающий обработчик, подключённый к дочернему stdout

$php= <<<'PHP'
$stdin = fopen('php://stdin', 'r');
stream_set_blocking($stdin, false);
echo 'Press enter to force run command...' . PHP_EOL;
echo fgets($stdin);
echo "OK! let's go." . PHP_EOL;
PHP;

    function non_block_read(&$pipes, &$data) {
        $read = array($pipes[1]);
        $write = array();
        $except = array($pipes[2]);
        $result = stream_select($read, $write, $except, 0);
        if($result === false) throw new Exception('stream_select failed');
        if($result === 0) return false;
    //    $data=fread($pipes[1],1024);
        if(!feof($pipes[1])) {
            stream_set_blocking($pipes[1],false);
            $data = stream_get_contents($pipes[1],1);
        } else
            $data='';
        return true;
    }



    fwrite($pipes[0], '<?php '.$php.' ?'.'>');
//    fwrite($pipes[0], "echo 1; exit\n");
//    fwrite($pipes[0], "\n");
    $output='';
    $starttime=microtime(true);
    while(microtime(true)-$starttime<1) {
        $x = "";
        if(non_block_read($pipes, $x)) {
            if($x=='') continue;
            $output.=$x;
            if(preg_match('~command\.\.\.~s',$output)) {
                fwrite($pipes[0], 'Hello
');
                break;
            }
        } else {
            echo '.';
            // perform your processing here
        }
    }
    echo "Output: >>" . $output.'>>';
    fclose($pipes[0]);
 //   rewind($pipes[2]);
    $err= stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    if(strlen($err)>0){
        echo '>>'.$err;
    }
    echo stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Важно закрывать все каналы перед вызовом
    // proc_close во избежание мёртвой блокировки
    $return_value = proc_close($process);

    echo "команда вернула $return_value\n";
}
?>
