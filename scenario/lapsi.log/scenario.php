<?php
/**
 *  анализатор логов
 */
include_once '../../autoload.php';

$config = (object)array(

);

/**
 * Читалка длинных логов. Греп по IP.
 * Class scenario
 */
class scenario extends base
{

    /**
     * @var scaner
     */
    var $scaner;

    /**
     * @var joblist
     */
    var $joblist;

    /**
     * Просто результат операции, чтобы было
     * что выковыривать.
     * @var mixed
     */
    var $result;

    /**
     * парсер логов - простой греп, навроде поиск по IP
     * @param $log
     * @param $pattern
     */
    function scan_access_log($log,$pattern){
        $this->scaner->newhandle($log);
        do {
            $this->scaner->scan('~'.preg_quote($pattern).'[^\n]+~',0);
            if($this->scaner->found){
                if(!preg_match('~\.(jpe?g|gif|js|ico|css|png)(\?\d+?|)\s+HTTP/1~',$this->scaner->result[0])){
                    echo $this->scaner->result[0]."\n\n";
                }
                $this->scaner->result=array();
            } else
                break;
        } while (true);
    }

}

ini_set('display_error', 1);
error_reporting(E_ALL);

echo '<pre>';
$config->scaner = new scaner();
$config->joblist = new joblist();
$config->scenario = new scenario();


$config->scaner->init($config);
$config->joblist->init($config);
$config->scenario->init($config);

/** @var joblist $jobs */
$jobs = $config->joblist;
$jobs->append_scenario('scan_access_log','lapsi.msk.ru.access.log.0','62.133.162.140');

$config->scenario->result['result'] = array();
//print_r($jobs);
while ($jobs->donext()) {
    ;
}
