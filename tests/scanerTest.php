<?php

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk;

class scanerTest extends TestCase
{

    // 3 теста на черновую проверку способов вызова сканера
    public function testSyntax()
    {
        $xxx = <<<PTRN0
Hello from somethere    
PTRN0;
        $scaner = new Ksnk\scaner();
        $result = $scaner
            ->newbuf($xxx)
            ->scan('/from/', [0 => 'from'])
            ->scan('/ (somethere)/', [1 => 'some'])
            ->scan('/(from)/', [1 => 'from2'])
            ->getResult();
        $this->assertEquals($scaner->found, false);
        $this->assertEquals(['from' => 'from', 'some' => 'somethere'], $result);
    }

    public function testSyntax2()
    {
        $xxx = <<<PTRN1
Hello from somethere    
PTRN1;
        $scaner = new Ksnk\scaner();
        $res = [];
        $result = $scaner
            ->newbuf($xxx)
            ->scan('/from/', [0 => 'from'], function ($m) use (&$res) {
                $res = $m;
            })
            ->getResult();
        $this->assertEquals($scaner->found, true);
        $this->assertEquals($res, $result);
    }

    public function testSyntax3()
    {
        $xxx = <<<PTRN2
Hello from somethere    
PTRN2;
        $scaner = new Ksnk\scaner();
        $result = $scaner
            ->newbuf($xxx)
            ->scan('/from/', 0, 'from')
            ->scan('/ (somethere)/', 1, 'some')
            ->scan('/(from)/', 1, 'from2')
            ->getResult();
        $this->assertEquals($scaner->found, false);
        $this->assertEquals(['from' => 'from', 'some' => 'somethere'], $result);
    }

}

