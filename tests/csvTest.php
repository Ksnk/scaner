<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 25.11.2019
 * Time: 21:44
 */

use Ksnk\scaner\csv;

class csvTest extends PHPUnit_Framework_TestCase
{

    function testClassExists(){
        $this->assertNotEmpty(new csv());
    }

    public function testGetcsv()
    {
        $filename='data/data-20191112-structure-20171024.csv';
        $csv=csv::getcsv($filename);
//$csv=csv::getcsv('test/xcraft.txt');
//$csv=csv::getcsv('test/xcraft.3.txt'); utf-16LE
        $fp=fopen($filename,'r');
        if($csv->hasbom>0) fseek($fp,$csv->hasbom);
        $cnt=0;
        while(true){
            $row=$csv->nextRow();
            $frow=fgetcsv($fp,20000,$csv->delim);
            if($row!=$frow){
                $this->assertEquals($frow,$row);
                break;
            }
            $cnt++;
        }
        fclose($fp);
        echo $cnt;
    }
}
