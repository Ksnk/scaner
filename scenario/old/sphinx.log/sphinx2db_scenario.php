<?php
/**
 * поиск сфинксом
 */


/**
 * Читалка логов сфинкса, преобраование его в дамп-лог mySql
 * Class grep_logs_scenario
 * @property scaner $scaner
 * @tags lapsi
 */
class sphinx2db_scenario extends scenario
{
    /**
     *
     * Сканирование логов по IP
     * @param string $log :select[:все|~dir(*.{gz,log})] лог для поиска
     * @param string $after :text после такого времени
     * @param string $before :text до такого времени
     */

    function do_todb($log,$after,$before){
        $status=array('jumps'=>0,'linesread'=>0,'linesmatch'=>0);
        $this->scaner->newhandle($log);
        $vilka=array(0,$this->scaner->finish,0);
        $cnt=100;
        if(empty($after)) $after=0; else if(is_string($after))$after=strtotime($after);
        if(empty($before)) $before=time(); else if(is_string($before))$before=strtotime($before);

        // поиск нужной даты
        $stage=1;
        UTILS::mkt();
        // примерно нашли, работаем
        do {
            $this->scaner->scan('/^\[(.*?)\].+?$/m',0,'tool',1,'time');
            if ($this->scaner->found) {
                $result=$this->scaner->getresult();
                // sometimes you can' use strtotime: $time=strtotime($result['time']); -------------
                $time=date_parse_from_format('D M d H:i:s.u Y',$result['time']); // Tue May 14 12:52:22.434 2013
                $dt=new DateTime();
                $dt->setDate($time['year'],$time['month'],$time['day']);
                $dt->setTime($time['hour'],$time['minute'],$time['second']);
                $time=$dt->getTimestamp();
                // -----------
                if(!$time) continue;
                if ($stage==0) {
                    if ($after<=$time && $before>=$time) {
                        $status['linesmatch']++;
                        if(preg_match('/ 0 \(0,1000\)\] \[faces\](.*)$/',$result['tool'],$mm)) {
                            if(!UTILS::detectUTF8($result['tool']))
                                echo $result['tool']."\n";
                        }
                    } else if($time>$before) {
                        break;
                    }
                    $status['linesread']++;
                    continue;
                } else if($after>$time) {
                    $vilka[0]=$vilka[2];
                } else { //($after<$time)
                    $vilka[1]=$vilka[2];
                }
                $vilka[2]=($vilka[0]+$vilka[1])>>1;
                if($vilka[2]-$vilka[0]<40000) {
                    if($after<$time){
                        $this->scaner->position($vilka[0]);
                    }
                    UTILS::mkt('jumping');
                    $stage=0;
                } else {
                    $this->scaner->position($vilka[2]);
                    $status['jumps']++;
                }
            } else
                break;
        } while (true);
        UTILS::mkt('reading');
        printf('jumps:%d,nomatch:%d,match:%d ', $status['jumps'], $status['linesread']-$status['linesmatch'], $status['linesmatch']);
    }

}
