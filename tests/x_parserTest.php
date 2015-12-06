<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.12.15
 * Time: 21:01
 */

class x_parserTest extends PHPUnit_Framework_TestCase {

    function testClassExists(){
        $this->assertNotEmpty(new x_parser());
    }

    /**
     * @param integer $a : select[1:a|2:b|3:c]  А вот!
     */
    function justforTest($a=3){

    }

    function testOneParameter(){

        $this->assertEquals(
            str_replace("\r","","array (
  'x_parserTest' =>
  array (
    'justforTest' =>
    array (
      'title' => '',
      'param' =>
      array (
        'a' =>
        array (
          'type' => 'select',
          'function' => 'x_parserTest::justforTest',
          'name' => 'x_parserTest::justforTest[a]',
          'default' => 3,
          'title' => 'А вот!',
          'values' =>
          array (
            0 => '1:a',
            1 => '2:b',
            2 => '3:c',
          ),
        ),
      ),
    ),
  ),
)"),
            str_replace(" \n","\n",var_export(x_parser::getParameters('justforTest',__CLASS__), true))
        );
    }

    function testCreateinput(){
        $par=x_parser::getParameters('justforTest',__CLASS__);
        $this->assertEquals(
            '<label>А вот!<select name="x_parserTest::justforTest[a]"><option value="1">a</option><option selected value="2">b</option><option value="3">c</option></select></label>',
            x_parser::createInput($par[__CLASS__]['justforTest']['param']['a'],array(
                'x_parserTest::justforTest'=>array('a'=>2)
            ))
        );
        $this->assertEquals(
            '<label>А вот!<select name="x_parserTest::justforTest[a]"><option value="1">a</option><option value="2">b</option><option selected value="3">c</option></select></label>',
            x_parser::createInput($par[__CLASS__]['justforTest']['param']['a'])
        );
    }
}
 