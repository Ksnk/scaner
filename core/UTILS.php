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

    static function sphinxSearch($q, $index = 'titles',$match=7,$limit=30)
    {
        $suffix=' @brand 74606'; // Ищем только Стокке
        if (empty($q)) return '';
        if(!UTILS::detectUTF8($q))$q=iconv('cp1251','utf-8',$q);

        require_once $_SERVER["DOCUMENT_ROOT"] . "/../lapsi.msk.ru/az/helpers/sphinxapi.php";
        $cl = new SphinxClient();
        $host = 'localhost';
        $port = '9312';
        $cl->SetServer($host, $port);
        $cl->SetLimits(0, 1000);
        $q_matches = '';

        // поиск строки по названиям
        while (true) {
            if($match & 1){
            $cl->SetLimits(0, $limit);
            $cl->SetMatchMode(SPH_MATCH_ALL);

            $q_matches = $cl->Query(html_entity_decode($q, ENT_NOQUOTES, 'UTF-8').$suffix, $index);
            if (is_array($q_matches) && isset($q_matches["matches"])) break;
            }
 /*           if($match & 4){
                $cl->SetLimits(0, $limit);
                $cl->SetMatchMode(SPH_MATCH_ALL);
                    $q_matches = $cl->Query('*' . preg_replace('/\s+/', '* *', html_entity_decode(strtr($q, '-(), .', '     ') . '*', ENT_NOQUOTES, 'UTF-8')).$suffix, $index);
            if (is_array($q_matches) && isset($q_matches["matches"])) break;

            }*/
            if($match & 2){
            $cl->SetLimits(0, $limit);
            $cl->SetMatchMode(SPH_MATCH_ANY);
            $q_matches = $cl->Query(html_entity_decode($q, ENT_NOQUOTES, 'UTF-8').$suffix, $index);
            if (is_array($q_matches) && isset($q_matches["matches"])) break;
            }

        }
        ENGINE::debug($q_matches);

        if (is_array($q_matches) && isset($q_matches["matches"])) {
            $q_matches = array_keys($q_matches["matches"]);
        } else
            $q_matches = array();
        return $q_matches;
    }
}