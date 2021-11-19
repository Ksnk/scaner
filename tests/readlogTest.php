<?php

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk, Ksnk\scaner\scaner;

class readlogTest extends TestCase
{
    // Ищем в длинном логе вхождения POST
    // выводим полную строку
    /**
     * @example - результат выполнения на файле длиной 311667056 байт/ строка поиска где-то в конце файла
     * 85.114.10.158 - - [19/Nov/2021:07:08:32 +0000] "POST /login?task=user.login HTTP/1.1" 303 1177
     * 85.114.10.158 - - [19/Nov/2021:07:08:32 +0000] "GET /lichnyj-kabinet HTTP/1.1" 200 17260
     * Всего : 2
     *
     * ...
     *
     * Time: 1.08 minutes, Memory: 6.00 MB
     */
    public function test0()
    {

        $tofind = '19/Nov/2021:07:08:32 +0000'; //'POST /login?task=user.login';
        $log=__DIR__.'/kadis.org-access.log';
        //$log = __DIR__ . '/kadis.org-access.log.gz';

        $scaner = new scaner('nolines');
        $scaner->newhandle($log);
        $total = 0;
        //while ($scaner->scan('/^.*?' . preg_quote($tofind, '/') . '.*?$/m', 0, 'str')->found) {
        while ($scaner->scan(null, 0, 'str')->found) {
            $res = $scaner->getresult();
            $total++;
            echo $res['str'] . PHP_EOL;
        }
        echo ' Всего : ' . $total;
        print_r ($scaner->stat());

    }

}
