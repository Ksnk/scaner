<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

//require '../../autoload.php';
namespace Ksnk\scaner;

/**
 * Оформление некоторых идиотизмов в задачах старого monitoring'а
 * Class monitoring_scenario
 *
 * Примерная схема работы
 * - если требуется добавить новые данные в orgreg
 * -- импортировать данные Организации и Услуги в Excel
 * -- Тестировать c&p из Excel -> оставить ID пустым,
 * -- скопировать из Excel строки Организации в поле А, Услуги в поле B
 * -- получить изображение таблицы и вставить его в конец готовых данных
 * - если требуется модифицировать данные
 * -- прочитать страницу с ID="xxx" и вывести информацию ->скопировать вывод скрипта в блокнот, потом в Excel
 * -- модифицироват нужным образом в Excel
 * -- Тестировать c&p из Excel -> поставить номер и данные организаций и услуг в нужные поля формы
 * -- полученную таблицу вставить в нужное место, заменив старую таблицу
 *
 * @tags msp
 */
class monitoring_scenario extends scenario
{

  function csvquote($data, $quote = '"')
  {
    if (is_array($data)) {
      $result = [];
      foreach ($data as $d) $result[] = $this->csvquote($d);
      return $result;
    }
    if (preg_match('/[\s' . preg_quote($quote) . ']/s', $data)) {
      return $quote . str_replace($quote, $quote . $quote, $data) . $quote;
    } else
      return $data;
  }

  function csvunquote($data)
  {
    if (preg_match('~^([\'"])(.*)\\1$~s', $data, $m)) {
      return trim(str_replace($m[1] . $m[1], $m[1], $m[2]));
    }
    return $data;
  }

  const reghtml =
    //'d:/projects/monitoring/monitoring.corpmsp.ru/webapps/StartPage/orgreg.html';
    '../data/monitoriing/orgreg.html';
  //   data/monitoriing/orgreg_tpp.html'

  /**
   * ОПисание структуры исходной таблицы и заголовков полей при ее пересоздании
   * Заголовки не нужны, по сути...
   * @return object
   */
  function get_s()
  {
    return (object)[
      'values' => [],
      'cur' => '',
      'cnt' => 3,
      'lc' => 1,
      'struct' => [],

      '_' => [
        '№ п/п',
        'Номер реестровой записи',
        'Дата внесения реестровой записи',
        'Дата внесения изменений в реестровую запись',
        'Полное наименование организации, образующей инфраструктуру поддержки субъектов малого и среднего предпринимательства или имеющей право в соответствии с федеральными законами выполнять функции организаций, образующих инфраструктуру поддержки субъектов малого и среднего предпринимательства (далее соответственно - организация инфраструктуры, МСП) и ее организационно-правовая форма',
        'Сокращенное наименование организации инфраструктуры (при наличии)',
        'Идентификационный номер налогоплательщика организации инфраструктуры',
        'Основной государственный регистрационный номер организации инфраструктуры; дата внесения сведений об организации инфраструктуры в Единый государственный реестр юридических лиц',
        'Планируемый срок создания организации инфраструктуры',
        17 => 'Адрес в пределах места нахождения, указанный в едином государственном реестре юридических лиц',
        18 => 'Адрес для направления корреспонденции',
        22 => 'Ф.И.О. руководителя организации инфраструктуры',
        23 => 'Контактный телефон руководителя организации инфраструктуры',
        24 => 'Адрес электронной почты руководителя организации инфраструктуры',
        28 => 'реквизиты документа (дата, номер',
        29 => 'полное наименование сертифицирующей организации'
      ],
      'sub' => [
        9 => 'Наименования структурных подразделений организации инфраструктуры, реализующих меры поддержки субъектов МСП по отдельным направлениям поддержки (при наличии)',
        10 => 'Тип организации инфраструктуры в соответствии с частью 2 статьи 15 Федерального закона от 24 июля 2007 г. № 209-ФЗ О развитии малого и среднего предпринимательства в Российской Федерации',
        11 => 'Форма оказываемой поддержки',
        12 => 'Наименование мер поддержки или услуг',
        13 => 'Условия получения поддержки',
        14 => 'Требования к субъекту МСП - получателю поддержки',
        15 => 'Возможный (максимально возможный) размер поддержки',
        16 => 'Стоимость получения поддержки либо указание на безвозмездность предоставления поддержки',
        19 => 'Контактный телефон организации инфраструктуры',
        20 => 'Адрес электронной почты организации инфраструктуры',
        21 => 'Официальный сайт организации инфраструктуры',
      ],
      'low' => [
        25 => 'тип документа',
        26 => 'реквизиты документа (вид, наименование, дата, номер)',
        27 => 'номер пункта (статьи) части документа',
      ],
      'first' => [0, 9, 25],
      'data' => [],
      'rowcnt' => 0,
      'tdcnt' => 0,
      'trcnt' => 0,
      'colcnt' => 0,
    ];
  }

  /**
   * Парсинг одной записи таблицы
   * @param $id
   * @return null|object
   */
  function get_ByCode($id=0)
  {
    /** @var scaner $scaner */
    $_ = $this->get_s();

    $scaner = $this->scaner;
    if (!empty($id)) {
      $scaner->newhandle(self::reghtml);
      $scaner
        ->scan('~<tr class=["\']row_org\s+row_reg_(?:(?!<tr).)*?>\s*(' . $id . ')\s*</div>~smi', 1, 'num');
    } else {
      // следующий код
      $scaner
      //  ->until()
        ->scan('~<\!\-\-|<tr class=["\']row_org\s+row_reg_(?:(?!<t).)*?<t(?:(?!<t).)*?div>\s*(\d+?\s*)<~smi', 1, 'num');
    }
    if ($scaner->found) {
      $pos=$scaner->reg_begin;
      $res = $scaner->getresult();
      if (empty($res)) {
        $scaner->scan('~\-\->~smi');
        return $this->get_ByCode();
      }
      //$scaner->until('~<tr class=["\']row_org\s+row_reg_(?:(?!<t).)*?<t(?:(?!<t).)*?div>\s*(\d+?\s*)<~smi');
      $scaner->position($pos );
      $scaner->syntax([
        'attr' => '\s*:name:\s*=\s*:quoted:',
        'name' => '\w+',
        'body' => '.*?',
        'quoted' => '(?:"[^"]*"|\'[^\']*\'|[^\'"]*)',
        'tag' => 'tr|td|div',
        'open' => '<:tag:(?::attr:|)>',
        'close' => '</:tag:>',
      ], '~:open:|:close:~smu',
        function ($line) use (&$_) {
          //echo htmlspecialchars(print_r($line, true));

          if (\UTILS::val($line, 'open') != '' && $line['tag'] == 'tr') {
            $_->tdcnt = 0;
            if (count($_->struct) <= $_->trcnt) $_->struct[] = [];
            //ksort($_->struct)[$_->trcnt]);
            $_->colcnt = 0;
          }
          if (\UTILS::val($line, 'open') != '' && $line['tag'] == 'td') {

            if (isset($_->struct[0]) && count($_->struct[0]) > 0)
              for ($i = 0; $i < count($_->struct[0]); $i++) {
                if (isset($_->struct[$_->trcnt][$_->tdcnt])) $_->tdcnt++; else break;
              }
            //printf('tr> %s %s',$_->trcnt,$_->tdcnt);
            $rowspan = 1;

            if (isset($line['name'])){
              if( $line['name'] == 'rowspan') {
                $rowspan = trim($line['quoted'], '"\'');
              }
            } else {
               // echo 'xxx';
            }
            for ($i = 0; $i < $rowspan; $i++) {
              if (count($_->struct) < $_->trcnt + $i) $_->struct[] = [];
              $_->struct[$_->trcnt + $i][$_->tdcnt] = $_->trcnt . 'x' . $_->tdcnt;
            }
            $_->rowcnt = max($_->rowcnt, $rowspan);
          }

          if (\UTILS::val($line, 'close') != '' && trim($line['_skiped']) != '') {
            $_->cur .= trim($line['_skiped']);
          }
          if (\UTILS::val($line, 'close') != '' && 'td' == $line['tag']) {
            foreach (['_', 'sub', 'low'] as $sec) {

              if (isset($_->{$sec}[$_->tdcnt])) {
                if (in_array($_->tdcnt, $_->first)) {
                  if (!isset($_->values[$sec]))
                    $_->values[$sec] = [[]];
                  else
                    $_->values[$sec][] = [];
                }
                $cnt = count($_->values[$sec]) - 1;
                $_->values[$sec][$cnt][$_->tdcnt] = //sprintf('%s x %s %s', $_->trcnt, $_->tdcnt, $_->cur);
                  $this->csvunquote($_->cur);
              }
            }
            //$_->values[] = sprintf('%s x %s %s', $_->trcnt, $_->tdcnt, $_->cur);
            $_->tdcnt++;
            $_->colcnt++;
            $_->cur = '';
          }
          if (\UTILS::val($line, 'close') != '' && 'tr' == $line['tag']) {
            $_->trcnt++;
            $_->rowcnt -= 1;
            //printf(' rowcnt: %s', $_->rowcnt);
            if ($_->rowcnt <= 0) {
                return false;
            }
          }
          return true;
        }, false);
      return $_;

    } else {
      return null;
    }
  }

  /**
   *  перенумеровать все номера
   * @param string $date :date Дата весения правок
   * @param bool $createcsv :radio[0:Не надо|1:Создать] Сделать CSV
   */
  function do_renumber($date = '', $createcsv=false)
  {
    if (empty($date)) $date = time();
    else $date = strtotime($date);

    $editor = new editor(self::reghtml);
    /** @var scaner $scaner */
    $scaner = $this->scaner;
    $scaner->newhandle(self::reghtml);
    $curr = 1;
    $pastpos = 0;
    do {
      $scaner
        ->scan('~<\!\-\-|<tr class=["\']row_org\s+row_reg_(?:(?!<td).)*?<td(?:(?!<td).)*?div>\s*(\d+?\s*)<~smi', 1, 'num');
      if ($scaner->found) {
        $res = $scaner->getResult();
        if (empty($res)) {
          // start of html-comment found
          $scaner
            ->scan('~\-\->~s');
          continue;
        }
        $num = \UTILS::val($res, 'num');
        $pastpos = $scaner->getpos();
        if ($curr != $num) {
          $editor->longedit($pastpos - 1 - mb_strlen($num, '8bit'), mb_strlen($num, '8bit'), $curr);
          $curr++;
        } else {
          $curr++;
        }
        //echo \UTILS::val($res,'num').' ';
      } else
        break;
    } while (true);
    $scaner->position($pastpos);
    foreach ([
               ['~/files/opendata/reestroi/data\-(\d+)(\-structure\-20171024\.csv)~smi', date('Ymd', $date)],
               ['~/files/opendata/reestroi/data\-(\d+)(\-structure\-20171024\.csv)~smi', date('Ymd', $date)],
               ['~Дата\s+последнего\s+внесения\s+изменений.*?>(\d\d\.\d\d.\d\d\d\d)~sui', date('d.m.Y', $date)],
               ['~Дата\s+актуальности.*?>(\d\d\.\d\d.\d\d\d\d)~sui', date('d.m.Y', strtotime('+1 month', $date))]
             ] as $k => $v) {
      $res = $scaner
        ->scan($v[0], 1, 'date', 2, 'tail')
        ->getResult();
      if (!$scaner->found) break;
      if (!isset($res['tail'])) $res['tail'] = '';
      if ($res['date'] != $v[1])
        $editor->longedit($scaner->getpos() - mb_strlen($res['tail'], '8bit') - mb_strlen($res['date'], '8bit'), mb_strlen($res['date'], '8bit'), $v[1]);
    }
    $scaner->close();
    $editor->update();
    if($createcsv){
      $this->_importdata($date);
    }
    echo "\nDone.";
  }

  /**
   * Импортировать все в CSV
   * @param $date - Дата внесения правок
   */
  function _importdata($date='')
  {
    if(!is_int($date))
      $date=empty($date)?time():strtotime($date);
    $this->scaner->newhandle(self::reghtml);
    $this->scaner->until('/Создаваемые организации инфраструктуры/ui');
    $result=null;
    $_ = function ($where,$param=[]) use (&$result) {
      if(is_array($where)){
        $res=[];$from=[];$to=[];
        foreach($param as $k=>$p){
          $from[]='{'.$k.'}';
          $to[]=$p;
        }
        foreach($where as $w){
          if($w=='{cnt}')
            $res[]=$param['cnt'];
          else
            $res[]=\UTILS::val($result,'values|'.str_replace($from,$to,$w));
        }
        return $res;
      }
      return
        \UTILS::val($result,'values|'.$where);
    };
    $row=['rec_no'=>'{cnt}'
      ,'registry_rec_no'=>'_|0|1'
      ,'registry_rec_created'=>'_|0|2'
      ,'registry_rec_modified'=>'_|0|3'
      ,'org_name_full'=>'_|0|4'
      ,'org_name_short'=>'_|0|5'
      ,'org_inn'=>'_|0|6'
      ,'org_reg_no_and_date'=>'_|0|7'
      ,'org_planned_creation_date'=>'_|0|8'
      ,'org_dept'=>'sub|{i}|9'
      ,'org_type'=>'sub|{i}|10'
      ,'support_form'=>'sub|{i}|11'
      ,'support_services'=>'sub|{i}|12'
      ,'support_conditions'=>'sub|{i}|13'
      ,'support_requirements'=>'sub|{i}|14'
      ,'support_amount'=>'sub|{i}|15'
      ,'support_cost'=>'sub|{i}|16'
      ,'org_address_egrul'=>'_|0|17'
      ,'org_address'=>'_|0|18'
      ,'org_phone_number'=>'sub|{i}|19'
      ,'org_email'=>'sub|{i}|20'
      ,'org_web_site'=>'sub|{i}|21'
      ,'org_head_name'=>'_|0|22'
      ,'org_head_phone_number'=>'_|0|23'
      ,'org_head_email'=>'_|0|24'
      ,'budget_act'=>'low|0|26'
      ,'budget_act_ref'=>'low|0|27'
      ,'msp_program'=>'low|1|26'
      ,'msp_program_ref'=>'low|1|27'
      ,'legal_act'=>'low|2|26'
      ,'legal_act_ref'=>'low|2|27'
      ,'org_cert'=>'_|0|28'
      ,'org_cert_issuer'=>'_|0|29'];

    $fh=fopen(dirname(self::reghtml).'/data-'.date('Ymd',$date).'-structure-20171024.csv','w+');
    fwrite($fh,csv::BOM);
    fputcsv($fh,array_keys($row),';');
    $cnt=0;
    $waitfor=1;
    while($result=$this->get_ByCode()){
      if(!isset($result->values['sub'])){
        $result->values['sub']=[[]];
      }
      // контроль целостности исходной ьаблицы
      if($_('_|0|0')!=$waitfor){
        printf("waiting object `%s` instead of `%s`\n",$waitfor,$_('_|0|0'));
        $waitfor=$_('_|0|0')+1;
      } else {
        $waitfor++;
      }
    // сливаем в таблицу

      for( $i =0; $i<count($result->values['sub']);$i++ ) {
        if(strlen(trim($_('_|0|8')))>3) break 2;
        fputcsv($fh, $_(array_values($row),['cnt'=>++$cnt,'i'=>$i]), ';');
      }
    }
    fclose($fh);
    printf("Total: %s objects produced %s lines.\n",$waitfor-1,$cnt);
  }

  /**
   * прочитать страницу с ID="xxx" и вывести информацию
   * @param $id
   */
  function do_edititem($id)
  {
    $result = $this->get_ByCode($id);
    $k = 0;
    while (true) {
      if (!isset($result->values['_'][$k])) break;
      echo "\nИмпорт организаций\n";
      echo '' . implode("\t", $this->csvquote([
          $result->values['_'][$k][4], // полное
          $result->values['_'][$k][5], // сокр
          $result->values['_'][$k][6], // inn
          $result->values['_'][$k][7], // огрн
          $result->values['_'][$k][17], // адрес 1
          $result->values['_'][$k][18], // inn
          $result->values['_'][$k][22], // inn
          $result->values['_'][$k][23], // inn
          $result->values['_'][$k][24], // inn

          $result->values['low'][0][26], // inn
          $result->values['low'][0][27], // inn
          $result->values['low'][1][26], // inn
          $result->values['low'][1][27], // inn
          $result->values['low'][2][26], // inn
          $result->values['low'][2][27], // inn

          $result->values['_'][$k][28], // inn
          $result->values['_'][$k][29]]) // inn
        );
      echo "\nПодразделения\n";
      $j = 0;
      while (true) {
        if (!isset($result->values['sub'][$j])) break;
        echo '' . implode("\t", $this->csvquote([
            $result->values['_'][$k][4],
            $result->values['_'][$k][6],
            $result->values['sub'][$j][9], // полное
            $result->values['sub'][$j][10], // полное
            $result->values['sub'][$j][11], // полное
            $result->values['sub'][$j][12], // полное
            $result->values['sub'][$j][13], // полное
            $result->values['sub'][$j][14], // полное
            $result->values['sub'][$j][15], // полное
            $result->values['sub'][$j][16], // полное
            $result->values['sub'][$j][19], // полное
            $result->values['sub'][$j][20], // полное
            $result->values['sub'][$j][21], // полное
          ])), "\n";
        $j++;
      }
      $k++;
    }
    //var_export($scaner);
    //$this->joblist->append_scenario('scan_book_pages',array("http://www.rfbr.ru/rffi/ru/books/"));
  }

  function result2html($result){
    $_ = function ($where) use ($result) {
      return \UTILS::val($result->values, $where);
    };
    // вариант создания нового документа
    // выводим HTML рыбу
    $rows = max(3, 3 * count($result->values['sub']));
    $rowsx3 = max(1, count($result->values['sub']));
    $regcode = substr($_('_|0|6'), 0, 2);
    // первая строка
    echo '<tr class=\'row_org row_reg_' . $regcode . '\'>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|0') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|1') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|2') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|3') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|4') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|5') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|6') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|7') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|8') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|9') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|10') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|11') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|12') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|13') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|14') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|15') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|16') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|17') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|18') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|19') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|20') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|21') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|22') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|23') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|24') . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>закон (решение) о бюджете, предусматривающий выделение бюджетных ассигнований из федерального бюджета, бюджета субъекта Российской Федерации и (или) местного бюджета на создание организации инфраструктуры</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|0|26') . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|0|27') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|28') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|29') . '</div></td>
</tr>
';
    for ($i = 1; $i < $rows; $i++) {
      echo '<tr class=\'row_org row_reg_' . $regcode . '\'>';
      if ($i % 3 == 0) {
        $x = round($i / 3);
        echo '
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|9') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|10') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|11') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|12') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|13') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|14') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|15') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|16') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|19') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|20') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|21') . '</div></td>';
      }
      if ($i % $rowsx3 == 0) {
        $x = round($i / $rowsx3);
        $txt = $x == 1
          ? 'государственная (муниципальная) программа (подпрограмма), иная программа развития МСП, предусматривающая создание организации инфраструктуры полностью или частично за счет средств федерального бюджета, бюджетов субъектов Российской Федерации и (или) местных бюджетов'
          : 'закон, иной нормативный правовой акт, устанавливающий требования к организации инфраструктуры либо предусматривающий право организации выполнять функции организаций инфраструктуры';
        echo '
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $txt . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|' . $x . '|26') . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|' . $x . '|27') . '</div></td>';
      }
      echo '</tr>
';
    }

  }

  function getCoordByColName($colname,$_=null){
    if(is_null($_)) $_ = $this->get_s();
    foreach(['_','sub','low'] as $p)
      foreach($_->{$p} as $k=>$v){
        if ($v==$colname){
          return [$p,$k];
        }
    }
    return false;
  }

  /**
   * Обработка "изменения реестра"
   * @param $updates :textarea
   * @param $date
   * @param $debug :radio[0:не менять данные|1:менять]
   */
  function do_test_updates($updates = '', $date = '', $debug=true){
    static $values=[];
    if (empty($date))
      $date = time();
    else
      $date = strtotime($date);
    $csv = csv::csvStr($updates, ['delim' => "\t"]);
    $rows=[];
    while($row=$csv->nextRow()){
      if(empty($row)) continue;
      $code=trim($row[0]);
      if(!isset($values[$code])){
        $values[$code] = $this->get_ByCode($code);
        $values[$code] = $this->get_ByCode($code);
        //print_r($values[$code]);
      }
      $res=&$values[$code];
      $coord=$this->getCoordByColName(trim($row[1]));
      if(false===$coord){
        printf("не найден - %s\n",$row[1]); continue;
      }
      $newval=trim($row[3]);
      $oldval=trim($row[2]);
      if(empty($oldval) && $coord[0]=='sub'){ // новая организация
        //$neworg_row[]
        $x=(int)($res->trcnt/3)+1;
        $found=false;
        for($i=(int)($res->trcnt/3)+1;$i<count($res->values['sub']);$i++){
          if(\UTILS::val($res, 'values|'.$coord[0].'|'.$i.'|'.$coord[1])==''){
            $found=true;
            break;
          }
        }
        if(!$found){
          $res->values['sub'][]=[];
        };
      }
      $found=false;
      for($i=0;$i<count($res->values[$coord[0]]);$i++){
        if($coord[0]!='sub' || preg_replace('/\s+/s','',\UTILS::val($res, 'values|'.$coord[0].'|'.$i.'|'.$coord[1]))==preg_replace('/\s+/s','',$oldval)){
          $res->values[$coord[0]][$i][$coord[1]]=$newval;
          //printf("point to %s|%s|%s\n",$coord[0],$i,$coord[1]);
          $found=true;
          break;
        }
      }
      if(!$found){
        printf("cant't place %s-%s with  %s\n",$coord[0],$coord[1],$newval);
      }
    }
    foreach($values as $code=>$x) {
      $x->values['_'][0][3] = date('d.m.Y', $date);
      $this->result2html($x);
      echo "\n----\n";
    }
    echo count($rows);
  }

  /**
   * Тестировать c&p из Excel
   * @param $code - если указан - изменение, если не указан - добавление нового
   * @param $date
   * @param $a :textarea
   * @param $b :textarea
   */
  function do_test_excel_input($code = '', $date = '', $a='', $b='')
  {
    if (empty($code)) {
      // читаем последнюю запись в файле
      /** @var scaner $scaner */
      $scaner = $this->scaner;
      $res = $scaner
        ->newhandle(self::reghtml)
        ->until('/Создаваемые организации инфраструктуры/ui')
        ->doscan('~<tr class=\'row_org\s+row_reg_(?:(?!<tr).)*?>(1[\d]+)</div>~smi', 1, 'num')
        ->getResult();
      end($res['doscan']);
      $r = current($res['doscan']);
      $result = $this->get_ByCode($r['num']);
    } else {
      $result = $this->get_ByCode($code);
    }
    if (empty($date))
      $date = time();
    else
      $date = strtotime($date);
    //  print_r($result);
    $_ = function ($where) use ($result) {
      return \UTILS::val($result->values, $where);
    };

    $scaner = csv::csvStr($a, ['delim' => "\t"]);
    $r = $scaner->nextRow();
    if (!empty($code)) {
      $inn = $_('_|0|6');
      if (9 == strlen($inn)) $inn = '0' . $inn;
      $regcode = substr($inn, 0, 2);
    } else {
// создание нового
      $inn = \UTILS::val($r, 2);
      if (9 == strlen($inn)) $inn = '0' . $inn;
      $regcode = substr($inn, 0, 2);

      $result->values['_'][0][0] = $_('_|0|0') + 1;
      $result->values['_'][0][1] = '1' . $regcode . str_pad(substr($_('_|0|1'), 3) + 1, 4, "0", STR_PAD_LEFT);
      $result->values['_'][0][2] = date('d.m.Y', $date);
      $result->values['_'][0][3] = '';
      $result->values['_'][0][4] = \UTILS::val($r, 0);
      $result->values['_'][0][5] = \UTILS::val($r, 1);
      $result->values['_'][0][6] = $inn;
      $result->values['_'][0][7] = \UTILS::val($r, 3);
      $result->values['_'][0][8] = '×';
      $result->values['_'][0][17] = \UTILS::val($r, 4);
      $result->values['_'][0][18] = \UTILS::val($r, 5);
      $result->values['_'][0][22] = \UTILS::val($r, 6);
      $result->values['_'][0][23] = \UTILS::val($r, 7);
      $result->values['_'][0][24] = \UTILS::val($r, 8);

      $result->values['low'][0][26] = \UTILS::val($r, 9);
      $result->values['low'][0][27] = \UTILS::val($r, 10);
      $result->values['low'][1][26] = \UTILS::val($r, 11);
      $result->values['low'][1][27] = \UTILS::val($r, 12);
      $result->values['low'][2][26] = \UTILS::val($r, 13);
      $result->values['low'][2][27] = \UTILS::val($r, 14);

      $result->values['_'][0][28] = \UTILS::val($r, 15);
      $result->values['_'][0][29] = \UTILS::val($r, 16);

      $scaner = csv::csvStr(rtrim($b, "\n") . "\n", ['delim' => "\t"]);
      $result->values['sub'] = [];
      while (!empty($r = $scaner->nextRow())) {
        $result->values['sub'][] = [
          9 => \UTILS::val($r, 2),
          10 => \UTILS::val($r, 3),
          11 => \UTILS::val($r, 4),
          12 => \UTILS::val($r, 5),
          13 => \UTILS::val($r, 6),
          14 => \UTILS::val($r, 7),
          15 => \UTILS::val($r, 8),
          16 => \UTILS::val($r, 9),
          19 => \UTILS::val($r, 10),
          20 => \UTILS::val($r, 11),
          21 => \UTILS::val($r, 12),
        ];
      }
    }
    // вариант создания нового документа
    // выводим HTML рыбу
    $rows = max(3, 3 * count($result->values['sub']));
    $rowsx3 = max(1, count($result->values['sub']));
    // первая строка
    echo '<tr class=\'row_org row_reg_' . $regcode . '\'>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|0') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|1') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|2') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|3') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|4') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|5') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|6') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|7') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|8') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|9') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|10') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|11') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|12') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|13') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|14') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|15') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|16') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|17') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|18') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|19') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|20') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|0|21') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|22') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|23') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|24') . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>закон (решение) о бюджете, предусматривающий выделение бюджетных ассигнований из федерального бюджета, бюджета субъекта Российской Федерации и (или) местного бюджета на создание организации инфраструктуры</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|0|26') . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|0|27') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|28') . '</div></td>
<td rowspan=\'' . $rows . '\'><div>' . $_('_|0|29') . '</div></td>
</tr>
';
    for ($i = 1; $i < $rows; $i++) {
      echo '<tr class=\'row_org row_reg_' . $regcode . '\'>';
      if ($i % 3 == 0) {
        $x = round($i / 3);
        echo '
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|9') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|10') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|11') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|12') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|13') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|14') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|15') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|16') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|19') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|20') . '</div></td>
<td rowspan=\'3\'><div>' . $_('sub|' . $x . '|21') . '</div></td>';
      }
      if ($i % $rowsx3 == 0) {
        $x = round($i / $rowsx3);
        $txt = $x == 1
          ? 'государственная (муниципальная) программа (подпрограмма), иная программа развития МСП, предусматривающая создание организации инфраструктуры полностью или частично за счет средств федерального бюджета, бюджетов субъектов Российской Федерации и (или) местных бюджетов'
          : 'закон, иной нормативный правовой акт, устанавливающий требования к организации инфраструктуры либо предусматривающий право организации выполнять функции организаций инфраструктуры';
        echo '
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $txt . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|' . $x . '|26') . '</div></td>
<td rowspan=\'' . $rowsx3 . '\'><div class=\'cell-small\'>' . $_('low|' . $x . '|27') . '</div></td>';
      }
      echo '</tr>
';
    }
    echo json_encode($b, JSON_UNESCAPED_UNICODE);
  }
}
