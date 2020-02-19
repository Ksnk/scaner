<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.04.16
 * Time: 13:46
 */
namespace Ksnk\scaner;

/**
 * Проверка возможностей x_parcer
 * Class sqlfiddle_scenario
 * @tags ~debug
 */
class xparcer_scenario extends scenario {

    /**
     * Тестировать
     * @param string $a :radio[1:one|3:two|4:four|5:five] 1-й параметр
     * @param $b
     * @param int|string $c :select[one|3:two|4:four|five] 3-й параметр
     * @param array $d :checkbox[1:да|2:заодно и удалить] Полностью?
     */
    function do_test0($a,$b,$c=4,$d=array()){
        /*
        $parsed=parse_url($url0);
        if($parsed['scheme']=='https'){
            $values[':has_https']=1;
        } else {
            // собираем url обратно
            $parsed['scheme']='https';
            $urls = '';
            foreach (['scheme' => '%s:', 'host' => '//%s', 'path' => '%s', 'query' => '?%s'] as $k => $v) {
                if (!empty($parsed[$k])) $urls .= sprintf($v, $parsed[$k]);
            }
            $result = _check_url_get_info($http, $urls, array('method' => HTTP_Request2::METHOD_GET, 'http2_config' => array('store_body' => TRUE)));
            if (!$result['result']) {
                $values[':has_https']=0;
            }
        }
        $url404='';$parsed['path']='/i/d.like.to.check.404.page/';
        foreach (['scheme' => '%s:', 'host' => '//%s', 'path' => '%s', 'query' => '?%s'] as $k => $v) {
            if (!empty($parsed[$k])) $url404 .= sprintf($v, $parsed[$k]);
        }
        */
        $port = ($_SERVER["SERVER_PORT"] != '80' ? ':' . $_SERVER["SERVER_PORT"] : '');
        printf("%s\n\$a=`%s`,\$b=`%s`,\$c=`%s`, \$d=`%s`"
            ,'http'.(isset($_SERVER["HTTPS"])?'s':'').'://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]
            ,$a,$b,$c,empty($d)?'empty':implode(',',$d));
    }

    /**
     * проверка загрузки файла
     * @param string $file :file выберите файл
     * @param string $files :files выберите файлы
     */
    function do_fileupload($file,$files){
        print_r($file);print_r($files);
    }
    /**
     * phpinfo
     */
    function do_phpinfo(){
        $this->outstream(self::OUTSTREAM_HTML_FRAME);
        phpinfo();
    }
}
