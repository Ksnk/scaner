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

        $scaner = new scaner('nolines'); // не теряем время на подсчет строк, а то крыша едет
        $scaner->newhandle($log);
        $total = 0;
        while (
            // времена проставлены для чтения файла напрямую, без гнузипа и пхара
            $scaner->scan('/^.*?' . preg_quote($tofind, '/') . '.*?$/m', 0, 'str')->found // поиск регуляркой 59 сек
            // $scaner->scan($tofind)->found // строковый поиск 2.12 todo:WTF ?
            // $scaner->scan(null, 0, 'str')->found // проверка на чистое чтение файла от начала до конца - 55 секуннд
        ) {
            $res = $scaner->getresult();
            $total++;
            echo $res['str']?:$res[0] . PHP_EOL;
        }
        echo ' Всего : ' . $total;
        print_r ($scaner->stat());

    }

}
