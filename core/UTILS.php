<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.12.15
 * Time: 12:36
 */

class UTILS {

    static function detectUTF8 ($string){
        return preg_match('%(?:
       [\xC2-\xDF][\x80-\xBF]        		# non-overlong 2-byte
       |\xE0[\xA0-\xBF][\x80-\xBF]          # excluding overlongs
       |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}   # straight 3-byte
       |\xED[\x80-\x9F][\x80-\xBF]          # excluding surrogates
       |\xF0[\x90-\xBF][\x80-\xBF]{2}    	# planes 1-3
       |[\xF1-\xF3][\x80-\xBF]{3}           # planes 4-15
       |\xF4[\x80-\x8F][\x80-\xBF]{2}    	# plane 16
       )+%xs', $string);
    }

    /**
     * Класс-хелпер для отслеживания времени
     * @param bool $print
     * @return mixed
     */
    static function mkt($print=false){
        static $tm ; $ttm = $tm ; $tm= microtime(1) ;
        if($print) {
            printf(" %.03f sec spent%s (%s)\n" ,$tm-$ttm,is_string($print)?' for '.$print:'',date("Y-m-d H:i:s"));
            return false;
        } else
            return $tm-$ttm;
    }
}