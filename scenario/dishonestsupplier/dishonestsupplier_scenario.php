<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 04.02.16
 * Time: 12:41
 */

/**
 * загрузка файлов на стокке.ком и проверка наличия новых
 * Class sqlfiddle_scenario
 * @tags Zakupki
 */
class dishonestsupplier_scenario extends scenario {

    function get_transport($create=true){
        static $tr=false;
        if(empty($tr) && $create){
            $tr= new ftp_transport(array(
                'uri'=>'ftp://free:free@ftp.zakupki.gov.ru/fcs_fas/'
                // ftp://free@ftp.zakupki.gov.ru/fcs_fas/unfairSupplier
                // ftp://free@ftp.zakupki.gov.ru/fcs_fas/pprf615unfairContractor
            ));
        }
        return $tr;
    }

    /**
     * Загрузка файла на сайт стокке.ком в специальное место
     * @param $contents
     * @param $contents
     * @param $name
     */
    function upload_file($contents,$name,$is_filename=false){
        $transport=$this->get_transport();
        if($transport){
            $transport->upload($contents,$name,$is_filename);
        }
    }

    /**
     * Прочитать xml с товарами от Стокке и записать информацию в таблицу.
     * @param file $xml_name :select[~dir(*.xml)]  имя файла для загрузки
     */
    function do_fillStokkegoods($xml_name){
        /**
         * временные
         */

        //<product product-id="1001">
        //<display-name xml:lang="ru">Tripp Trapp® Стульчик</display-name>

        //lap_stokke_items
        $store=array();

        $this->scaner->newhandle($xml_name);
        do {
            $item=false;
            $this->scaner
                ->until()
                ->scan('/<product product-id="([^"]+)"/m',1,'id')
                ->until('/<\/product>/');

            if ($this->scaner->found) {
                $this->scaner
                    ->scan('/<display-name xml:lang="ru">(.*?)<\/display-name>/m',1,'name');
                if ($this->scaner->found) {
                    $r=$this->scaner->getresult();
                    $this->scaner->scan('<variants>');
                    if ($this->scaner->found) continue;

                    $item=$r;
                }
            } else {
                break;
            }
            if(!$item){
                continue;
            }
            //var_dump($item);
            $store[$item['id']]=$item['name'];
            printf('%s: %s<br>',$item['id'],$item['name']);
        } while(true);

        $ids=$this->getDB()->selectCol('select articul from lap_stokke_items where articul in (?[?1])',$store);
        if(!empty($ids)){
            foreach($ids as $i){
                unset($store[$i]);
            }
        }
        if(!empty($store)){
            $insert=$this->getDB()->insertValues('insert into lap_stokke_items (?1[?2k]) values () ' .
                'on duplicate key update ?1[?2k=VALUES(?2k)];',array('articul','name'));
            foreach($store as $k=>$v){
                $insert->insert(array('articul'=>$k,'name'=>$v));
            }
            $insert->flush();
        }

    }

    function upload_zip($filename){
        echo $filename.PHP_EOL;
    }

    /**
     * прочитать список файлов
     */
    function do_scan_dir(){
        // generate item files
        // $this->upload_file('test','items.xml');
        foreach(['unfairSupplier', 'pprf615unfairContractor'] as $dir) {
            $transport = $this->get_transport();
            $raw = $transport->scan($dir);
            $times = array();
            $date = date('Y-m-d-H-i-s');
            foreach ($raw as $item) {
                if ($item['type'] == 1) {
                    printf('file `%s`, time: %s<br>' . PHP_EOL, $item['filename'], date('d-m-Y H:i:y', $item['mtime']));
                    $times[$item['filename']] = $item['mtime'];

                    $this->joblist->append_scenario('upload_zip', [$dir.$item['filename']]);

                }
            }
        }
    }
}