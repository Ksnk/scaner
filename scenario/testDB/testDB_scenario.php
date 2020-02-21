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
class testDB_scenario extends scenario
{

  function __construct($joblist = null){
    require_once "gdata.php";
    require_once "config.php";
    parent::__construct($joblist = null);
  }

  var $predefinedTables = [
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
  function do_od_requests()
  {
    /**
     * Номер запроса, инициатор запроса
     * /Наименование данных  /Дата поступления запроса  /Описание данных
     * /Область запроса  /Востребованность  /Пользовательская оценка  /Статус заявки
     */
    $fields = [
      'Наименование данных' => 'field_od_query_data_benefits|bf',
      "Номер запроса, инициатор запроса" => "concat ('№',ft.field_od_query_id_value,', ',
fn.field_user_name_value, ' ',
sn.field_user_middle_name_value, ' ',
ln.field_user_last_name_value 
)|*",
      'Описание данных' => 'field_od_query_message|od',
      'Дата поступления запроса' => 'FROM_UNIXTIME(n.created)|*',
      'Область запроса' => 'GROUP_CONCAT(td.name)|*',//'field_od_query_data_rubrics|rb|tid',
      'Пользовательская оценка' => 'field_od_query_data_profit|dp',
      'Статус заявки' => 'field_query_state|fs',
      'field_user_last_name|ln',
      'field_user_middle_name|sn',
      'field_user_name|fn',
      'field_od_query_id|ft',
      'field_od_query_data_rubrics|rb',
      '|||left join taxonomy_term_data td on td.tid=rb.field_od_query_data_rubrics_tid',
    ];
    $ff = [];
    $j = [];
    foreach ($fields as $k => $f) {
      list($name, $table, $plus, $mode, $empty) = explode('|', $f . '|||||', 5);
      $alias = (empty($table) ? 'n' : $table);
      if ($table == '*') $plus = '';
      else if (!empty($plus)) $plus = '_' . $plus;
      else $plus = '_value';
      if (!is_numeric($k))
        $ff[] = ($alias != '*' ? $alias . '.' : '') . $name . (empty($table) ? '' : $plus) . ' as "' . $k . '"';
      if (!empty($table) && $table != '*')
        $j[] = 'left join field_data_' . $name . ' ' . $alias . ' on n.nid=' . $alias . '.entity_id and n.vid=' . $alias . '.revision_id';
      if (!empty($mode))
        $j[] = $mode;
    }
    $sql = 'select 
    ' . implode(',
    ', $ff) . '
    from node n
    ' . implode("\n", $j);
    echo $sql . '
    where n.type=\'opendata_query\'
group by n.nid order by created desc  limit 10;';

  }

  /**
   * проверка тухлых полей данных
   * @param $action :select[checktable:проверить|cleartables:очистить+✞] действие
   */
  function do_tablelist($action)
  {
    \gdata::_init();
    $cnt = 0;
    foreach (\ENGINE::db()->select('show tables like "field_%";') as $row) {
      //printf("%s<br>\n", implode(',', $row));
      $r = implode(',', $row);
      if (!in_array($r, $this->predefinedTables)) {
        $cnt++;
        //if($cnt>2) break;
        $this->joblist->append_scenario($action, array(implode(',', $row)));
      }
    };
    printf("Всего %s таблиц полей<br>\n", $cnt);
  }

  function checktable($table)
  {
    \gdata::_init();
    // printf("%s<br>\n", $table); return;
    $c = \ENGINE::db()->selectCell('SELECT count(*) FROM (SELECT  t.entity_id FROM ?1k t
LEFT JOIN node n ON t.entity_id=n.nid -- and n.vid = t.revision_id
WHERE n.nid IS NULL) x
LIMIT 1', $table);
    if ($c > 0) echo $table . ' лишние строки, не привязаны к таблице node - ' . $c;
    else echo $table . ' ok';
    echo "<br>\n";
  }

  function cleartables($table)
  {
    $c = \ENGINE::db()->selectCell('SELECT count(*) FROM (SELECT  t.entity_id FROM ?1k t
LEFT JOIN node n ON t.entity_id=n.nid -- and n.vid = t.revision_id
WHERE n.nid IS NULL) x
LIMIT 1', $table);
    if ($c > 0) {
      echo $table . ' лишние строки, не привязаны к таблице node - ' . $c;
      // printf("%s<br>\n", $table); return;
      $c = \ENGINE::db('debug once')->delete('DELETE FROM ?1k WHERE NOT exists (SELECT nid FROM node WHERE entity_id=nid);', $table);
    }
    echo $table . ' ok ' . $c . ' records deleted';
    echo "<br>\n";
  }


  /**
   * select
   * --    n.nid as "id",
   * -- n.title
   * bf.field_od_query_data_benefits_value as "Наименование данных",
   * concat ('№',ft.field_od_query_id_value,', ',
   * fn.field_user_name_value, ' ',
   * sn.field_user_middle_name_value, ' ',
   * ln.field_user_last_name_value
   * ) as "Номер запроса, инициатор запроса",
   * od.field_od_query_message_value as "Описание данных",
   * FROM_UNIXTIME(n.created) as "Дата поступления запроса",
   * GROUP_CONCAT(td.name) as "Область запроса",
   * dp.field_od_query_data_profit_value as "Пользовательская оценка",
   * fs.field_query_state_value as "Статус заявки"
   * -- field_od_user_email[und][0][email
   * -- field_user_last_name[und][0][value]
   * from node n
   * left join field_data_field_user_last_name ln on n.nid=ln.entity_id and n.vid=ln.revision_id
   * left join field_data_field_user_middle_name sn on n.nid=sn.entity_id and n.vid=sn.revision_id
   * left join field_data_field_user_name fn on n.nid=fn.entity_id and n.vid=fn.revision_id
   * left join field_data_field_od_query_id ft on n.nid=ft.entity_id and n.vid=ft.revision_id
   * -- left join field_data_field_title ft  on n.nid=ft.entity_id and n.vid=ft.revision_id
   * left join field_data_field_od_query_data_rubrics rb on n.nid=rb.entity_id and n.vid=rb.revision_id
   *
   * left join taxonomy_term_data td on td.tid=rb.field_od_query_data_rubrics_tid
   * left join field_data_field_od_query_message od on n.nid=od.entity_id and n.vid=od.revision_id
   * left join field_data_field_od_query_data_benefits bf on n.nid=bf.entity_id and n.vid=bf.revision_id
   * left join field_data_field_od_query_data_profit dp on n.nid=dp.entity_id and n.vid=dp.revision_id
   * left join field_data_field_query_state fs on n.nid=fs.entity_id and n.vid=fs.revision_id
   * where n.type='opendata_query'
   * group by n.nid order by created desc;
   */

  /**
   * поиск организаций
   * @param string $list :textarea список
   */
  function do_findOrgByList($list)
  {
    $l=explode("\n",$list);
    foreach($l as $org){
      if(''==trim($org)) continue;
      $o=\gdata::findnode([
        'Название' =>  trim($org)
        //,'Тип' => 'Федеральные'
      ]);
      if(empty($o)) printf("%s не найдена \n",$org);
      else      {
        printf("%s(%s) найдена \n",$o->title,$o->nid);
      }
    }
  }


  /**
   * Создание новой ноды(не раб.и не проб!!)
   * @param $data :textarea
   * @param $title название набора
   * @throws \Exception
   */
  function do_create_nodebydrupal(
    $data,
    $title
  ){
    session_write_close();
    $_SESSION=[];
    \gdata::_init();
    $db=\ENGINE::db();

    \gdata::startdrupal();

    $docs=[];
    $scaner=new scaner();
    $scaner->newbuf($data);
    $reg='~<a[^>]+class="description"[^>]+href="([^"]+)"[^<]+<[^<]+class="file_name">([^<]+)~sui';
    $scaner
      ->scan($reg, 1, 'url', 2, 'title');
    while($scaner->found) {
      $docs[]=$scaner->getResult();
      $scaner
        ->scan($reg, 1, 'url', 2, 'title');
    } ;

    try {
$cnt=1000;
      foreach ($docs as $idx=>$d) {
        $d['title']=mb_substr($d['title'],0,255,'utf-8');
        // записываем файл
        $uri = 'https://rosreestr.ru' . $d['url'];

        $file = (object)[
          'uid' => 1,
          'filename' => basename($uri),
          'uri' => $uri,
          'filemime' => mime_content_type($uri),
          //'filesize' => strlen($f)
        ];
        $existing_files = file_load_multiple(array(), array('uri' => $file->uri));
        if (count($existing_files)) {

          $existing = reset($existing_files);
          $file->fid = $existing->fid;
        } else {
          $f = file_get_contents($uri);
          if (empty($f)) {
            printf ("can't read - %s \n",$uri);
            continue;
          };
          $file->filesize = strlen($f);
          $file = file_save($file);
        }
        // записываем датасет
        $dnid=$db->selectCell('select nid from node where title=? and type=? limit 1',$d['title'],'dataset');
        if(empty($dnid)) {
          $dataset = \entity_create('node', [
            'title' => $d['title'],
            'type' => 'dataset', // resource
            'language' => LANGUAGE_NONE,
            'uid' => 1,
            'status' => 1,
            'promote' => 0,
            'comment' => 2
          ]);
          $wdataset = entity_metadata_wrapper('node', $dataset);
          $wdataset->field_contact_name->set('Савина Ольга Евгеньевна');
          $wdataset->field_license->set('notspecified');
          //$wdataset->field_resources->set($wresource);

          $wdataset->field_tags->set([1330]);
          $wdataset->field_dataset_id->set('7706560536-datauploaded' . (++$cnt));
          $wdataset->field_contact_phone->set('8 (800) 100-34-34');
          $wdataset->field_organization->set(63);
          $wdataset->field_rubric->set(9);
          $wdataset->field_date_first_time_publ->set(strtotime('-1 day'));
          $wdataset->field_date_dataset_actual->set(strtotime('+3 month'));
          $wdataset->field_owner->set('ФЕДЕРАЛЬНАЯ СЛУЖБА ГОСУДАРСТВЕННОЙ РЕГИСТРАЦИИ, КАДАСТРА И КАРТОГРАФИИ');
          $wdataset->field_dataset_title->set($d['title']);
          //$wdataset->field_dataset_body->set($d['title']);
          $wdataset->field_multivolume->set(0);
          $wdataset->field_revision_dirty->set(0);
          $wdataset->field_dataset_status->set('approved');
          $wdataset->save();
          $dnid=(int)$wdataset->nid;
        }
        // записываем датасет
        $rnid=$db->selectCell('select nid from node where title like ?l and type=? limit 1',$d['title'],'resource');
        if(empty($rnid)) {

          $resource = \entity_create('node', [
            'title' => $d['title'] . ' - ' . date("d.m.Y"),
            'type' => 'resource',
            'language' => LANGUAGE_NONE,
            'uid' => 1,
            'status' => 1,
            'promote' => 0,
            'comment' => 2
          ]);
          // Reference the first node to the second node.
          $wresource = entity_metadata_wrapper('node', $resource);

          $wresource->field_dataset_ref->set($dnid);
          //[field_format] => csv - [6]
         // $wresource->field_link_remote_file->set($file->fid);
          $wresource->field_created->set(strtotime('-1 day'));
          $wresource->field_utf8_encoding->set(0);
          $wresource->save();
          $rnid=(int)$wresource->nid;
        }
        // setlinks
        $d = \gdata::getnode($dnid);
        $r = \gdata::getnode($rnid);
        if($r->field_link_passport='-=EMPTY=-'){
          $fname = 'field_link_passport';
          $data = [
            'entity_id' => $r->nid,
            'revision_id' => $r->vid,
            'field_link_remote_file_display' => 1,
            'entity_type' => 'node',
            'bundle' => $r->type,
            'deleted' => 0,
            'language' => 'und',
            'delta' => 0,
            'field_link_remote_file_fid' => $file->fid
          ];
          $db->delete('delete from ?1k where entity_id=? and revision_id=?;','field_data_'.$fname, $data['entity_id'], $data['revision_id']);
        };

        if(!empty($r->field_link_remote_file)) {
          //$wresource->field_link_remote_file->set($file->fid);
          $fname = 'field_link_remote_file';
          $data = [
            'entity_id' => $r->nid,
            'revision_id' => $r->vid,
            'field_link_remote_file_display' => 1,
            'entity_type' => 'node',
            'bundle' => $r->type,
            'deleted' => 0,
            'language' => 'und',
            'delta' => 0,
            'field_link_remote_file_fid' => $file->fid
          ];
          $db->insert('INSERT INTO ?1k (?2[?1k]) VALUES (?2[?2]) ' .
             'ON DUPLICATE KEY UPDATE ?2[?1k=VALUES(?1k)];','field_data_'.$fname, $data);
        }
        //print_r($d);print_r($r);

        printf("%s)\tdata:%s, res:%s, FID:%s\n",1+$idx,$dnid,$rnid,$file->fid);
        //break;
      }
    } catch(\Exception $e) {
      echo $e->getMessage();
    }

  }

  /**
   * сменить дату у всех ОД организации
   * @param string $list :textarea список названий, построчно
   * @param $change :radio[0:не менять|1:менять]
   * @param string $date дата
   */
  function do_changeDateFromOrgs($list, $change=0, $date)
  {
    $l=explode("\n",$list);
    $time=strtotime($date);
    //echo $time ;
    foreach($l as $org){
      if(''==trim($org)) continue;
      $o=\gdata::findnode([
        'Название' =>  trim($org)
        //,'Тип' => 'Федеральные'
      ]);
      if(!empty($o)) {
        $c=\gdata::findnode([
          'field_organization' =>  $o->nid
          //,'Тип' => 'Федеральные'
        ],'dataset',0);
        if(empty($c)){
          printf("%s найдены OD \n",$org);
        } else if(empty($time)){
          printf("`%s` неверное изображение даты \n",$date);
        } else {
          foreach($c as $cc){

            //$actual=strtotime('+1 month',$time);
            $doc=\gdata::getnode($cc);
            $rid=$doc->field_resources;
            if(!empty($rid)){
              $xnid=\ENGINE::db()->selectRow('select nid,vid from node_revision where nid=? order by vid desc limit 1',$rid);
              // поставить дату только у самого последнего файла
              if($change==1)
                \ENGINE::db()->update('update node_revision set timestamp=? where nid=? and vid=?',$time,$xnid['nid'],$xnid['vid']);
              // и еще у оригинального файла
              $resource=\gdata::getnode($rid);
              if(!empty($resource->field_upload_original)) {
                if($change==1)
                  \ENGINE::db()->update('update field_data_field_upload_original set timestamp=? where fid=?', $time, $resource->field_upload_original);
              }
             // X \ENGINE::db()->update('update `field_data_field_created` set field_created_value=? WHERE `entity_id` = ? and revision_id=?',$time,$xnid['nid'],$xnid['vid']);
            }
            //\ENGINE::db()->update('update node set changed=?1 where  changed<?1 and nid=?2',$time,$cc);
            //\ENGINE::db()->update('update field_data_field_dataset_latest_update set field_Dataset_latest_update_value=? where entity_id=?',$time,$cc);
            //\ENGINE::db()->update('update field_data_field_structure_latest_update set field_structure_latest_update_value=? where entity_id=?',$time,$cc);

            //$xnid=\ENGINE::db()->selectRow('select nid,vid from node_revision where nid=? order by vid desc limit 1',$cc);
            //\ENGINE::db()->update('update node_revision set timestamp=? where nid=? and vid=?',$time,$xnid['nid'],$xnid['vid']);

            //\ENGINE::db()->update('update field_data_field_date_last_change set field_date_last_change_value=? where entity_id=?',$time,$cc);
            //\ENGINE::db()->update('update field_data_field_date_dataset_actual set field_date_dataset_actual_value=? where entity_id=?',$time,$cc);
            // \ENGINE::db()->update('update field_data_field_Dataset_latest_update set field_Dataset_latest_update_value=? where entity_id=?',$time,$cc);
          }
          if($change==1)
            printf("%s проставлена дата изменения\n", count($c),$org);
          else
            printf("%s не менялось\n", count($c),$org);
        }
      }
      else        printf("%s не найдена \n",$org);
    }
    print_r($c);
  }

  /**
   * Тестировать ноду с индексом ID
   * @param $id нода
   */
  function do_test($id){
    try {
      print_r(\gdata::getnode($id));
    } catch (\Exception $e){
      print_r($e->getMessage());
    }
    echo 'OK';
  }

}