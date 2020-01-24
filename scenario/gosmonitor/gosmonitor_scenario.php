<?php
/**
 * Created by PhpStorm.
 * пример парсинга xml с помощью syntax
 * пример использования ftp_transport
 */

namespace Ksnk\scaner;

/**
 * @property spider spider
 * @tags gosmonitor
 */
class gosmonitor_scenario extends scenario {

  function __construct($joblist = null){
    require_once "gdata.php";
    require_once "config.php";
    parent::__construct($joblist = null);
  }
  /**
     * Описание структуры данных исходной таблицы Excel (csv - \t )
     * @return object
     */
    function get_s()
    {
        return (object)[
            '_' => [
                '№ п/п',
                'Номер реестровой записи',
                'Дата внесения реестровой записи',
                'Дата внесения изменений в реестровую запись',
                'Полное наименование организации, образующей инфраструктуру поддержки субъектов малого и среднего предпринимательства или имеющей право в соответствии с федеральными законами выполнять функции организаций, образующих инфраструктуру поддержки субъектов малого и среднего предпринимательства (далее соответственно - организация инфраструктуры, МСП), и ее организационно-правовая форма (для создаваемых организаций инфраструктуры - при наличии)',
                'Сокращенное наименование организации инфраструктуры (при наличии)',
                'Идентификационный номер налогоплательщика организации инфраструктуры',
                'Основной государственный регистрационный номер организации инфраструктуры; дата внесения сведений об организации инфраструктуры в Единый государственный реестр юридических лиц',
                'Планируемый срок создания организации инфраструктуры',
                17 => 'адрес организации инфраструктуры в пределах места нахождения организации инфраструктуры, указанный в Едином государственном реестре юридических лиц',
                18 => 'адрес для направления корреспонденции',
                22 => 'фамилия, имя, отчество (последнее – при наличии)',
                23 => 'контактный телефон',
                24 => 'адрес электронной почты',
                28 => 'реквизиты документа (дата, номер',
                29 => 'полное наименование сертифицирующей организации'
            ],
            'sub' => [
                9 => 'Наименования структурных подразделений организации инфраструктуры, реализующих отдельные меры поддержки субъектов МСП по отдельным направлениям поддержки (при наличии)',
                10 => 'Тип организации инфраструктуры в соответствии с частью 2 статьи 15 Федерального закона от 24 июля 2007 г. № 209-ФЗ «О развитии малого и среднего предпринимательства в Российской Федерации»',
                11 => 'форма оказываемой поддержки',
                12 => 'наименование мер поддержки или услуг',
                13 => 'условия получения поддержки',
                14 => 'требования к субъекту МСП - получателю поддержки',
                15 => 'возможный (максимально возможный) размер поддержки',
                16 => 'стоимость получения поддержки либо указание на безвозмездность предоставления поддержки',
                19 => 'контактный телефон',
                20 => 'адрес электронной почты',
                21 => 'официальный сайт в информационно-телекоммуникационной сети «Интернет»',
            ],
            'values' => [],
            'cur' => '',
            'cnt' => 3,
            'lc' => 1,
            'struct' => [],
            'first' => [0, 9, 25],
            'data' => [],
            'rowcnt' => 0,
            'tdcnt' => 0,
            'trcnt' => 0,
            'colcnt' => 0,
        ];
    }
/*
 function find($names,$tit,&$lasttit,$second){
        if(!empty($tit)) $lasttit=$tit;
        foreach($names as $n){
            if(preg_match('/^\s*'.($n['delta']+1).'\./is',$lasttit)
            && preg_match('/'.preg_replace(['/\s+/','/\./'],['\\s+','.+?'],preg_quote(trim($n['name']),'/')).'/isu',$second)
            ){
                return $n;
            }
        }
        return false;
    }
 */
    function find($names,$tit,&$lasttit,$second){
        static $ind_names=[],$_names=[];
        if(!empty($tit)) $lasttit=$tit;

        if(!isset($ind_names[$lasttit])){
            foreach($names as $n){
                if(preg_match('/'.preg_replace(['/\s+/'],['\\s+'],preg_quote($n['ind_name'],'/')).'/is',$lasttit)
                ){
                    $ind_names[$lasttit]=$n['ind_id'];
                    break;
                }
            }

        }
        if(!isset($ind_names[$lasttit])){
            foreach($names as $n){
                if(preg_match('/^\s*'.($n['delta']+1).'\./is',$lasttit)
                ){
                    $ind_names[$lasttit]=$n['ind_id'];
                    break;
                }
            }

        }
        if(!isset($_names[$second])) {
            foreach($names as $n){
                if(preg_match('/'.preg_replace(['/\s+/','/\./'],['\\s+','.+?'],preg_quote(trim($n['name']),'/')).'/isu',$second)
                ){
                    $_names[$second]=$n['cr_id'];
                }
            }
        }
        if(!isset($_names[$second]) || !isset($ind_names[$lasttit])) {
            return false;
        }

        foreach($names as $n){
            if($_names[$second]==$n['cr_id'] && $ind_names[$lasttit]==$n['ind_id']) {
                return $n;
            }
        }

        return false;
    }

    /**
     * Вставить данные в анкеты
     * @param string $data :textarea
     * @param  $testonly :checkbox[1] не менять данные
     * @param  $debug :checkbox[1] отладка
     */
    function do_updatedata ($data,$testonly=true,$debug=true){
        $csv=csv::csvStr($data,['delim'=>"\t"]);
        $names='';
        $cnt=0;
        $headers = [];
        // если row[2] не похож на url - считаем первые 2 строки заголовком
        if($row=$csv->nextRow()) {
            if(!filter_var($row[2], FILTER_VALIDATE_URL)){
                // заголовок
                $headers[]=$row;
                $headers[]=$csv->nextRow();
                $row=$csv->nextRow();
            }
            do {
                if(filter_var($row[2], FILTER_VALIDATE_URL)){
                    // попытка найти организацию по url
                    if(!($x=\gdata::findOrgByUrl($row[2]))){
                        if($x['title']!=$row[1]){
                            printf('Названия организаций не совпадают `%s` - `%s`'."\n",$x['title'],$row[1]);
                        }
                        printf("Организация `%s` (%s) не найдена\n",$row[1],$row[2]);
                        continue;
                    }
                    printf("Организация `%s` (%s)-%s\n",$row[1],$row[2],$x['nid']);
                    // попытка найти свежую анкету
                    if($a=\gdata::findAnkete($x['nid'])) {
                        printf("Найдена %s (%s)\n", $a['title'], $a['nid']);
                        if(empty($names))$names=\gdata::getCodeNames($a['method']);
                    } else {
                        printf("Анкета не найдена\n");
                        continue;
                    }
                    //заполняем данные
                    $lastheader='';
                    for($i=3;$i<count($row);$i++){
                       $param=$this->find($names,$headers[0][$i],$lastheader,$headers[1][$i]);
                       if(empty($param)){
                           printf("Не найдена позиция %s (%s)\n", $lastheader,$headers[1][$i]);
                           //break;
                       } else {
                           \gdata::write_values($a,$param,$row[$i],$testonly);
                       }
                    }


                } else {
                    continue;
                }
                //echo $row[2];
                $cnt++;//print_r($row);
            } while ($row = $csv->nextRow());
        }

        echo $cnt;
        if($debug)
            print_r($names);
    }


    /**
     * Тестировать ноду с индексом ID
     * @param $id нода
     */
    function do_test($id){
        print_r(\gdata::getnode($id));
    }

    /**
     * Данные анкеты индексом ID
     * @param $id анкета
     */
    function do_ankete($id){
      $ankete=
        \gdata::getankete($id);
      foreach($ankete as $a){
        printf("%s(%s)-%s(%s)=%s | %s(%s)\n",$a['parameter'],$a['parameter_id'],$a['cr'],$a['cr_id'],$a['cr_value'],$a['wstate'],$a['wstate_id']);
        /*
          [parameter] => Возможность для бесплатного консультирования и информирования по нормативам и требованиям к проводимым проверкам
            [parameter_id] => 3222
            [cr] => HTML доступность
            [cr_id] => 24
            [cr_value] => 0
            [wstate] => утверждено
            [wstate_id] => 25
         */
      }
    }

  /**
   * ОБновить индикатор "наличие карты сайта"
   * @param string $data :textarea
   * @param  $testonly :checkbox[1] не менять данные
   * @param  $debug :checkbox[1] отладка
   */
  function do_updateindicator ($data,$testonly=true,$debug=true)
  {
    static $maxdate;
    \gdata::_init();
    $db=\ENGINE::db();
    $csv = csv::csvStr($data, ['delim' => "\t"]);
    $cnt=0;
    if($row=$csv->nextRow()) {
      if(!filter_var($row[2], FILTER_VALIDATE_URL)){
        // заголовок
        $headers[]=$row;
        $headers[]=$csv->nextRow();
        $row=$csv->nextRow();
      }
      do {
        if(filter_var($row[2], FILTER_VALIDATE_URL)){
          // попытка найти организацию по url
          if(!($x=\gdata::findOrgByUrl($row[2]))){
            if($x['title']!=$row[1]){
              printf('Названия организаций не совпадают `%s` - `%s`'."\n",$x['title'],$row[1]);
            }
            printf("Организация `%s` (%s) не найдена\n",$row[1],$row[2]);
            continue;
          }
          //printf("Организация `%s` (%s)-%s\n",$row[1],$row[2],$x['nid']);
          // считаем время
          $val=$row[3];
          if(!empty($val) && preg_match('/(да)|нет/iu',$val,$m)){
            $val=empty($m[1])?0:1;
          }
          printf("ставим значение индикатора has_sitemap `%s` (%s | %s)-%s\n",$val,$row[2],$x['nid'],$row[1]);
          if(!isset($maxdate)){
            $maxdate=$db->selectCell('select `date` from `indicator` order by date desc limit 1');
          }

          $k=[
            'rating_type'=>'tech',
            'indicator_name'=>'sitemap',
            'date'=>$maxdate,
            'entity_id'=>$x['nid'],
            'value'=>$val,
            'points'=>$val,
            'entity_type'=>'node'
          ];
          if(!$testonly) {
            $db->insert("INSERT INTO `indicator` (?1[?1k]) VALUES (?1[?2]) ON DUPLICATE KEY UPDATE  ?1[?1k=VALUES(?1k)]", $k);
          } else {
            print_r($k);
          }

        } else {
          continue;
        }
        //echo $row[2];
        $cnt++;//print_r($row);
      } while ($row = $csv->nextRow());
    }

    echo $cnt;
    // print_r($names);
  }

  /**
   * корректировка ретинга для отсутствующих сайтов техрейтинга
   * @param $change :radio[0:не менять|1:менять]
   */
  function do_hackindicator ($change=0)
  {
    static $maxdate;
    var_export($change);

    \gdata::_init();
    $db=\ENGINE::db();
    $x=[201=>221,
      224 => 207,
      535917 => 245,// - minobrnauki.gov.ru
      535916 => 245,// - edu.gov.ru
      229 => 207, //  -.nalog.ru
      197 => 239, // - rospotrebnadzor.ru
      180 => 245,
      185 => 245];
    if(!isset($maxdate)){
      $maxdate=$db->selectCell('select `date` from `indicator` order by date desc limit 1');
    }
    foreach(['federal','municipal','regional'] as $gov) {
      $list = $db->selectCol("SELECT n.nid
        FROM node n
      LEFT JOIN field_data_field_mob_government_level gov ON n.nid = gov.entity_id AND (gov.entity_type = 'node' AND gov.deleted = 0)
      LEFT JOIN field_data_field_mob_register reg ON  n.nid = reg .entity_id AND (reg.entity_type = 'node' AND reg .deleted = 0)
      WHERE  TRUE
        -- and n.nid = 225
        AND gov.field_mob_government_level_value=? AND reg.field_mob_register_value='minec'", $gov);
      foreach(['widget','tech','expert'] as $type) {
        $rating = $db->selectCol("SELECT  DISTINCT(entity_id)  FROM rating 
WHERE TRUE
AND NOT points=0
AND rating_type=?
AND entity_id IN (?[?2])
AND  date=?
", $type, $list, $maxdate);
        $ids=array_diff($list, $rating);
        printf("%s : %s\n%s\n",$gov,$type,print_r(array_diff($list, $rating),true));
        foreach($ids as $k) {
            if(isset($x[$k])) $v=$x[$k];
            else {
              $xx=array_rand($rating);
              $v =$rating[$xx];
              $x[$k]=$v;
            }
            if($change>0) {
              $db->insert("INSERT INTO rating(rating_type,`date`,entity_id,type,value,points,place,place_raw,entity_type,group_id,group_changed)
  SELECT rating_type,`date`,? AS entity_id,type,value,points,place,place_raw,entity_type,group_id,group_changed
  FROM `rating`
  WHERE TRUE
        AND entity_id=?
        AND rating_type=?
        AND date=?
  ON DUPLICATE KEY UPDATE value=VALUES(value),points=VALUES(points),place=VALUES(place)-7,place_raw=VALUES(place_raw)-7", $k, $v, $type, $maxdate);
              $db->insert("INSERT INTO indicator (rating_type,indicator_name,`date`,entity_id,value,points,entity_type)
  SELECT rating_type,indicator_name,`date`,	? AS entity_id,value,points,entity_type FROM `indicator`
  WHERE TRUE
        AND entity_id=?
        AND rating_type=?
        AND date=?
  ON DUPLICATE KEY UPDATE value=VALUES(value),points=VALUES(points)", $k, $v, $type, $maxdate);
            } else {
              //printf("изменения не производились");
            }
        }
      }
    }
    if($change>0)
      printf("изменения не производились");
    var_export($x);
/*    return ;
    //Для всех реципиентов скопировать рейтинг с доноров

    $db->delete("delete from rating
where entity_id=?
      and date=?",173,$maxdate);
    $db->delete("delete from  `indicator`
where entity_id=?
      and rating_type='tech'
      and date=?
  ",173,$maxdate);*/
    echo 'done';

  }


  }