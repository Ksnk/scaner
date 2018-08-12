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
 */
class stokke_com_scenario extends scenario {

    /**
     * @return xDatabaseLapsi
     */
    function getDB(){
        static $db=false;
        if(!$db){
            ENGINE::set_option(array(
                'database.options'=>'nocache',
                'database.host' => 'localhost',
                'database.user' => 'ulapsi',
                'database.password' => 'G87FZOqw',
                'database.base' => 'u178433',
                'database.prefix' => '',

                'engine.aliaces' => array(
                    'Database' => 'xDatabaseLapsi'
                ),
            ));
            $db=ENGINE::db('nocache debug');
            Autoload::register(array(
                realpath($_SERVER["DOCUMENT_ROOT"] . "/../m.lapsi.ru/system/model/lapsi"),
                realpath($_SERVER["DOCUMENT_ROOT"] . "/../m.lapsi.ru/system")
            ));
        }
        return $db;
    }

    function get_transport($create=true){
        static $tr=false;
        if(empty($tr) && $create){
            $tr= new sftp_transport(array(
                'host'=>'stoftp.stokke.com',
                'name'=>'ecom-russia',
                'password'=>'4Jznrt425P5QKK9V',
                'root'=>'/ecommerce/'
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

    function getItemList(){
        $items=array();
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
    /**
     * Сравниваем стокке с нашими товарами
     */
    function do_sinchStokke(){
        $o=$this->getDB()->select('select e.id, s.articul, s.name as stokename from lap_stokke_items as s join lapsi_elements as e on e.id=s.item');
        echo '<table>';
        foreach($o as $oo){
            $item=Model_Item::get($oo['id']);
            echo '<tr>';
            echo '<td>'.$oo['id'].'</td>';
            echo '<td>'.$oo['articul'].'</td>';
            echo '<td>'.$item->itemname.'</td>';
            echo '<td>'.$oo['stokename'].'</td>';
            echo '</tr>';
        }
        echo '</table>';
    }


    /**
     * создать файл с товарами
     */
    function do_createLapsiSklad(){
        $ids=$this->getDB()->select('select articul,item from lap_stokke_items where item>0');
        $data='';
        $date = time() + (7 * 24 * 2);//('P2D'));
        $date=date('Y-m-d\TH:i:s.000P',$date);
        echo $date;

        foreach($ids as $i){
            $item=Model_Item::get($i['item']);
            if($item->canbuy && $item->price) {
  /*              $riad = ENGINE::db()->selectRow("SELECT * FROM `ostatki` WHERE ID_ELEMENT=? limit 1", $this->item['id']);
                $numb=0;
                foreach(array('S_MEBEL_SEDOVA','S_MEBEL_SEDOVAVARNA','S_AUTO_SEDOVA','S_KOLYA_SEDOVA','S_KOLYA_INFANSI','S_OPT','S_MEBEL_ENGELSA','S_AUTO_ENGELSA','S_KOLYA_ENGELSA') as $x){
                    if(!empty($riad[$xx]))
                        $numb+=$riad[$xx];
            }*/
            $data.=sprintf('
            <record product-id="%s">
                <allocation>%3$s</allocation>
                <allocation-timestamp>%s</allocation-timestamp>
                <perpetual>false</perpetual>
                <preorder-backorder-handling>none</preorder-backorder-handling>
                <preorder-backorder-allocation>0</preorder-backorder-allocation>
                <ats>%3$s</ats>
                <on-order>0</on-order>
                <turnover>0</turnover>
            </record>',$i['articul'],$date,20);
            }
        }
        if(!empty($data)){
            $data='<?xml version="1.0" encoding="UTF-8"?>
<inventory xmlns="http://www.demandware.com/xml/impex/inventory/2007-05-31">
    <inventory-list>
        <header list-id="LAPSI_SPB_RU">
            <default-instock>false</default-instock>
            <description>Lapsi, RU</description>
            <use-bundle-inventory-only>false</use-bundle-inventory-only>
        </header>

        <records>'.$data.'
        </records>
    </inventory-list>
</inventory>';
            file_put_contents(dirname(__FILE__).'/LAPSI_SPB_RU.xml',$data);
        }
    }

    /**
     * создать файл с ценами
     */
    function do_createLapsiPrices(){
        $ids=$this->getDB()->select('select articul,item from lap_stokke_items where item>0');
        $data='';

        foreach($ids as $i){
            $item=Model_Item::get($i['item']);
            if($item->canbuy && $item->price){
                //ENGINE::debug($item->price,$item);
                $data.=sprintf('
            <price-table product-id="%s">
				<amount quantity="1">%s.00</amount>
			</price-table>',$i['articul'],$item->price);
            }
        }
        if(!empty($data)){
            $data='<?xml version="1.0" encoding="UTF-8"?>
<pricebooks xmlns="http://www.demandware.com/xml/impex/pricebook/2006-10-31">
	<pricebook>
		<header pricebook-id="RUB-ECRU-prices">
			<currency>RUB</currency>
			<display-name xml:lang="x-default">Price list</display-name>
			<online-flag>true</online-flag>
		</header>
		<price-tables>
'.$data.'
		</price-tables>
	</pricebook>
</pricebooks>
';
            file_put_contents(dirname(__FILE__).'/RUB-ECRU.xml',$data);
        }
    }

    /**
     * Вывести с названиями все расцветки товара
     * @param $id
     */
    function do_colors($id){
        $this->getDB();
        $item=Model_Item::get($id);
        $colors=$item->colors;
        foreach($colors as $c){
            echo $c->itemname.' http://m.lapsi.ru/'.$c->item_url.'</br>';
        }
    }

    /**
     * Поискать все ненайденные пока товары Стокке
     * @param integer $limit количество товаров
     */
    function do_scanLapsiItems($limit=400){
        $ids=$this->getDB()->select('select articul,name from lap_stokke_items where item=0 limit ?',$limit);

//        print_r($ids);
        $clear=array();

        foreach($ids as $item) {
            $name=
                $item['name'];
           // echo htmlspecialchars($name);
           // $xids=UTILS::sphinxSearch($name,'titles',7);
            $yids=$this->getDB()->select(
                'select IBLOCK_ELEMENT_ID FROM  `b_iblock_element_property`
                WHERE  `VALUE` LIKE  \'%?x%\' limit 30'// and IBLOCK_ELEMENT_ID in(?[?2])'
                ,$item['articul']);//,$xids);
            //print_r($item);
           // print_r($xids);
           // print_r($yids);
            $ii=array();
            foreach($yids as $i){
                $x=Model_Item::get($i['IBLOCK_ELEMENT_ID']);
                if(!$i || preg_match('/комплект/iu',$x->name))
                    continue;
                if($x->brand!=74606)
                    continue;
                $ii[]=$i['IBLOCK_ELEMENT_ID'];
                echo $item['articul'].' '.htmlspecialchars($name).' &lt; <a href="'.htmlspecialchars($x->lapsi_elementurl).'">'.$x->itemname.'</a><br>';
            }
            if(count($ii)==1){
                $this->getDB()->update('update lap_stokke_items set item=? where articul=?'
                    ,$ii[0],$item['articul']);
            } else {
                if(count($ii)>1)
                    echo ' Слишком много вариантов '.$item['articul'].' '.$item['name'].'</br>';
                else {
                    echo ' Не найден '.$item['articul'].' '.$item['name'].'</br>';
                }
                $clear[]=$item['articul'];
            }
        }
        if(!empty($clear)){
            $this->getDB()->update('update lap_stokke_items set item=-1 where articul in (?[?2])'
                ,$clear);
        }
    }
    /**
     * Загрузить список товаров и сканировать новые заказы
     */
    function do_scanstokke(){
        $stage='staging';
        // generate item files
        // $this->upload_file('test','items.xml');
        $transport=$this->get_transport();
        $raw=$transport->scan();
        $times=array();
        $date=date('Y-m-d-H-i-s');
        foreach($raw as $item){
            if($item['type']==1){
                printf('file `%s`, time: %s<br>',$item['filename'],date('d-m-Y H:i:y',$item['mtime']));
                $times[$item['filename']]=$item['mtime'];
            }
        }
        $files=array(
            "LAPSI_SPB_RU.xml"=>$stage."/inbound/inventory/LAPSI_SPB_RU.xml",
            "RUB-ECRU.xml"=>$stage."/inbound/pricebook/RUB-ECRU-".$date.".xml"
        );
        foreach($files as $file=>$dir){
            if(!isset($times[$file]) || $times[$file]<filemtime(dirname(__FILE__).'/'.$file)){
                echo 'uploading '.$file.' as '.$dir.'<br>';
                $this->upload_file(dirname(__FILE__).'/'.$file,$dir,true);
            }
        }
    }
}