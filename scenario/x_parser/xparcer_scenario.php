<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.04.16
 * Time: 13:46
 */

/**
 * Проверка возможностей x_parcer
 * Class sqlfiddle_scenario
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
        $port = ($_SERVER["SERVER_PORT"] != '80' ? ':' . $_SERVER["SERVER_PORT"] : '');
        echo 'http'.(isset($_SERVER["HTTPS"])?'s':'').'://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        printf('  $a=`%s`,$b=`%s`,$c=`%s`, $d=`%s` port=`%s`',$a,$b,$c,empty($d)?'empty':implode(',',$d),$port);
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
        phpinfo();
    }

    /**
     * сканировать дамп на строчные буквы в ссылках
     */
    function do_scandump(){
        $this->scaner->newhandle('D:\\projects\\specokraska.ru\\specokraska_2017-09-28_20-15-00.sql');
        do {
            $this->scaner
                ->scan('/[\"\'](\/[^#][^\/\'\"]*[A-Z][^\/"\']+)/m',1,'id');

            if ($this->scaner->found) {
                $r=$this->scaner->getresult();
                    printf("\n%s ",$r['id']);

            } else {
                break;
            }
            //var_dump($item);
        } while(true);
        echo "\n-----------------------------------";

    }
}
