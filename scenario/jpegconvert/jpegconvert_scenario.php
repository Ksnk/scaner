<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 04.02.16
 * Time: 16:24
 */

class jpegconvert_scenario {


    /**
     * читать exif из jpeg
     */
    function do_convert(){
        $exif=new exifsupport();
        $exif->read(__DIR__.'/kupon-big2.jpg');
    }

}