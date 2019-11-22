<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 22.11.2019
 * Time: 17:10
 */

namespace Ksnk\scaner;

include_once '../autoload.php';
\Autoload::map(['Ksnk\scaner'=>'']);


/**
 * Class csv
 * @package Ksnk\scaner
 * читатель csv, возможно в не той кодировке, возможно с нетеми делимитерами и нетеми обрамлениями
 */
class csv extends scaner {

    /**
     * функция выдает класс csv с правильно определенной кодировкой и делимитерами
     * @param $nameresource
     * @param $titles - количество строк на заголовок.
     * @return csv
     */
    static function getcsv($nameresource, $headers=1){
        $class= new self();
        $class->newhandle($nameresource);
        // читаем 10 срок, определяем кодировку, делиметеры
        // читаем 3 символа - не БОМ ли это
        for ($i=0;$i<10;$i++) {
            $class->line();
        }
        $buf = $class->getresult();
        return $class;
    }


    function nextRow(){
        return [1];
    }
}
/**
//decoding
if(!mb_detect_encoding($content,'UTF-8', true)){
    $content = @iconv('cp1251', "utf-8//IGNORE", $content);
}
// check delimiter
// possible values , ; \t
//$delimiter=',';

$fh = fopen("php://temp", 'r+');
fputs($fh, $content);
rewind($fh);

$db=ENGINE::db();
$cnts=array(0,0,0,0,0,0);
$cols=fgetcsv($fh,10000,$delimiter);fgetcsv($fh,10000,$delimiter);
$minch=0;
while($rows=fgetcsv($fh,10000,$delimiter)){
    //$rows[1]=preg_replace('/\s+/',' ',$rows[1]);
    if(preg_match('~^([0-9]{10,11})-[a-zA-Z0-9_-]+$~u',$rows[0],$m)){
        $uriname=$rows[0];
        $orginn=$m[1];
        $cnts['x'.$m[1]]++;
        $cnts[5]++;
        $cnts['z_'.$uriname]++;
    } else {
        echo "<span style='color:red'>не паспорт</span>:".json_encode($rows,JSON_UNESCAPED_UNICODE)."<br>\n";
        $cnts['not a passport']++; continue;
    }
    $r=$db->selectRow('select n.nid,n.status,n.changed, fd.field_dataset_title_value as name, organization.title as orgname, uid.field_dataset_id_value as datauid
from node n
left join field_data_field_organization org on n.nid = org.entity_id AND n.vid = org.revision_id
left join field_data_field_dataset_id uid on n.nid = uid.entity_id AND n.vid = uid.revision_id
left join  field_data_field_dataset_title as fd on fd.entity_id= n.nid AND n.vid = fd.revision_id
left join node organization on organization.nid = org.field_organization_target_id
where  n.type="dataset"
    -- and fd.field_dataset_title_value like "%специально уполномоченных на решение задач в области защиты населения и территорий РФ%"
    and uid.field_dataset_id_value=?
    -- and fd.field_dataset_title_value="Сведения о местах нахождения органов, специально уполномоченных на решение задач в области защиты населения и территорий РФ от ЧС"'
        ,$uriname );
    if(!empty($r)){
        $cnts['status_'.$r['status']]++;
        $cnts[1]++;
        $minch=$minch<=0?$r['changed']:min($minch,$r['changed']);
        printf("[%s]%s - '%s'(%s)[%s]<br>\n", $r['status'],$r['orgname'],$r['name'],date("Y-m-d H:i:s",$r['changed']), $uriname);
        $cnts[$r['orgname']]++;
    } else {
        $cnts[2]++;
        printf("<span style='color:red'>не найдено!</span>  '%s'-%s<br>\n", $rows[1], $rows[2]);
    }


    // $files=$db->select('select file_managed where filename like %l', 'public://opendata/'.)
    // public://opendata/7017069388-razcopsop/structure-2018-12-14T00-00-00

    //print_r($rows);
}
echo ' обновление '.date("Y-m-d H:i:s",$minch)."<br>\n";
echo '<pre>';
$r=$cnts;
foreach($cnts as $k=>&$c){
    if(preg_match('/^z_/',$k) && $c==1){
        unset($r[$k]);
    }
}
print_r($r);
echo '</pre>';
fclose($fh);


*/

$csv=csv::getcsv('d:/projects/monitoring/csv/reestroi/data-20191112-structure-20171024.csv');
$cnt=10;
while($row=$csv->nextRow()){
    print_r($row);
    if($cnt-- <0) break;
}