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

    function cmpCsv($f){
        $csv = csv::getcsv($f);
        $fp = fopen($f, 'r');
        if ($csv->hasbom > 0) fseek($fp, $csv->hasbom);
        $cnt = 0;
        while (!feof($fp)) {
            $row = $csv->nextRow();
            $frow = fgetcsv($fp, 20000, $csv->delim);
            if ($row != $frow) {
                $this->assertEquals($frow, $row);
                break;
            }
            $cnt++;
        }
        fclose($fp);
        printf("%s - %s\n", $cnt, $f);
    }

    public function testGetcsv0()
    {
        $this->cmpCsv('data/data-20191112-structure-20171024.csv');
    }

/*    public function testGetcsv()
    {
        $filename='data/data-20191112-structure-20171024.csv';
        $files=glob('data/*.csv');
        foreach($files as $f) {
            $this->cmpCsv($f);
        }
    }*/
}
