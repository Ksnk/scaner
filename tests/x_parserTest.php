<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.12.15
 * Time: 21:01
 */

use \Ksnk\scaner\x_parser;

class x_parserTest extends PHPUnit_Framework_TestCase {

    function sanitizeWS($str){
        return str_replace("\r","",str_replace(" \n","\n",$str));
    }

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
            $this->sanitizeWS("array (
  'tags' =>
  array (
    0 => 'unknown',
  ),
  'x_parserTest' =>
  array (
    'justforTest' =>
    array (
      'title' => '',
      'description' =>
      array (
        0 => '
 ',
        1 => 'param integer \$a : select[1:a|2:b|3:c]  А вот!
',
        2 => '',
      ),
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
            $this->sanitizeWS(var_export(x_parser::getParameters('justforTest',__CLASS__), true))
        );
    }

    function testCreateinput(){
        $par=x_parser::getParameters('justforTest',__CLASS__);
        $this->assertEquals(
            '<label>А вот!<select name="x_parserTest::justforTest[a]"><option value="1">a</option><option value="2" selected>b</option><option value="3">c</option></select></label>',
            x_parser::createInput($par[__CLASS__]['justforTest']['param']['a'],array(
                'x_parserTest::justforTest'=>array('a'=>2)
            ))
        );
        $this->assertEquals(
            '<label>А вот!<select name="x_parserTest::justforTest[a]"><option value="1">a</option><option value="2">b</option><option value="3" selected>c</option></select></label>',
            x_parser::createInput($par[__CLASS__]['justforTest']['param']['a'])
        );
    }
}
 