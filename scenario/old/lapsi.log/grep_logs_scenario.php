<?php
/**
 *  анализатор логов
 */


/**
 * Читалка длинных логов. Греп по IP.
 * Class grep_logs_scenario
 * @property scaner $scaner
 * @tags lapsi
 */
class grep_logs_scenario extends scenario
{

    /**
     * Сканирование логов по IP
     * @param string $log :select[:все|~dir(*.{gz,log})] лог для поиска
     * @param string $pattern :text IP для поиска
     * @param string $text :text текст для поиска
     */

    function do_scanlog($log,$pattern,$text){
        $this->scaner->newhandle($log);$reg1='';
        if(!empty($text)) $reg1=$text;
        if(!empty($pattern)) $reg='~'.preg_quote($pattern).'[^\n]+~';
        else {
            $reg=$reg1;
        }
        $reg1='~\.(jpe?g|gif|js|ico|css|png)(\?\d+?|)\s+HTTP/1~';
        $reg2='~YandexBot/3\.0|AhrefsBot/5\.0~';
        do {
            $this->scaner->scan($reg,0);
            if($this->scaner->found){
                $result=$this->scaner->getresult();
                $result=$result[0];
                if(preg_match($reg1,$result) || preg_match($reg2,$result)) continue;
                echo $result;
            } else
                break;
        } while (true);
    }

}
