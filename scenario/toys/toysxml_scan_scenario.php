<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

/**
 * Работа с сайтом bragindesign.ru - сканирование всех товаров.
 * Class braindesign_scan_scenario
 */
class toysxml_scan_scenario extends scenario
{

    static $single=null;

    static function get($par){
        if (!self::$single) {
            self::$single = new self($par);
        }
        return self::$single;
    }

    static function xCol($str){
        //Наличие ставится следующим образом: если артикула вообще нет в прайсе, а в админке есть, то ставится количество 0. Если в прайсе артикулы есть, то <5 ставим 5, 5-10 ставим 10, более >10 ставим 50.
        //3. Если товара у нас нет, а в прайсе есть, то такой товар добавляем во временную категорию. Можно я там создала категорию Временная по игрушкам.
        if($str=='<-5') return 5;
        if($str=='5-10') return 10;
        return 50;
    }

    /**
     * Конвертировать XLS в YML
     * @param $file : file[*.xls|*.xlsx] xls файл c ценами
     * @param string $ymllname
     * @param $action :checkbox[1:XLM->YML|2:YML->inrium]
     */
    function do_readXmlFile($file,$ymllname='toys.yml',$action=0xf){
        if(is_readable('../../tools/PHPExcel/PHPExcel.php'))
            include_once('../../tools/PHPExcel/PHPExcel.php');
        else
            include_once('../../PHPExcel/PHPExcel.php');

        $titles=array(
            "Производитель"=>-1,
            "Наименование"=>-1,
            "Артикул"=>-1,
            "Штрих-код"=>-1,
            "Кол. на складе"=>-1,
            "Действ. базовая цена"=>-1,
            "Акция"=>-1,
            "Код"=>-1
        );
        $title_found=false;
        //print_r($file);
        //ob_start();
        $objPHPExcel = PHPExcel_IOFactory::load( $file );
        $objPHPExcel->setActiveSheetIndex(0);

        // вычисляем начало таблички
        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
            // Получим итератор строки и пройдемся по нему циклом
            $values=$worksheet->toArray(null, true, false, true);
            //print_r($values); break ;
            $maxrow=5;$title_found=-1;
            foreach($values as $rkey=>$row){
                // Получим итератор ячеек текущей строки
                // print_r($values); break 2;
                // Пройдемся циклом по ячейкам строки
                if($title_found<0){
                    $maxrow--; if($maxrow<0) break;
                    $foundvals=0;
                    foreach($row as $ckey=>$val){
                        // Берем значение ячейки
                        foreach($titles as $k=>&$v){
                            if ($val==$k){
                                $v=$ckey;
                                $foundvals++;
                            }
                        }
                        unset($v);
                    }
                    if($foundvals>3){
                        $title_found=$rkey;
                        print_r($titles);
                    }
                } else {
                    break;
                }
            }
            if($title_found>0){
                $cnt=0;
                $handle=fopen($ymllname,'w');
                fwrite($handle,
                    '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE yml_catalog SYSTEM "shops.dtd">
        <yml_catalog date="2016-05-18 12:10">
            <shop>
                <name>'.$file.'</name>
                <company>'.$file.'</company>
                <currencies>
                    <currency id="RUB" rate="1" />
                </currencies>
                <categories/>
<offers>
'
                );

                do{
                    $cnt++;
                    $title_found++;
                    $result=array();
                    $totalempty=true;
                    foreach($titles as $k=>$v){
                        $result[$k]=trim($values[$title_found][$v]);
                        if(!empty($result[$k]))
                            $totalempty=false;
                    }
                    if(!$totalempty){
                        $tab="\t";
                        fwrite($handle,
                            '<offer id="'.$result['Код'].'" type="vendor.model" available="true">
'.$tab.'<vendor>'.$result['Производитель'].'</vendor>
'.$tab.'<vendorCode>'.$result['Артикул'].'</vendorCode>
'.$tab.'<price>'.$result['Действ. базовая цена'].'</price>
'.$tab.'<currencyId>RUB</currencyId>
'.$tab.'<param name="ean13">'.$result['Штрих-код'].'</param>
'.$tab.'<name>'.$result['Наименование'].'</name>
'.$tab.'<param name="stock">'.self::xCol($result['Кол. на складе']).'</param>
</offer>
'
                        );
                    }
                } while($title_found<count($values) && !$totalempty);

                fwrite($handle,
                    '</offers>
        </shop>
        </yml_catalog>'
                );
                echo $cnt.' items found';
                break;
            }
        }

    }

    /**
     * Импортировать YML в базу товаров ИНРИУМ
     * @param $file :file[*.xml|*.yml] имя файла для импорта
     * @param $brands :text список брендов через запятую
     */
    function do_importYML($file,$brands){
        if(is_readable('../../modules/yandeximport/yandeximport.php')){

        }
    }

    /**
     * Прочитать 1 страницу с товарами.
     */
    /*
    function do_test1page(){
        $this->joblist->append_scenario('scanitem',array("https://bragindesign.ru/product/tobonn-орех/"));

    }
    */

}
