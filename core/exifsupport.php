<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 04.02.16
 * Time: 15:49
 */

class exifsupport {

    var $sections=array();

    function readmarker($h){
        $x=unpack('n',fread($h,2));
        return $x[1];//['chars1'].$x['chars2'];
    }

    function read($filename){
        $this->sections=array();
        $h=fopen($filename,'r');
        $z=$this->readmarker($h);
        if($z!=0xFFD8)
            print_r($z);

        $cnt=0;
        while(!feof($h) && $cnt++<20) {
            $z=$this->readmarker($h);
            if ($z>0xFF00) {
                $len=unpack('n',fread($h,2));
                if($z==0xFFe1) {
                    var_dump($z,$len);
                }
                fseek($h,$len[1]-2,SEEK_CUR);
            } else
                break;
        }
        fclose($h);
    }

}