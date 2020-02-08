<?php
/**
 * Модуль данных для госмонитора
 */

class gdata extends \drupal_base{

    static function maxdate(){
        static $maxdate;
        self::_init();
        $db=ENGINE::db();
        if(!isset($maxdate)){
            $maxdate=$db->selectCell('select `date` from `indicator` order by date desc limit 1');
        }
        return $maxdate;
    }

    static function write_values($ankete,$param,$val,$debug=true){
        static $delta;
        self::_init();
        $db=ENGINE::db();
        // прочитать параметр
        $value=$db->selectRow('select fd.entity_id as nid,fd.revision_id as vid from field_data_field_rating_value_form_data fd
inner join field_data_field_rating_value_ind fi on fi.entity_id=fd.entity_id and  fi.revision_id=fd.revision_id
where 
 fi.field_rating_value_ind_target_id=? and 
fd.field_rating_value_form_data_target_id=?',$param['ind_id'],$ankete['nid']);
        if(!empty($value)) {
            $key=$value['nid'];
            if(!isset($delta[$key]))$delta[$key]=0;
            else $delta[$key]++;
            if(!empty($val) && preg_match('/(да)|нет/iu',$val,$m)){
                $val=empty($m[1])?0:1;
            }
            if(!$debug) {
                // изменить данные
                $k = [
                    'entity_type' => 'node',
                    'bundle' => 'mob_expert_rating_values',
                    'deleted' => 0,
                    'entity_id' => $value['nid'],
                    'revision_id' => $value['vid'],
                    'language' => 'und',
                    'delta' => 0,
                    'field_rating_value_created_value' => date("Y-m-d 00:00:00")
                ];
                $db->insert("insert into `field_data_field_rating_value_created` (?1[?1k]) value (?1[?2]) on duplicate key update  ?1[?1k=VALUES(?1k)]", $k);
                $k = [
                    'entity_type' => 'node',
                    'bundle' => 'mob_expert_rating_values',
                    'deleted' => 0,
                    'entity_id' => $value['nid'],
                    'revision_id' => $value['vid'],
                    'language' => 'und',
                    'delta' => 0,
                    'field_rating_value_corrected_value' => date("Y-m-d 00:00:00")
                ];
                $db->insert("insert into `field_data_field_rating_value_corrected` (?1[?1k]) value (?1[?2]) on duplicate key update  ?1[?1k=VALUES(?1k)]", $k);

                $db->update('update workflow_node set sid=28 where nid=?', $value['nid']);// 28

                $k = ['entity_type' => 'node',
                    'bundle' => 'mob_expert_rating_values',
                    'deleted' => 0,
                    'entity_id' => $value['nid'],
                    'revision_id' => $value['vid'],
                    'language' => 'und',
                    'delta' => $delta[$key],
                    'field_rating_value_init_values_first' => $param['cr_id'],
                    'field_rating_value_init_values_second' => $val];
                $db->insert("insert into `field_data_field_rating_value_init_values` (?1[?1k]) value (?1[?2]) on duplicate key update  ?1[?1k=VALUES(?1k)]", $k);
                $k = ['entity_type' => 'node',
                    'bundle' => 'mob_expert_rating_values',
                    'deleted' => 0,
                    'entity_id' => $value['nid'],
                    'revision_id' => $value['vid'],
                    'language' => 'und',
                    'delta' => $delta[$key],
                    'field_rating_value_edit_values_first' => $param['cr_id'],
                    'field_rating_value_edit_values_second' => $val];
                $db->insert("insert into `field_data_field_rating_value_edit_values` (?1[?1k]) value (?1[?2]) on duplicate key update  ?1[?1k=VALUES(?1k)]", $k);

                // изменить
                $k = ['entity_type' => 'node',
                    'bundle' => 'mob_expert_rating_values',
                    'deleted' => 0,
                    'entity_id' => $value['nid'],
                    'revision_id' => $value['vid'],
                    'language' => 'und',
                    'delta' => $delta[$key],
                    'field_rating_value_cur_values_first' => $param['cr_id'],
                    'field_rating_value_cur_values_second' => $val];

                $db->insert("insert into `field_data_field_rating_value_cur_values` (?1[?1k]) value (?1[?2]) on duplicate key update  ?1[?1k=VALUES(?1k)]", $k);
            }
            printf(($debug?'Будет и':'И')."зменен параметр p:%s, a:%s, ind:%s, cr:%s, v:%s\n",$value['nid'],$ankete['nid'],$param['ind_id'],$param['cr_id'],$val);
        } else {
            printf("Не найден в базе параметр a:%s, cr:%s\n",$ankete['nid'],$param['ind_id']);
        }
    }

    static function getCodeNames($tid=3523){
        self::_init();
        $db=ENGINE::db();
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

    static function getankete($id){
        self::_init();
        $db=ENGINE::db();
        $row=$db->select("SELECT taxonomy_ind_name.name as parameter,indicator.field_rating_value_ind_target_id as parameter_id,
 taxonomy_ind_cr.name as cr,
 cur_values.field_rating_value_cur_values_first as cr_id,
 -- cur_values.field_rating_value_cur_values_first AS cr_id, 
 cur_values.field_rating_value_cur_values_second AS cr_value,
 workflow_states.state as wstate,
 workflow_node.sid as wstate_id
    FROM node form_data
    INNER JOIN field_data_field_rating_value_form_data form_data_rel ON form_data.nid = form_data_rel.field_rating_value_form_data_target_id
    INNER JOIN node values_node ON form_data_rel.entity_id = values_node.nid
    LEFT JOIN field_data_field_rating_value_ind indicator ON values_node.nid = indicator.entity_id AND (indicator.entity_type = 'node' AND indicator.deleted = 0)
    INNER JOIN taxonomy_term_data taxonomy_ind_name ON indicator.field_rating_value_ind_target_id = taxonomy_ind_name.tid
    LEFT JOIN field_data_field_rating_value_cur_values cur_values ON values_node.nid = cur_values.entity_id AND (cur_values.entity_type = 'node' AND cur_values.deleted = 0)
    INNER JOIN taxonomy_term_data taxonomy_ind_cr ON cur_values.field_rating_value_cur_values_first = taxonomy_ind_cr.tid
LEFT JOIN workflow_node workflow_node ON values_node.nid = workflow_node.nid
LEFT JOIN workflow_states workflow_states ON workflow_node.sid = workflow_states.sid

    
    WHERE (( (form_data.nid =?d ) ) AND (( (form_data.type IN ('mob_expert_rating_form_data')) )))",$id);
        return $row;
    }

    static function findAnkete($nid){
        self::_init();
        $db=ENGINE::db();
        $row=$db->selectRow('SELECT ank.nid,ank.title,p.field_expert_rating_form_policy_target_id as method FROM `field_data_field_expert_rating_form_mob` fmob
inner join node ank on ank.nid=fmob.entity_id and ank.vid=fmob.revision_id
inner join field_data_field_expert_rating_form_policy p on ank.nid=p.entity_id and ank.vid=p.revision_id
WHERE fmob.`field_expert_rating_form_mob_target_id` = ?d
order by ank.created desc',$nid);
        return $row;
    }

    static function findOrgByUrl($url){
        self::_init();
        $db=ENGINE::db();
        $url=preg_replace(['~^https?://(www\.)?~','~/$~'],'',$url);
        // find org
        $row=$db->selectRow('SELECT org.title, org.nid FROM `field_data_field_mob_url`url
inner join node org on org.nid=url.entity_id and org.vid=url.revision_id 
 where url.`field_mob_url_url` like ?l and url.bundle="mob"',$url);

        return $row;
/*
        $info['posts'] = array();
        $result = $db->select("SELECT `nid`, `type`, `language`, `title`, `uid`, `created` FROM `node` ORDER BY `nid`");
        $total=mysqli_num_rows($result);
        if (!empty($total)) {
            $cnt=0;$timestart=microtime(true);$timecont=$timestart-20;
            while($data = mysqli_fetch_assoc($result)) {
                $cnt++;
                if(microtime(true)-$timecont > 5) {
                    $timecont=microtime(true);
                    printf("%s of %s - %.03f ETA\n",$cnt, $total, ($timecont-$timestart)*(($total/$cnt) -1));
                }
                $info['posts'][$data['nid']] = array(
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'language' => $data['language'],
                    'created' => $data['created'],
                    'created_read' => date("d.m.Y, G:i:s", $data['created'])
                );
                $result_body = $src_dblink->query("SELECT `body_value`, `body_summary` FROM `" . $src_prefix . "field_data_body` WHERE `entity_id` = '" . $data['nid'] . "' LIMIT 1");
                if (mysqli_num_rows($result_body)) {
                    $data_body = mysqli_fetch_assoc($result_body);
                    $info['posts'][$data['nid']]['text_short'] = $data_body['body_summary'];
                    $info['posts'][$data['nid']]['text_full'] = $data_body['body_value'];
                }
                $result_count = $src_dblink->query("SELECT `totalcount` FROM `" . $src_prefix . "node_counter` WHERE `nid` = '" . $data['nid'] . "' LIMIT 1");
                if (!empty($result_count) && mysqli_num_rows($result_count)) {
                    $data_count = mysqli_fetch_assoc($result_count);
                    $count = $data_count['totalcount'];
                } else {
                    $count = 0;
                }
                $info['posts'][$data['nid']]['reads'] = $count;
                $info['posts'][$data['nid']]['terms'] = array();
                $result_terms = $src_dblink->query("SELECT `rr_taxonomy_term_data`.`tid`, `rr_taxonomy_term_data`.`vid` FROM `rr_taxonomy_term_data` INNER JOIN `rr_taxonomy_index` ON `rr_taxonomy_index`.`tid` = `rr_taxonomy_term_data`.`tid` WHERE   `rr_taxonomy_index`.`nid` = '" . $data['nid'] . "' ORDER BY `vid`, `tid`");
                if (!empty($result_terms) && mysqli_num_rows($result_terms)) {
                    while($data_terms = mysqli_fetch_assoc($result_terms)) {
                        $info['posts'][$data['nid']]['terms'][$data_terms['vid']][] = $data_terms['tid'];
                    }
                }
            }
        }
        return false; */
    }
}