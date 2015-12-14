<?php
/**
 *  анализатор логов
 */

/**
 * Читалка логов сфинкса, преобраование его в дамп-лог mySql
 * Class grep_logs_scenario
 * @property scaner $scaner
 */
class lapsitv_scenario extends scenario
{
    /**
     * Сканирование логов по IP
     * @param string $log :select[:все|~dir(*.{gz,log})] лог для поиска
     * @param string $after :text после такого времени
     * @param string $before :text до такого времени
     */
    function do_uploadsite($url='http://lapsi.com.local/'){

    }

}
