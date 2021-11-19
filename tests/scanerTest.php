<?php

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk;

//include "../vendor/autoload.php";

class scanerTest extends TestCase
{

    function getScaner()
    {
        $scaner = new Ksnk\scaner\scaner();
        $scaner->_all(function ($mess) {
            echo '>>>' . $mess . PHP_EOL;
        });
        return $scaner;
    }

    // 3 теста на черновую проверку способов вызова сканера
    public function testSyntax()
    {
        $xxx = <<<PTRN0
Hello from somethere    
PTRN0;
        $scaner = $this->getScaner();
        $result = $scaner
            ->newbuf($xxx)
            ->scan('/from/', 0, 'from')
            ->scan('/ (somethere)/', 1, 'some')
            ->scan('/(from)/', 1, 'from2')
            ->getResult();
        $this->assertEquals($scaner->found, false);
        $this->assertEquals(['from' => 'from', 'some' => 'somethere'], $result);
    }

    public function testSyntax3()
    {
        $xxx = <<<PTRN2
Hello from somethere    
PTRN2;
        $scaner = $this->getScaner();
        $result = $scaner
            ->newbuf($xxx)
            ->scan('/from/', 0, 'from')
            ->scan('/ (somethere)/', 1, 'some')
            ->scan('/(from)/', 1, 'from2')
            ->getResult();
        $this->assertEquals($scaner->found, false);
        $this->assertEquals(['from' => 'from', 'some' => 'somethere'], $result);
    }

    /**
     * тестируется запчасть от парсера данных из игры EndlessSky
     */
    public function testlongsintax()
    {
        // открываем tgz с данными/ Там сохраненнка и данные, которые должны получится
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator('phar://data.tgz'), \RecursiveIteratorIterator::CHILD_FIRST
        );
        $test_data = [];
        /** @var \SplFileInfo $path */
        foreach ($iterator as $path) {
            if ($path->isFile() && preg_match('~\.txt$~', $path->getPathname())) {
                $test_data[str_replace("\\", '/',
                    str_replace(dirname(__FILE__) . DIRECTORY_SEPARATOR, '', $path->getPathname()))] = 1;
            }
        }

        $scaner = $this->getScaner();

        foreach ($test_data as $filename => $v) {
            $scaner->newhandle($filename);
            $conditions = [];
            $tokens = [
                'line' => [':operand:(?: +?:value:|)'],
                'operand' => ['"[^"]*"', '[\'\w]+'],
                'value' => ['[\S]+'],
            ];

            while ($scaner->scan('/^(conditions)(.*)$/m', 2, 'body', 1, 'reason')->found) {
                $res = $scaner->getResult();
                if ($res['reason'] == 'conditions') {
                    $scaner
                        ->tillReg('/^[^\t\n]/m')// '/^\t[^\t]/m'
                        ->syntax($tokens, '/^\t+:line:/m',
                            function ($line) use ($scaner, &$conditions) {
                                if (!isset($line['value'])) $line['value'] = '';
                                if (!empty($line['_skipped'])) {
                                    $scaner->report('ERROR:' . print_r($line, true));
                                    return;
                                }
                                if (!empty($line['operand'])) {

                                    if ($line['operand'][0] == '"') {
                                        $line['operand'] = trim($line['operand'], '"');
                                    }
                                    $conditions[$line['operand']] = $line['value'];
                                }
                            });
                    $scaner->until();
                }
            }

            $result = json_decode(
                file_get_contents(preg_replace('~\.txt$~', '.result.json', $filename))
                , JSON_OBJECT_AS_ARRAY);

            $this->assertEquals($result, $conditions);
        }
    }

}

