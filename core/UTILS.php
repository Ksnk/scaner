<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.12.15
 * Time: 12:36
 */

class UTILS {
    
    static $months_rp=array('Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря');

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
    static function mkt($print=false,$save=true){
        static $tm ; $ttm = $tm ; if($save)$tm= microtime(1) ;
        if($print) {
            printf(" %.03f sec spent%s (%s)\n" ,$tm-$ttm,is_string($print)?' for '.$print:'',date("Y-m-d H:i:s"));
            return false;
        } else
            return microtime(1)-$ttm;
    }

    static function sphinxSearch($q, $index = 'titles',$match=7,$limit=30)
    {
        $suffix='';//' @brand 74606'; // Ищем только Стокке
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

    /**
     * scan phar files for files matches the mask
     * @param $phar
     * @param $mask
     * @return array
     */
    static function scanPharFile($phar,$mask){
        if(!$phar instanceof Phar){
            $phar=new Phar($phar);
        }
        $iterator = new RecursiveIteratorIterator($phar);
        $result=array();
        /** @var $f PharFileInfo */
        foreach($iterator  as  $f) if (preg_match($mask,$f)){
           // echo ' found '.$f."\n";
            $result[]=$f;
        }
        return $result;
    }

    private static function _relist(&$curr,&$result,$path){
        foreach($curr as $x=>&$y){
            if(!is_array($y))
                $result[]=$path.'|'.$x;
            else
                UTILS::_relist($y,$result,$path.'|'.$x);
        }
    }

    static function getallheaders(){
        if (function_exists('getallheaders')){
            return getallheaders();
        } else {
            $headers = array();
            foreach ($_SERVER as $name => $value)
            {
                if (substr($name, 0, 5) == 'HTTP_')
                {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
            return $headers;
        }
    }

    static function uploadedFiles(){
        $uploaded=array();
        $paths=array();

        foreach($_FILES as $x=>$y){
            if(!empty($_FILES[$x]['name'])){
                UTILS::_relist($_FILES[$x]['name'],$paths,$x.'|{{}}');
            }
        }
        foreach($paths as $p){
            $uploaded[]=array(
                'name'=>UTILS::val($_FILES,str_replace('{{}}','name',$p),'x'),
                'error'=>UTILS::val($_FILES,str_replace('{{}}','error',$p),'x'),
                'tmp_name'=>UTILS::val($_FILES,str_replace('{{}}','tmp_name',$p),'x'),
                'path'=>$p
            );
        }
        return $uploaded;
    }

    /**
     * convert simple DOS|LIKE mask with * and ? into regular expression
     * so
     *   * - all files - its'a a difference with the rest "select by mask"
     *   *.xml - all files with xlm extension
     *   *.jpg|*.jpeg|*.png -
     *   hello*world?.txt - helloworld1.txt,helloXXXworld2.txt, and so on
     *
     * @param $mask - simple mask
     * @param bool $last - mask ends with last simbol
     * @param bool $isfilemask - is this mask used to filter filenames?
     * @return string
     */
    static function masktoreg($mask, $last = true, $isfilemask = true)
    {
        if (!empty($mask) && $mask{0} == '/') return $mask; // это уже регулярка
        if($isfilemask){
            $star='[^:/\\\\\\\\]';//
            $mask=explode('|',$mask);
        } else {
            $star='.';//
            $mask=array($mask);
        }
        /* so create mask */
        $regs = array(
            '~\[~' => '@@0@@',
            '~\]~' => '@@1@@',
            '~[\\\\/]~' => '@@2@@',
            '/\*\*+/' => '@@3@@',
            '/\./' => '\.',
            '/\|/' => '\|',
            '/\*/' => $star . '*',
            '/\?/' => $star,
            '/#/' => '\#',
            '/@@3@@/' => '.*',
            '/@@2@@/' => '[\/\\\\\\\\]',
            '/@@1@@/' => '\]',
            '/@@0@@/' => '\[',
        );
        $r=array();
        foreach($mask as $m)
            $r[]=preg_replace(
                    array_keys($regs), array_values($regs), $m
                ) . ($last ? '$' : '');
        return '#' . implode('|',$r). '#iu';
    }

    static function val($rec,$disp,$default=''){
        $x=explode('|',$disp);
        $v=$rec;
        foreach($x as $xx){
            if(is_object($v)){
                if(property_exists($v,$xx)){
                    $v=$v->$xx;
                } else {
                    $v=$default;
                    break;
                }
            } elseif(isset($v[$xx])){
                $v=$v[$xx];
            } else {
                $v=$default;
                break;
            }
        }
        return $v;
    }

}