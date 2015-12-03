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
                $result=$result[0];/*
            $this->scaner->scan($pattern);
            if($this->scaner->found){
                $result=$this->scaner->getline();*/
                if(!preg_match('~\.(jpe?g|gif|js|ico|css|png)(\?\d+?|)\s+HTTP/1~',$result)){
                    $this->result[]= $result;
                }
            } else
                break;
        } while (true);
    }

}
