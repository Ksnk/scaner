<?php
/**
 * Created by PhpStorm.
 * пример парсинга xml с помощью syntax
 * пример использования ftp_transport
 */

namespace Ksnk\scaner;
use \Google_Client, \Google_Service_Sheets;


    /**
 * @property spider spider
 * @tags gosmonitor
 */
class gosmonitor_scenario extends scenario {

  function __construct($joblist = null){
    require_once "mongo.php";
    require_once "gdata.php";
    if(file_exists($f=__DIR__.'/../../../sites/default/settings.php')){
      $databases=[]; //
      include $f;
      $db=$databases['default']['default'];
      define('DB_HOST',$db['host']);
      define('DB_NAME',$db['database']);
      define('DB_USER',$db['username']);
      define('DB_PASSWORD',$db['password']);
    } else {
      require_once "config.php";
    }
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
    function find($names,$first,$second,$debug=false){
      if(preg_match('/^\d+/',$first,$m)) {
        // поиск внутри names
        $reg = '/'.preg_replace(['/\s+/','/\./'],['\\s+','.+?'], preg_quote($second,'/') ). '/usi';

        foreach ($names as $n) {
          if (($n['delta'] + 1) == $m[0] && preg_match($reg, $n['name'])) {
            return $n;
          }
        };
/*
        printf("%s,%s\n%s\n", $first, $second,  print_r($ankete[0], true));
        $reg = '/'.preg_replace(['/\s+/','/\./'],['\\s+','.+?'], preg_quote($second,'/') ). '/usi';
        foreach ($ankete as $a) {
          if (($a['delta'] + 1) == $m[0] && preg_match($reg, $a['cr'])) {
            if($debug)
              printf("%s,%s, %s\n%s\n", $first, $second, $reg, print_r($a, true));
            return $a;
          }
        };
*/
      }
      return false;
    }

    /**
     * Вставить данные в анкеты
     * @param string $data :textarea данные
     * @param  $change :radio[0:не менять данные|1:менять]
     * @param  $debug :radio[0:только ошибки|1:все изменения] вывод
     */
    function do_updatedata ($data,$change=false,$debug=true){
      static $names=[];

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
                    if(!!($a=\gdata::findAnkete($x['nid']))) {
                        $a['site']=$x['nid'];
                        printf("Найдена %s (%s)\n", $a['title'], $a['nid']);
                        if(empty($names[$a['method']]))$names[$a['method']]=\gdata::getCodeNames($a['method']);

                    } else {
                        printf("Анкета не найдена\n");
                        continue;
                    }
                    //$ankete=\gdata::getankete($a['nid']);
                    //заполняем данные
                    $lastheader='';
                    $row2 = $csv->nextRow(); // вторая строка !

                    for($i=3;$i<count($row);$i++){
                      $check_naliche_nempty=false;
                      if(''!=$headers[0][$i]){
                        $lastheader=$headers[0][$i];
                        // Тут проверяем, что первый параметр - наличие и он заполнен
                        $check_naliche_nempty=true;
                      }
                       $param=$this->find($names[$a['method']],$lastheader,$headers[1][$i],$debug);
                      /**
                      $param >>> Array
                      (
                      [delta] => 0
                      [rt_id] => 1
                      [ind_id] => 2675
                      [ind_name] => Полное наименование госоргана
                      [ind_rel] => 3
                      [cr_id] => 20
                      [name] => наличие
                      [cr_rel] => 1
                      )
                       */
                      if(empty($param)) {
                        printf("Не найдена позиция %s (%s)\n", $lastheader, $headers[1][$i]);
                      } elseif($param['cr_rel']!=1){
                          printf("Нерелевантные данные. `%s` для `%s`(%s)\n",$row[$i], $lastheader,$headers[1][$i]);
                      } else {
                           $val=$row[$i];
                           $vals=\gdata::get('anketevalues');
                           $val_descr=$vals[$param['cr_id']];
                        if($check_naliche_nempty){
                          // первый параметр - наличие
                          if($param['name']!='наличие'){
                            printf("Первый параметр группы не `наличие`. `%s` для `%s`(%s)\n",$val, $lastheader,$headers[1][$i]);
                          } elseif(!preg_match($val_descr['reg'],$val)){
                            printf("Параметр `наличие` не заполнен. `%s` для `%s`(%s)\n",$val, $lastheader,$headers[1][$i]);
                          }
                        }
                           if(!preg_match($val_descr['reg'],$val)) {
                             if(''!=trim($val))
                               printf("Некорректные данные. `%s` для `%s`(%s)\n",$val, $lastheader,$headers[1][$i]);
                             $val=$val_descr['min'];
                           }
                           \gdata::write_values($param, $a, $val,trim($row2[$i]), $change,$debug);
                      }
                      // if($i>8) break;
                    }


                } else {
                    continue;
                }
                // пересчитываем КИД

                $vals=\gdata::get('anketevalues');
                $ankete=\gdata::getankete($a['nid']);

                if(empty($names[$a['method']]))$names[$a['method']]=\gdata::getCodeNames($a['method']);

                $rcnt=0;
                $rel=0;
                $lastind=0;
                $calc=['relevancy_sum'=>0,'parameter_sum'=>0];
                foreach ($ankete as $aline) {
                  /**
                   * aline:
                    [delta] => 0
                    [parameter] => Полное наименование госоргана
                    [parameter_id] => 2675
                    [cr] => наличие
                    [cr_id] => 20
                    [cr_value] => 1
                    [wstate] => утвержден один параметр
                    [wstate_id] => 28
                    [value_nid] => 664982
                   * param:
                    [delta] => 0
                    [rt_id] => 1
                    [ind_id] => 2675
                    [ind_name] => Полное наименование госоргана
                    [ind_rel] => 3
                    [cr_id] => 20
                    [name] => наличие
                    [cr_rel] => 1
                   */

                  if(!empty($aline['cr'])) {
                    $param = $this->find($names[$a['method']]
                      , ($aline['delta'] + 1) . '. ' . $aline['parameter'], $aline['cr']);
                    if (empty($param)) {
                      printf("КИД.Не найден параметр. `%s. %s`(%s) для %s\n"
                        , $aline['delta'] + 1, $aline['parameter'], $aline['cr'], $a['nid']);
                    } else if ($param['cr_rel'] != 1) {
                      printf("КИД.Нерелевантный критерий заполнен. `%s. %s`(%s) для %s\n",$param['delta']+1,$param['ind_name'],$param['name'], $a['nid']);
                    } else {
                      if($lastind!=$param['ind_id']){
                        // обсчитываем предыдущее значение
                        if(isset($calc['relevancy'])) {
                          if($calc['relevancy']<0)
                            $calc['relevancy']=0-$calc['relevancy'];
                          $calc['relevancy_sum'] += $calc['relevancy'];
                          $calc['parameter_sum'] += ($calc['criterion_mult'] * $calc['relevancy']);
                        }
                        // начинаем новое
                        $calc['relevancy']=$param['ind_rel'];
                        $calc['criterion_mult']=1;
                        $lastind=$param['ind_id'];
                      }
                      $rcnt++;
                      $v = $vals[$aline['cr_id']];
                      $vv=$v['kid'][$aline['cr_value']];
                      if($calc['relevancy']<0)
                        $vv=1-$vv;
                      $rel += $v['kid'][$aline['cr_value']];
                      $calc['criterion_mult']*=$vv;
                    }
                  } else {
                    $rcnt++;
                  }
                  //if($rcnt>3) break;
                }
              // обсчитываем последнее значение
                if($calc['relevancy']<0)
                    $calc['relevancy']=0-$calc['relevancy'];
                $calc['relevancy_sum'] += $calc['relevancy'];
                $calc['parameter_sum'] += ($calc['criterion_mult'] * $calc['relevancy']);

                $k = [
                  'entity_type' => 'node',
                  'bundle' => 'mob_expert_rating_form_data',
                  'deleted' => 0,
                  'entity_id' => $a['nid'],
                  'revision_id' => $a['vid'],
                  'language' => 'und',
                  'delta' => 0,
                  'field_expert_rating_form_kid_value' => ($calc['parameter_sum'] / $calc['relevancy_sum']) * 100.0
                ];
                if($change) {
                  $db=\gdata::get('db');
                  $db->insert("INSERT INTO `field_data_field_expert_rating_form_kid` (?1[?1k]) VALUE (?1[?2]) ON DUPLICATE KEY UPDATE  ?1[?1k=VALUES(?1k)]", $k);
                }
                printf("кид: %s\n",$k['field_expert_rating_form_kid_value']);
              /*               $node = node_load($form_data_nid);
                             $node->field_expert_rating_form_kid = array('und' => array(0 => array('value' => $value))); */

                //echo $row[2];
                $cnt++;//print_r($row);
             // if($cnt>10) break;
            } while ($row = $csv->nextRow());
        }

        printf("Записан%s %s анкет%s\n",\gdata::plural($cnt,'а|ы|o'), $cnt,\gdata::plural($cnt,'а|ы'));
    }


    /**
     * Тестировать ноду с индексом ID
     * @param $id нода
     */
    function do_readnode($id){
        print_r(\gdata::getnode($id));
    }

    /**
     * Данные анкеты индексом ID
     * @param $id анкета
     */
    function do_ankete($id){
      $db=\gdata::get('db');
      $ankete=
        \gdata::getankete($id);
      $cnt=0;
      foreach($ankete as $a){
        $cnt++;
        printf("%s)\t%s(%s)-%s(%s|%s)=%s | %s(%s)\n",$a['delta']+1,$a['parameter'],$a['parameter_id'],$a['cr'],$a['cr_id'],
          $a['value_nid'],
          $a['cr_value'],$a['wstate'],$a['wstate_id']);
      }
      printf("Всего: %s\n",$cnt);
    }

    /**
     * отменить поля в анкетах по списку
     * @param string $data :textarea - сsv [{название,url, диапазоны через запятую},...]
     * @param  $change :radio[0:не менять данные|1:менять]
     * @param  $debug :radio[0:без отладки|1:отладка]
     */
    function do_denyankettefields($data,$change,$debug)
    {
     // \gdata::_init();
     // session_write_close();
     // $_SESSION=[];
     // \gdata::startdrupal();
      $csv = csv::csvStr($data, ['delim' => "\t"]);
      $urlrow=null;
      if ($row = $csv->nextRow()) {
        if(is_null($urlrow)){
          for($i=0;$i<count($row);$i++){
            if(filter_var($row[$i], FILTER_VALIDATE_URL)) {
              $urlrow = $i;
              break;
            }
          }
        }
        if (is_null($urlrow)) {
          // заголовок
          $headers[] = $row;
          $headers[] = $csv->nextRow();
          $row = $csv->nextRow();
          for($i=0;$i<count($row);$i++){
            if(filter_var($row[$i], FILTER_VALIDATE_URL)) {
              $urlrow = $i;
              break;
            }
          }
        }
        if (is_null($urlrow)) {
          echo "Проверьте данные.";
          return;
        }

        do {
          if (filter_var($row[$urlrow], FILTER_VALIDATE_URL)) {
            // попытка найти организацию по url
            if (!($x = \gdata::findOrgByUrl($row[$urlrow]))) {
              if ($x['title'] != $row[$urlrow-1]) {
                printf('Названия организаций не совпадают `%s` - `%s`' . "\n", $x['title'], $row[1]);
              }
              printf("Организация `%s` (%s) не найдена\n", $row[$urlrow-1], $row[1]);
              continue;
            }
            printf("Организация `%s` (%s) найдена\n", $row[$urlrow-1], $x['nid']);
            if($a=\gdata::findAnkete($x['nid'])) {
              printf("Найдена %s (%s)\n", $a['title'], $a['nid']);
              if(empty($names))$names=\gdata::getCodeNames($a['method']);
            } else {
              printf("Анкета не найдена\n");
              continue;
            }
            // анализ диапазонов
            $punks=[];
            $list=preg_split('~,|\n~',$row[$urlrow+1]);
            foreach($list as $l){
              if(preg_match('/^\s*(\d+)\s*$/',$l,$m)){
                $punks[]=(int)$m[1];
              } else if(preg_match('/^\s*(\d+)\s*\-\s*(\d+)\s*$/',$l,$m)){
                if($m[1]>$m[2]){$x=$m[2];$m[2]=$m[1];$m[1]=$x;}
                for($x=$m[1];$x<=$m[2];$x++)$punks[]=$x;
              } else if(''!=trim($l)) {
                printf("нипонил - `%s`\n", trim($l));
              }
            }
            $fields=implode(',',$punks);
            printf("поля анкеты: %s\n",$fields);
            $ankete=\gdata::getankete($a['nid']);
            foreach($punks as $f){
              foreach($ankete as $al){
                if($al['delta']+1 == $f){
                  if($change){
                    $x='Удалили';
                    \gdata::deletenode($al['value_nid']);
                  } else {
                    $x='Планировали уладить';
                  }
                  printf("%s значение %s\n",$x,$al['value_nid']);
                  break ;
                }
              }
            }
          }

        } while($row = $csv->nextRow());
      }
      echo "done.";
    }

  /**
   * Обновить индикатор "наличие карты сайта"
   * @param string $data :textarea - сsv [{№, название, url, значение},...]
   * @param  $testonly :checkbox[1] не менять данные
   * @param  $debug :checkbox[1] отладка
   */
  function do_updateindicator ($data,$testonly=true,$debug=true)
  {
    $db=\gdata::get('db');
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
          $maxdate=\gdata::get('maxdate');

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
   * перенос рейтинга с одной организации на другую
   * @param $src - ID организации источника
   * @param $dest - ID организации получателя
   * @param $ratings - списокк рейтингов, через запятую
   * @param $timestart - начиная с даты
   * @param $timefin - заканчивая датой (пусто - до текущей)
   * @param $change :radio[0:не менять|1:менять]
   */
  function do_transer_rating ($src,$dest,$ratings, $timestart,$timefin,$change)
  {
    $db=\gdata::get('db');

    if(!empty($t=strtotime($timestart))){
      $timestart=$t;
    }
    if(!empty($timefin)){
      if(!empty($t=strtotime($timefin))) $timefin=$t;
    } else {
      $timefin = \gdata::get('maxdate');
    }
    $db=\ENGINE::db();
    // ищем рейтинг

    // даты
    $dates=$db->selectCol('select distinct date from indicator where date>=? and date <=?',$timestart, $timefin);
    // читаем
    $sql='select entity_id, date, value, points, rating_type, indicator_name from indicator
where date in (?[?2]) and entity_id=?';
    if(!empty($ratings)){
      $sql.=$db->_([' and rating_type in (?[?2])',preg_split('/\s*[;,]\s*/',$ratings)]);
    }
    $data=$db->select($sql,$dates,$src);
    foreach($data as $d){
      if($change) {
        $d['entity_id']=$dest;
        $db->insert("INSERT INTO indicator (?1[?1k]) values(?1[?2])
      ON DUPLICATE KEY UPDATE value=VALUES(value),points=VALUES(points)", $d);
      }
    }
    // читаем рейтинг
    $sql='select rating_type,`date`,entity_id,type,value,points,place,place_raw,entity_type,group_id,group_changed from rating
where date in (?[?2]) and entity_id=?';
    if(!empty($ratings)){
      $sql.=$db->_([' and rating_type in (?[?2])',preg_split('/\s*[;,]\s*/',$ratings)]);
    }
    $data=$db->select($sql,$dates,$src);
    foreach($data as $d){
      if($change) {
        $d['entity_id']=$dest;
        $db->insert("INSERT INTO rating (?1[?1k]) values(?1[?2])
      ON DUPLICATE KEY UPDATE value=VALUES(value),points=VALUES(points)", $d);
      }
    }
    echo 'done.';


    /*    $db->insert("INSERT INTO indicator (rating_type,indicator_name,`date`,entity_id,value,points,entity_type)
      SELECT rating_type,indicator_name,`date`,	? AS entity_id,value,points,entity_type FROM `indicator`
      WHERE TRUE
            AND entity_id=?
            AND rating_type=?
            AND date=?
      ON DUPLICATE KEY UPDATE value=VALUES(value),points=VALUES(points)", $k, $v, $type, $maxdate);
      */
  }

  /**
   * корректировка рейтинга для отсутствующих сайтов техрейтинга
   * @param $change :radio[0:не менять|1:менять]
   */
  function do_hackindicator ($change=0)
  {
    var_export($change);

    $db=\gdata::get('db');
    $x=[201=>221,
      224 => 207,
      535917 => 245,// - minobrnauki.gov.ru
      535916 => 245,// - edu.gov.ru
      229 => 207, //  -.nalog.ru
      197 => 239, // - rospotrebnadzor.ru
      180 => 245,
      185 => 245];

    $maxdate=\gdata::get('maxdate');
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
    echo 'done';

  }

  /**
   * тестировать
   */
  function do_test(){
    print_r(\gdata::get('anketevalues'));
  }

  /**
   * Обновить записи коллекции Hosts в монге
   * @param int $change :radio[0:не менять|1:менять]
   * @throws \Exception
   */
  function do_updateHosts ($change=0)
  {
    var_export($change);

    $db=\gdata::get('db');

    $mongo= \gdata::get('mongo');
    $collectionHost=$mongo->selectCollection('Host');

   // $host = $collectionHost->findOne(['gosmonitor_id' => intval(197)]);
    // найдем все организации участвующие в рейтинге
    $ids=$db->selectCol('select  distinct entity_id from field_data_field_mob_register where field_mob_register_value in (\'minec\',\'over\')  ');
    $cnt=['total'=>0,'insert'=>0,'update'=>0];
    //$org=\gdata::getnode(239733);
    //print_r($org);
    foreach($ids as $o_id){
      $org=\gdata::getnode($o_id);
      $host = $collectionHost->findOne(['gosmonitor_id' => intval($o_id)]);

      $od_link=$db->selectRow('select field_mob_link_to_chapter_url as url from field_data_field_mob_link_to_chapter where entity_id=? and  revision_id=? and deleted=0', $org->nid,$org->vid);
      $add_urls=$org->field_additional_urls;
      $site = [
        // 'curator' => $curators,
        'name' => $org->title,
        'site' => $org->field_mob_url,
        'gosmonitor_id' => intval($o_id),
        'add_url' => !empty($add_urls) ? $add_urls : null,
        'short_name' => $org->field_mob_name_short,
        'od_link' => empty($od_link['url'])?null:$od_link['url'],
        'changed' => $org->changed,
        'latest_record_update' => new \MongoDate(time())
      ];
      if(!isset($host['_id'])){
        $host = $collectionHost->findOne(['site' => $site['site']]);
      }
      $hostId = null;
      try {
        if (!isset($host['_id'])) {
          printf("Не найдена запись %s в монге", $o_id);
          //watchdog('widget', 'site not found will be inserted: ' . var_export($site, true));
          if ($change) {
            $collectionHost->insert($site, ['fsync' => true]);
          } else {
            printf("будем вставлять в монгу %s\n", print_r($site, true));
          }
          $cnt['insert']++;
          //break;
        } else {
          $hostId = new \MongoId($host['_id']->{'$id'});
          // сравниваем ключевые поля
          $update = [];
          foreach (['name', 'gosmonitor_id', 'site', 'add_url', 'short_name', 'od_link'] as $x) {
            if (!empty($site[$x]) && $host[$x] != $site[$x]) {
              $update[$x] = $site[$x];
            }
          }
          if (!empty($update)) {
            if ($change) {
              $collectionHost->update(['_id' => $hostId], ['$set' => $update], ['fsync' => true]);
            } else {
              printf("будем обновлять %s в монгу %s\n", $hostId, print_r($update, true));
            }
            $cnt['update']++;
          }
        }
      } catch(\Exception $e){
        printf("exception %s\nработали с %s %s %s", $e->getMessage(),
          $hostId,$o_id, print_r($site, true));
      }
     // print_r($host);
      $cnt['total']++;
      //if($cnt> 10 ) break;
    }
    print_r( $cnt);
    /*
    if (!isset($host['_id'])) {
      watchdog('widget', 'site not found by id, trying to find by URL: ' . $siteUrl);
      $host = $collectionHost->findOne(['site' => $siteUrl]);
      $mustUpdateId = true;
    }
    if (isset($host['_id'])) {
      $hostId = new MongoId($host['_id']->{'$id'});
      if ($mustUpdateId) {
        $collectionHost->update(['_id' => $hostId], ['$set' => [
          'gosmonitor_id' => intval($siteId),
          'changed' => time(),
          'latest_record_update' => new MongoDate(time())
        ]], ['fsync' => true]);
      }
    } */
  }


  function do_authlink(){
      // 4/xQF_91gkq-eg7-lsNlVnYE-mKphsNwOcW09beBjBSnBLyAUwyfB3cGE

  }
  /**
   * Читать файл с анкетами OD
   * @param $url
   */
  function do_update_od_ankete($url){
      /**
       * Returns an authorized API client.
       * @return Google_Client the authorized client object
       */
      function getClient()
      {
          $client = new Google_Client();
          $client->setApplicationName('Google Sheets API PHP Quickstart');
          $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
          $client->setAuthConfig(__DIR__.'/credentials.json');
          $client->setAccessType('offline');
          $client->setPrompt('select_account consent');

          // Load previously authorized token from a file, if it exists.
          // The file token.json stores the user's access and refresh tokens, and is
          // created automatically when the authorization flow completes for the first
          // time.
          $tokenPath = 'token.json';
          if (file_exists($tokenPath)) {
              $accessToken = json_decode(file_get_contents($tokenPath), true);
              $client->setAccessToken($accessToken);
          }

          // If there is no previous token or it's expired.
          if ($client->isAccessTokenExpired()) {
              // Refresh the token if possible, else fetch a new one.
              if ($client->getRefreshToken()) {
                  $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
              } else {
                  // Request authorization from the user.
                  $authUrl = $client->createAuthUrl();
                  printf("Open the following link in your browser:\n%s\n", $authUrl);
                  /*
                  print 'Enter verification code: '; */
                  $authCode = '4/xQF_91gkq-eg7-lsNlVnYE-mKphsNwOcW09beBjBSnBLyAUwyfB3cGE'; //trim(fgets(STDIN));

                  // Exchange authorization code for an access token.
                  $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                  $client->setAccessToken($accessToken);

                  // Check to see if there was an error.
                  if (array_key_exists('error', $accessToken)) {
                      throw new Exception(join(', ', $accessToken));
                  }
              }
              // Save the token to a file.
              if (!file_exists(dirname($tokenPath))) {
                  mkdir(dirname($tokenPath), 0700, true);
              }
              file_put_contents($tokenPath, json_encode($client->getAccessToken()));
          }
          return $client;
      }


// Get the API client and construct the service object.
      $client = getClient();
      $service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
      $spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
      $range = 'Class Data!A2:E';
      $response = $service->spreadsheets_values->get($spreadsheetId, $range);
      $values = $response->getValues();

      if (empty($values)) {
          print "No data found.\n";
      } else {
          print "Name, Major:\n";
          foreach ($values as $row) {
              // Print columns A and E, which correspond to indices 0 and 4.
              printf("%s, %s\n", $row[0], $row[4]);
          }
      }
  }

}