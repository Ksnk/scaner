<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 20.01.2020
 * Time: 14:07
 * Базовые функции работы с друпалом
 */

class drupal_base {
  /**
   * инициализация класса досупа к данным
   */
  static function _init(){
    static $done=false;
    if($done) return;

    ENGINE::set_option(array(
      'database.options'=>'nocache',

      'database.host' => DB_HOST,
      'database.password' => DB_PASSWORD,
      'database.user' => DB_USER,
      'database.base' => DB_NAME,
      'database.prefix' => DB_PREFIX,

      'engine.aliaces' => array(
        'Database' => 'xDatabaseLapsii'
      ),
    ));
    $done=true;
  }

  /**
   * Поиск ноды по параметрам
   * - первый эшелон - родные параметры таблицы node
   * - второй эшелон - названия полей записи, локальные названия полей
   * Поле с именем 'Название' - отдельная обработка.
   * @param $filter - массив [имя поля=>значение] Агрегируются по И
   * @param string $type - тип записи для чтения
   * @param int $limit - limit==1 выдается одна нода, прочитанная. Иначе выдается список entity_id для последующего чтения
   * @return array|mixed
   */
  static function findnode($filter,$type='organization',$limit=1){
    self::_init();
    $instance=self::getNodeInstance($type);
    $db=ENGINE::db();
    $filter['type']=$type;
    // простой фильтр
    $node_self=['nid','vid','type','title','status','created','changed','comment'];
    $where=[];
    $join=[];
    foreach($filter as $k=>$v){
      if($k=='Название'){
        $tab='field_data_field_organization_short_name';
        $join[]=$db->_(['left join ?1k on ?1k.entity_id=node.nid and ?1k.revision_id=node.vid',$tab]);
        $where[]=$db->_(['(?k.?k like ?3l or node.title like ?3l)',$tab,'field_organization_short_name_value',$v]);
      } else
        if(in_array($k,$node_self)){
          $where[]=$db->_(['node.?k = ?',$k,$v]);
        } else {
          foreach($instance as $i){
            if(($i['data']==$k or $i['field_name']==$k) and in_array($i['type'],['text','entityreference'])){
              $tab='field_data_'.$i['field_name'];
              $join[]=$db->_(['left join ?1k on ?1k.entity_id=node.nid and ?1k.revision_id=node.vid',$tab]);
              if($i['type']=='entityreference'){
                $where[]=$db->_(['?k.?k = ?',$tab,$k.'_target_id',$v]);
              } else
                $where[]=$db->_(['?k.?k like ?l',$tab,$k.'_value',$v]);
            }
          }
        }
    }
    if($limit==1) {
      $row = $db->selectCell('SELECT node.nid FROM node node '
        . implode("\n", $join)
        . ' WHERE ' . implode(" and ", $where). ' LIMIT 1');
      if (!empty($row)) {
        return self::getnode($row);
      }
    } else {
      return  $db->selectCol('SELECT node.nid FROM node node '
        . implode("\n", $join)
        . ' WHERE ' . implode(" and ", $where));
    }
    //\ENGINE::db()->update(urn false;
  }

  /**
   * Внутренний кэш полей по имени структуры
   * @param $type
   * @return mixed
   */
  static function getNodeInstance($type){
    static $cache=[];
    if(!isset($cache[$type])){
      self::_init();
      $db=ENGINE::db();
      $instance=$db->selectByInd('field_name','SELECT f.field_name,f.type,ci.data FROM `field_config` f
left join `field_config_instance` ci on ci.field_id=f.id
where bundle=?',$type);
      foreach ($instance as &$i){
        $x=unserialize($i['data']);
        $i['data']=$x['label'];
      }
      unset($i);
      $cache[$type]=$instance;
    }
    return $cache[$type];
  }

  /**
   * информация о ноде, свободный формат
   * @param $id
   * @return array
   */
  static function getnode($id){
    self::_init();
    $db=ENGINE::db();
    $row=$db->selectRow('SELECT nid,vid,type,title,status,created,changed,comment FROM `node` where `nid`=?d',$id);
    $instance=self::getNodeInstance($row['type']);
    $row['changed|date']=date("Y-m-d H:j:s",$row['changed']);
    $row['created|date']=date("Y-m-d H:j:s",$row['created']);
    $rows=[];
    $data=[];
    $cnt=10;
    //ENGINE::db('debug once');
    $debug=[];
    foreach($instance as $i){
      $d=$db->selectRow('SELECT * FROM ?k where `entity_id`=? and bundle=? and revision_id=?d','field_data_'.$i['field_name'],$row['nid'],$row['type'],$row['vid']);
      //$debug[]=$d;
      $value='-=EMPTY=-';
      $key='';
      if(!empty($d)) {
        //$rows[]=$d;
        $key = $i['field_name'] . '_value';
        if($i['type']=='datestamp'){
          $value=date("Y-m-d H:j:s",$d[$i['field_name'].'_value']);
        } else if($i['type']=='entityreference'){
          $key = $i['field_name'] . '_target_id';
          $value=$d[$key];
          if(!empty($value)) {
            $value = '('.$value.')'.$db->selectCell('SELECT title FROM node WHERE nid=?d', $value);
          }
        } else if($i['type']=='file'){
          $key = $i['field_name'] . '_fid';
          $value=$d[$key];
          if(!empty($value)) {
            $value = '('.$value.')'.$db->selectCell('SELECT filename FROM file_managed WHERE `fid` = ?d', $value);
          }
        } else if($i['type']=='taxonomy_term_reference'){
          $value=$db->selectCell('select name from taxonomy_term_data where tid=?d',$d[$i['field_name'].'_tid']);
          $key = $i['field_name'] . '_tid';
        } else if($i['type']=='link_field'){
          $value = $d[$i['field_name'] . '_url'];
          $key = $i['field_name'] . '_url';
        } else if($i['type']=='geolocation_latlng'){
          $value = $d[$i['field_name'] . '_lat'].'x'.$d[$i['field_name'] . '_lng'];
        } else if($i['type']=='geofield'){
          $value = $d[$i['field_name'] . '_lat'].'x'.$d[$i['field_name'] . '_lon'];
        } else {
          if (isset($d[$i['field_name'] . '_value'])) {
            $value = $d[$i['field_name'] . '_value'];
            $key = $i['field_name'] . '_value';
          } else {
            $key='';
            foreach ($d as $k => $v) {
              if (preg_match('~^' . $i['field_name'] . '~', $k)) {
                $value = $v;
                $key = $k;
                break;
              }
            }
          }
        }
      }
      if(!empty($key))$key='> '.$key;
      $rows[$i['data'].' | '.$i['type']]=$value.$key;
      $data[$i['data']]=$value;
      $data[$i['field_name']]=$value;
    }

    return [$row,$rows,$data/*,$instance*/];
  }

}