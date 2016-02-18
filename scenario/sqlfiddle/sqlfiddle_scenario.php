<?php
/**
 *  анализатор логов
 */


/**
 * Заменитель SQL-fiddle
 * Class sqlfiddle_scenario
 */
class sqlfiddle_scenario extends scenario
{
    /**
     *
     * Выполнение sql запросов, через ';'. a-la sqlfiddle
     * @param string $sql :textarea sql
     */

    function do_sqlfiddle($sql){

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

        $requests=explode(';',$sql);
        foreach($requests as $sqls){
            $sqls=trim($sqls);
            if(empty($sqls)) continue;
            if(preg_match('/^\s*create table (`?)(\w+)\1/i',$sqls,$m)){
                ENGINE::db('debug once')->select('drop table if exists ?k',$m[2]);
                print_r($m);
                $sqls.=' ENGINE=MEMORY';
            }

            echo $sqls.";\n";
            $result=ENGINE::db('debug once')->select($sqls);
            if(is_array($result) && !empty($result)){
                $keys=array_keys($result[0]);
                echo '<table>';
                echo '<tr><td></td>';
                foreach($keys as $k) echo '<th>'.$k.'</th>';
                echo '</tr><tr>';
                $rowcnt=1;
                foreach($result as $row){
                    echo '<tr><th>'.$rowcnt++.'</th>';
                    foreach($row as $r)echo '<td>'.$r.'</td>';
                    echo '</tr>';
                }
                echo '</table><br>';
            } else {
                var_dump($result);
            }
        }

    }

}
