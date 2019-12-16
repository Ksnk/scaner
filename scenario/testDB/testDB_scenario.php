<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 02.12.2019
 * Time: 16:43
 */

namespace Ksnk\scaner;

/**
 * @property spider spider
 * @tags data.gov
 */
class testDB_scenario extends scenario {

  var $predefinedTables=[
    'field_group',
    'field_collection_item',
    'field_collection_item_revision',
    'field_config',
    'field_config_instance',
    'field_validation_rule'
  ];

  /**
   * Генерация SQL для вывода всех заявок на OD
   */
  function do_od_requests(){
    /**
    Номер запроса, инициатор запроса
     * /Наименование данных	/Дата поступления запроса	/Описание данных
     * /Область запроса	/Востребованность	/Пользовательская оценка	/Статус заявки
     */
    $fields=[
      'Наименование данных'=>'field_od_query_data_benefits|bf',
      "Номер запроса, инициатор запроса"=>"concat ('№',ft.field_od_query_id_value,', ',
fn.field_user_name_value, ' ',
sn.field_user_middle_name_value, ' ',
ln.field_user_last_name_value 
)|*",
      'Описание данных'=>'field_od_query_message|od',
      'Дата поступления запроса'=>'FROM_UNIXTIME(n.created)|*',
      'Область запроса'=>'GROUP_CONCAT(td.name)|*',//'field_od_query_data_rubrics|rb|tid',
      'Пользовательская оценка'=>'field_od_query_data_profit|dp',
      'Статус заявки'=>'field_query_state|fs',
      'field_user_last_name|ln',
      'field_user_middle_name|sn',
      'field_user_name|fn',
      'field_od_query_id|ft',
      'field_od_query_data_rubrics|rb',
      '|||left join taxonomy_term_data td on td.tid=rb.field_od_query_data_rubrics_tid',
    ];
    $ff=[];
    $j=[];
    foreach($fields as $k=>$f){
      list($name,$table,$plus,$mode,$empty)=explode('|',$f.'|||||',5);
      $alias=(empty($table)?'n':$table);
      if($table=='*')$plus='';
      else if(!empty($plus))$plus='_'.$plus;
      else $plus='_value';
      if(!is_numeric($k))
        $ff[]=($alias!='*'?$alias.'.':'').$name.(empty($table)?'':$plus).' as "'.$k.'"';
      if(!empty($table) && $table!='*')
        $j[]='left join field_data_'.$name.' '.$alias.' on n.nid='.$alias.'.entity_id and n.vid='.$alias.'.revision_id';
      if(!empty($mode))
        $j[]=$mode;
    }
    $sql='select 
    '.implode(',
    ',$ff).'
    from node n
    '.implode("\n",$j);
    echo $sql.'
    where n.type=\'opendata_query\'
group by n.nid order by created desc  limit 10;';

  }

  /**
   * список таблиц
   * @param $action :select[checktable|cleartables] действие
   */
  function do_tablelist($action ){
    $cnt=0;
      foreach (\ENGINE::db()->select('show tables like "field_%";') as $row) {
        //printf("%s<br>\n", implode(',', $row));
        $r=implode(',', $row);
        if(!in_array($r,$this->predefinedTables)) {
          $cnt++;
          //if($cnt>2) break;
          $this->joblist->append_scenario($action, array(implode(',', $row)));
        }
      };
    printf("Всего %s таблиц полей<br>\n", $cnt);
  }

  function checktable($table){
   // printf("%s<br>\n", $table); return;
    $c=\ENGINE::db()->selectCell('select count(*) from (select  t.entity_id from ?1k t
left join node n on t.entity_id=n.nid -- and n.vid = t.revision_id
where n.nid is null) x
limit 1',$table);
    if($c>0) echo $table.' лишние строки, не привязаны к таблице node - '.$c;
    else echo $table.' ok';
    echo "<br>\n";
  }

  function cleartables($table){
    $c=\ENGINE::db()->selectCell('select count(*) from (select  t.entity_id from ?1k t
left join node n on t.entity_id=n.nid -- and n.vid = t.revision_id
where n.nid is null) x
limit 1',$table);
    if($c>0) {
      echo $table . ' лишние строки, не привязаны к таблице node - ' . $c ;
      // printf("%s<br>\n", $table); return;
      $c = \ENGINE::db('debug once')->delete('DELETE FROM ?1k WHERE NOT exists (SELECT nid FROM node WHERE entity_id=nid);', $table);
    }
    echo $table.' ok '.$c.' records deleted';
    echo "<br>\n";
  }
}

/**
 * select
--    n.nid as "id",
-- n.title
bf.field_od_query_data_benefits_value as "Наименование данных",
concat ('№',ft.field_od_query_id_value,', ',
fn.field_user_name_value, ' ',
sn.field_user_middle_name_value, ' ',
ln.field_user_last_name_value
) as "Номер запроса, инициатор запроса",
od.field_od_query_message_value as "Описание данных",
FROM_UNIXTIME(n.created) as "Дата поступления запроса",
GROUP_CONCAT(td.name) as "Область запроса",
dp.field_od_query_data_profit_value as "Пользовательская оценка",
fs.field_query_state_value as "Статус заявки"
-- field_od_user_email[und][0][email
-- field_user_last_name[und][0][value]
from node n
left join field_data_field_user_last_name ln on n.nid=ln.entity_id and n.vid=ln.revision_id
left join field_data_field_user_middle_name sn on n.nid=sn.entity_id and n.vid=sn.revision_id
left join field_data_field_user_name fn on n.nid=fn.entity_id and n.vid=fn.revision_id
left join field_data_field_od_query_id ft on n.nid=ft.entity_id and n.vid=ft.revision_id
-- left join field_data_field_title ft  on n.nid=ft.entity_id and n.vid=ft.revision_id
left join field_data_field_od_query_data_rubrics rb on n.nid=rb.entity_id and n.vid=rb.revision_id

left join taxonomy_term_data td on td.tid=rb.field_od_query_data_rubrics_tid
left join field_data_field_od_query_message od on n.nid=od.entity_id and n.vid=od.revision_id
left join field_data_field_od_query_data_benefits bf on n.nid=bf.entity_id and n.vid=bf.revision_id
left join field_data_field_od_query_data_profit dp on n.nid=dp.entity_id and n.vid=dp.revision_id
left join field_data_field_query_state fs on n.nid=fs.entity_id and n.vid=fs.revision_id
where n.type='opendata_query'
group by n.nid order by created desc;
 */