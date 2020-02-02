<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 02.02.2020
 * Time: 17:23
 */

class x_parserTest extends PHPUnit_Framework_TestCase
{

    function testDirectCall()
    {
        $this->assertEquals(
            'буйнакскийрайон.рф',idn\IDN::decodeIDN('xn--80aab3adcbea1ahlxkz.xn--p1ai')
        );
    }
}