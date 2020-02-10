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
      \gdata::_init();
      $db=\ENGINE::db();
      $ankete=
        \gdata::getankete($id);
      $cnt=0;
      foreach($ankete as $a){
        $cnt++;
        printf("%s)\t%s(%s)-%s(%s|%s)=%s | %s(%s)\n",$a['delta']+1,$a['parameter'],$a['parameter_id'],$a['cr'],$a['cr_id'],
          $a['value_nid'],
          $a['cr_value'],$a['wstate'],$a['wstate_id']);
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
      \gdata::_init();
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
          $maxdate=\gdata::maxdate();

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
    \gdata::_init();
    $db=\ENGINE::db();

    if(!empty($t=strtotime($timestart))){
      $timestart=$t;
    }
    if(!empty($timefin)){
      if(!empty($t=strtotime($timefin))) $timefin=$t;
    } else {
      $timefin = \gdata::maxdate();
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
    echo 'done';

  }

}