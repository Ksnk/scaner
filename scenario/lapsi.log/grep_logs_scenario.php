<?php
/**
 *  анализатор логов
 */


/**
 * Читалка длинных логов. Греп по IP.
 * Class grep_logs_scenario
 */
class grep_logs_scenario extends scenario
{

    /**
     * @var scaner
     */
    var $scaner;

    /**
     * парсер логов - простой греп,  поиск по IP, выкидываем обращения к картинкам и скриптам
     * @param $log
     * @param $pattern
     */
    function scan_access_log($log,$pattern){
        $this->scaner->newhandle($log);
        do {
            $this->scaner->scan('~'.preg_quote($pattern).'[^\n]+~',0);
            if($this->scaner->found){
                $result=$this->scaner->getresult();
                if(!preg_match('~\.(jpe?g|gif|js|ico|css|png)(\?\d+?|)\s+HTTP/1~',$result[0])){
                    echo $result[0]."\n\n";
                }
            } else
                break;
        } while (true);
    }

    function initialize(){
        $config = (object)array(

        );
        $config->scaner = new scaner();
        $config->joblist = new joblist();
        $config->scenario = $this;

        $config->scaner->init($config);
        $config->joblist->init($config);
        $config->scenario->init($config);

    }

}
/*
ini_set('display_error', 1);
error_reporting(E_ALL);

echo '<pre>';

$jobs = $config->joblist;
$jobs->append_scenario('scan_access_log','lapsi.msk.ru.access.log.0.gz','62.133.162.140');

$config->scenario->result['result'] = array();
//print_r($jobs);
while ($jobs->donext()) {
    ;
}
*/