<?php
/**
 * Модуль данных для госмонитора
 */

class gdata extends \drupal_base{

  private static $cache=[];

  static function get($name){
    if(!isset(self::$cache[$name])) {
      if(method_exists(__CLASS__,($method='_'.$name))){
        self::$cache[$name]=call_user_func(array(__CLASS__,$method));
      }
    }
    return self::$cache[$name];
  }

  /**
   * @return xDatabaseLapsi
   */
  private static function _db(){
    self::_init();
    return ENGINE::db();
  }


  private static function _mongo(){
    return new \emongo();
  }
  /**
   * @return array
   */
  private static function _anketevalues(){
    /** @var xDatabaseLapsi $db */
    $db=self::get('db');
    $res=$db->selectByInd('entity_id','select entity_id,field_rating_criteria_values_value from field_data_field_rating_criteria_values');
    foreach($res as &$r){
      preg_match('/^[^\|]+/',$r['field_rating_criteria_values_value'],$mm);
      $r['min']=$mm[0];
      preg_match_all('/\|([^\|\s]+)\|/s',$r['field_rating_criteria_values_value'],$m);
      $rr=[];
      foreach($m[1] as $v){
        $rr[]=preg_quote($v);
      }
      $r['reg']='/^(?:'.implode('|',$rr).')$/iu';
      preg_match_all('/([^\|\s]+)\|([^\|\s]+)\|([^\|\s]+)/s',$r['field_rating_criteria_values_value'],$m);
      $rr=[];
      foreach($m[0] as $ind=>$x){
        $rr[$m[1][$ind]]=0+$m[3][$ind];
      }
      $r['kid']=$rr;
    }
    return $res;
  }

  /**
   * Последняя записанная в базу дата
   * @return mixed
   */
  private  static function _maxdate(){
    /** @var xDatabaseLapsi $db */
      $db=self::get('db');
      return $db->selectCell('select `date` from `indicator` order by date desc limit 1');
  }

  static function plural($n, $suf = '')
  {
    list($one, $two, $five, $trash) = explode('|', $suf . '|||', 4);
    if ($n < 20 && $n > 9) return $five;
    $n = $n % 10;
    if ($n == 1) return $one;
    if ($n < 5 && $n > 1) return $two;
    return $five;
  }

  static function write_values($names,$ankete,$val,$comment,$change=false,$debug=false){
        static $delta=[];
      /** @var xDatabaseLapsi $db */
        $db=self::get('db');
        // прочитать параметр
      //ищем value-node
        $v=$db->selectRow('SELECT value_node.nid,value_node.vid, indicator.field_rating_value_ind_target_id as ind_id, cur_values.entity_id as values_nid
FROM `field_data_field_rating_value_form_data` rvfd
left join node value_node on value_node.nid=rvfd.entity_id and value_node.vid=rvfd.revision_id
left join field_data_field_rating_value_ind indicator on indicator.entity_id=value_node.nid and value_node.vid=indicator.revision_id
left join field_data_field_rating_value_cur_values cur_values on cur_values .entity_id=value_node.nid and value_node.vid=cur_values .revision_id
WHERE rvfd.field_rating_value_form_data_target_id = ?d and indicator.field_rating_value_ind_target_id=?',$ankete['nid'],$names['ind_id']);
        //print_r($v);
        if(!empty($v)) {
            if(!empty($val) && preg_match('/(да)|нет/iu',$val,$m)){
                $val=empty($m[1])?0:1;
            }
//                print_r($k);
            if(!isset($delta[$v['nid']]))$delta[$v['nid']] = 0;
            else $delta[$v['nid']]++;

          foreach (['edit', 'init', 'cur'] as $suf) {
            $k = [
              'entity_type' => 'node',
              'bundle' => 'mob_expert_rating_values',
              'deleted' => 0,
              'entity_id' => $v['nid'],
              'revision_id' => $v['vid'],
              'language' => 'und',
              'delta' => $delta[$v['nid']],];
            $k['field_rating_value_'.$suf.'_values_first'] = $names['cr_id'];
            $k['field_rating_value_'.$suf.'_values_second'] = $val;

            if ($change) {
              $db->insert("INSERT INTO `field_data_field_rating_value_".$suf."_values` (?1[?1k]) VALUE (?1[?2]) ON DUPLICATE KEY UPDATE  ?1[?1k=VALUES(?1k)]", $k);
            } else {
              if ($debug)
                print_r($k);
            }
          }
          if ($change) {
            foreach (['created', 'corrected'] as $suf) {
              $k = [
                'entity_type' => 'node',
                'bundle' => 'mob_expert_rating_values',
                'deleted' => 0,
                'entity_id' => $v['nid'],
                'revision_id' => $v['vid'],
                'language' => 'und',
                'delta' => 0,];
                $k['field_rating_value_'.$suf.'_value']=date("Y-m-d 00:00:00");
              $db->insert("INSERT INTO `field_data_field_rating_value_".$suf."` (?1[?1k]) VALUE (?1[?2]) ON DUPLICATE KEY UPDATE  ?1[?1k=VALUES(?1k)]", $k);
            }

            $db->update('UPDATE workflow_node SET sid=28 WHERE nid=?', $v['nid']);// 28
//*
            if (!empty($comment)) {
              foreach (['edit', 'init', 'cur'] as $suf) {
                $k = ['entity_type' => 'node',
                  'bundle' => 'mob_expert_rating_values',
                  'deleted' => 0,
                  'entity_id' => $v['nid'],
                  'revision_id' => $v['vid'],
                  'language' => 'und',
                  'delta' => $delta[$v['nid']],
                ];
                $k['field_rating_value_' . $suf . '_comments_first'] = $names['cr_id'];
                $k['field_rating_value_' . $suf . '_comments_second'] = $comment;
                $db->insert("INSERT INTO `field_data_field_rating_value_" . $suf . "_comments` (?1[?1k]) VALUE (?1[?2]) ON DUPLICATE KEY UPDATE  ?1[?1k=VALUES(?1k)]", $k);
              }
            } else {
              foreach (['edit', 'init', 'cur'] as $suf) {
                $k = [
                  'revision_id' => $v['vid'],
                  'entity_id' => $v['nid'],
                  'delta' => $delta[$v['nid']]
                ];
                $k['field_rating_value_' . $suf . '_comments_first'] = $names['cr_id'];
                $db->delete("DELETE FROM `field_data_field_rating_value_" . $suf . "_comments` WHERE ?1[?1k=?2| AND ]", $k);
              }
            }
          }
          if($debug)
            printf((!$change?'Будет и':'И')."зменен параметр p:%s, a:%s, ind:%s, cr:%s, v:%s\n"
              ,$v['nid'],$ankete['nid'],$ankete['ind_id'],$names['cr_id'],$val);
           // , print_r($ankete, true)
            //  , print_r($names, true),$val);
        } else {
          printf("Не найден в базе параметр a:%s, cr:%s\n"
            ,$ankete['nid'],$names['ind_id']);
        }
    }

    static function getCodeNames($tid=3523){
      $db=self::get('db');
        $row=$db->select("SELECT pi.delta,rt.field_expert_rating_type_target_id AS rt_id, i.tid AS ind_id, i.name as ind_name, indrel.field_policy_indicators_ind_rel_value AS ind_rel, cr.field_policy_ind_criterion_target_id AS cr_id,
 crn.name,   
 crrel.field_policy_ind_crit_rel_value AS cr_rel
    FROM taxonomy_term_data p
    LEFT JOIN field_data_field_policy_indicators pi ON p.tid = pi.entity_id AND (pi.entity_type = 'taxonomy_term' AND pi.deleted = 0)
    INNER JOIN field_collection_item fcpi ON pi.field_policy_indicators_value = fcpi.item_id
    LEFT JOIN field_data_field_policy_indicators_ind pii ON fcpi.item_id = pii.entity_id AND (pii.entity_type = 'field_collection_item' AND pii.deleted = 0)
    LEFT JOIN field_data_field_policy_indicators_ind_rel indrel ON fcpi.item_id = indrel.entity_id AND (indrel.entity_type = 'field_collection_item' AND indrel.deleted = 0)
    INNER JOIN taxonomy_term_data i ON pii.field_policy_indicators_ind_target_id = i.tid
    LEFT JOIN field_data_field_policy_indicators_crit pic ON fcpi.item_id = pic.entity_id AND (pic.entity_type = 'field_collection_item' AND pic.deleted = 0)
    INNER JOIN field_collection_item fcic ON pic.field_policy_indicators_crit_value = fcic.item_id
    LEFT JOIN field_data_field_policy_ind_criterion cr ON fcic.item_id = cr.entity_id AND (cr.entity_type = 'field_collection_item' AND cr.deleted = 0)
    INNER JOIN taxonomy_term_data crn ON cr.field_policy_ind_criterion_target_id = crn.tid
    LEFT JOIN field_data_field_policy_ind_crit_rel crrel ON fcic.item_id = crrel.entity_id AND (crrel.entity_type = 'field_collection_item' AND crrel.deleted = 0)
    LEFT JOIN taxonomy_vocabulary voc ON p.vid = voc.vid
    LEFT JOIN field_data_field_expert_rating_type rt ON p.tid = rt.entity_id AND (rt.entity_type = 'taxonomy_term' AND rt.deleted = 0)
    WHERE voc.machine_name IN ('expert_rating_policy') 
-- and not p.tid in(3445, 3427,1096, 1095,32,2843)
and p.tid=?
-- and crrel.field_policy_ind_crit_rel_value=1
-- limit 10 ;",$tid);
        return $row;
    }

    static function deletenode($nid){
      $db=self::get('db');
      $row=$db->delete("delete from node where nid=?d",$nid);
    }

    static function getankete($id){
      $db=self::get('db');
        $row=$db->select("SELECT policy_indicators.delta,taxonomy_ind_name.name as parameter,indicator.field_rating_value_ind_target_id as parameter_id,
 taxonomy_ind_cr.name as cr,
 cur_values.field_rating_value_edit_values_first as cr_id,
 cur_values.field_rating_value_edit_values_second AS cr_value,
 workflow_states.state as wstate,
 workflow_node.sid as wstate_id, values_node.nid as value_nid
    FROM node form_data
    left JOIN field_data_field_rating_value_form_data form_data_rel ON form_data.nid = form_data_rel.field_rating_value_form_data_target_id
    left JOIN node values_node ON form_data_rel.entity_id = values_node.nid
    LEFT JOIN field_data_field_rating_value_ind indicator ON values_node.nid = indicator.entity_id AND (indicator.entity_type = 'node' AND indicator.deleted = 0)
    left JOIN taxonomy_term_data taxonomy_ind_name ON indicator.field_rating_value_ind_target_id = taxonomy_ind_name.tid
    LEFT JOIN field_data_field_rating_value_edit_values cur_values ON values_node.nid = cur_values.entity_id AND (cur_values.entity_type = 'node' AND cur_values.deleted = 0)
    left JOIN taxonomy_term_data taxonomy_ind_cr ON cur_values.field_rating_value_edit_values_first = taxonomy_ind_cr.tid

 LEFT JOIN field_data_field_expert_rating_form_policy form_policy ON form_data.nid = form_policy.entity_id AND (form_policy.entity_type = 'node' AND form_policy.deleted = '0')
 --     INNER JOIN taxonomy_term_data taxonomy_term_data_field_data_field_expert_rating_form_policy ON form_policy.field_expert_rating_form_policy_target_id = taxonomy_term_data_field_data_field_expert_rating_form_policy.tid
 LEFT JOIN field_data_field_policy_indicators policy_indicators ON form_policy.field_expert_rating_form_policy_target_id = policy_indicators.entity_id AND (policy_indicators.entity_type = 'taxonomy_term' AND policy_indicators.deleted = '0')
 --     INNER JOIN field_collection_item fcollection ON policy_indicators.field_policy_indicators_value = fcollection.item_id
 LEFT JOIN field_data_field_policy_indicators_ind policy_indicator ON policy_indicators.field_policy_indicators_value = policy_indicator.entity_id AND (policy_indicator.entity_type = 'field_collection_item' AND policy_indicator.deleted = '0')

LEFT JOIN workflow_node workflow_node ON values_node.nid = workflow_node.nid
LEFT JOIN workflow_states workflow_states ON workflow_node.sid = workflow_states.sid
    
    WHERE  (form_data.nid =?d ) AND  (form_data.type IN ('mob_expert_rating_form_data'))
 and indicator.field_rating_value_ind_target_id = policy_indicator.field_policy_indicators_ind_target_id
order by policy_indicators.delta",$id);
        return $row;
    }

    static function findAnkete($nid){
      $db=self::get('db');
        $row=$db->selectRow('SELECT ank.nid,ank.vid,ank.title,p.field_expert_rating_form_policy_target_id as method FROM `field_data_field_expert_rating_form_mob` fmob
inner join node ank on ank.nid=fmob.entity_id and ank.vid=fmob.revision_id
inner join field_data_field_expert_rating_form_policy p on ank.nid=p.entity_id and ank.vid=p.revision_id
WHERE fmob.`field_expert_rating_form_mob_target_id` = ?d
order by ank.created desc',$nid);
        return $row;
    }

    static function findOrgByUrl($url, $nopuny=false){
      $db=self::get('db');
        $url=preg_replace(['~^https?://(www\.)?~','~/$~'],'',$url);
        $url_utf=idn_to_utf8($url);
        if(!empty($url_utf)){
          $url=$url_utf;
        }

        // find org
        $row=$db->selectRow('SELECT org.title, org.nid 
FROM `field_data_field_mob_url`url
inner join node org on org.nid=url.entity_id and org.vid=url.revision_id 
left join field_data_field_additional_urls au on au.entity_id=org.nid and  au.revision_id=org.vid 
left join  field_data_field_mob_register mr on mr.entity_id=org.nid and  mr.revision_id=org.vid 
where (
url.`field_mob_url_url` like ?1l
or au.field_additional_urls_url like ?1l)
 and url.bundle="mob"
order by field(mr.field_mob_register_value, "over", "minec") desc
limit 1',$url);
        if($nopuny && empty($row)){
          $url_utf=idn_to_utf8($url);
          $url_loc=idn_to_utf8($url);
          if(!empty($url_utf) && $url_utf!=$url){
            return self::findOrgByUrl($url_utf, true);
          } elseif(!empty($url_loc) && $url_loc!=$url){
            return self::findOrgByUrl($url_loc, true);
          }
        }
        return $row;
    }
}