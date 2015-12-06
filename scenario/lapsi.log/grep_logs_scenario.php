<?php
/**
 *  анализатор логов
 */


/**
 * Читалка длинных логов. Греп по IP.
 * Class grep_logs_scenario
 * @property scaner $scaner
 */
class grep_logs_scenario extends scenario
{

    /**
     * Сканирование логов по IP
     * @param string $log :select[:все|~dir(*.{gz,log})] лог для поиска
     * @param string $pattern :text IP для поиска
     */

    function do_scanlog($log,$pattern){
        $this->scaner->newhandle($log);
        do {
            $this->scaner->scan('~'.preg_quote($pattern).'[^\n]+~',0);
            if($this->scaner->found){
                $result=$this->scaner->getresult();
                $result=$result[0];
                if(!preg_match('~\.(jpe?g|gif|js|ico|css|png)(\?\d+?|)\s+HTTP/1~',$result)){
                    echo $result;
                }
            } else
                break;
        } while (true);
    }

}
