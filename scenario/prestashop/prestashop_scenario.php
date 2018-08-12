<?php
/**
 * Работа с сайтом на престашоп
 * Class prestashop_scenario
 */
class prestashop_scenario extends scenario
{
    var $config=array();

    function __construct(){
        $this->config['prestashop']=$_SERVER['DOCUMENT_ROOT'];
    }

    /**
     * Поиск по имени бренда его ID
     * @param $name
     */
    function do_find_brand($name){
        // добавляем окружение prestashop
 //       error_reporting(E_ALL); ini_set('display_errors',1);
       // include ($this->config['prestashop'].'/config/config.inc.php');
  //      include_once($this->config['prestashop'].'/init.php');
 //       error_reporting(E_ALL); ini_set('display_errors',1);
        echo '111';
        echo $this->config['prestashop'];
       // var_dump(ManufacturerCore::getIdByName($name));
//        die ('all jobs complete!');
    }
}
