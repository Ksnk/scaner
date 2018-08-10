<?php
/**
 * Утилиты работы с xcraft статистикой
 */
class xcraft_scenario extends scenario {

    function _init(){
        ENGINE::set_option(array(
            'database.options'=>'nocache',

            'database.host'=>'localhost',
            'database.user'=>'root',
            'database.password'=>'',
            'database.base'=>'lapsi',
            'database.prefix'=>'xxx',

            'engine.aliaces' => array(
                'Database' => 'xDatabaseLapsi'
            ),
        ));
    }
    /**
     * Сохранить онлайн лог.
     * @param string $a :textarea 1-й параметр
     * @menu xcraft
     */
    function do_storeonline($a){
        //$this->_init();
        $scaner=new scaner();
        $scaner->newbuf($a);//=1;
        do {
            $scaner->scan('/([^\t]*)\t([^\t]*)\t([^\t]*)\t([^\n]*)/',1,'name',2,'aliance',3,'score',4,'time');
           // print_r($scaner);
            if($scaner->found){
               // echo $cnt++;
                //if($cnt>5) break;
                print_r($scaner->getresult());
            } else
                break;
        } while (true);
        //print_r($a);
    }

}