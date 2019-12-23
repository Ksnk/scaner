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
        ->until()
        ->scan('~<\!\-\-|<tr class=["\']row_org\s+row_reg_(?:(?!<t).)*?<t(?:(?!<t).)*?div>\s*(\d+?\s*)<~smi', 1, 'num');
    }
    if ($scaner->found) {
      $pos=$scaner->reg_begin;
      $res = $scaner->getresult();
      if (empty($res))
        return $this->get_ByCode();
      $scaner
          ->until('~<tr class=["\']row_org\s+row_reg_(?:(?!<t).)*?<t(?:(?!<t).)*?div>\s*(\d+?\s*)<~smi');
      $scaner->position($pos - 1);
      $scaner->syntax([
        'attr' => '\s*:name:\s*=\s*:quoted:',
        'name' => '\w+',
        'body' => '.*?',
        'quoted' => '(?:"[^"]*"|\'[^\']*\'|[^\'"]*)',
        'tag' => 'tr|td|div',
        'open' => '<:tag:(?::attr:|)>',
        'close' => '</:tag:>',
      ], '~:open:|:close:~sm',
        function ($line) use (&$_) {
          //echo htmlspecialchars(print_r($line, true));

          if (\UTILS::val($line, 'open') != '' && $line['tag'] == 'tr') {
            $_->tdcnt = 0;
            if (count($_->struct) <= $_->trcnt) $_->struct[] = [];
            //ksort($_->struct)[$_->trcnt]);
            $_->colcnt = 0;
          }
          if (\UTILS::val($line, 'open') != '' && $line['tag'] == 'td') {

            if (count($_->struct[0]) > 0)
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
            if ($_->rowcnt <= 0) return false;
          }
          return true;
        });
      return $_;

    } else {
      return null;
    }
  }

  /**
   *  перенумеровать все номера
   */
  function do_renumber($date = '')
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
    echo "\nDone.";
  }

  /**
   * Импортировать все в CSV
   * @param $date - Дата внесения правок
   */
  function do_importdata($date='')
  {
    $date=empty($date)?time():strtotime($date);
    $this->scaner->newhandle(self::reghtml);
    $this->scaner->until('/Создаваемые организации инфраструктуры/ui');
    $result=null;
    $_ = function ($where) use (&$result) {
      return \UTILS::val($result,'values|'.$where);
    };
    $fh=fopen('data-'.date('Ymd',$date).'-structure-20171024.csv','w+');
    fwrite($fh,csv::BOM);
    fputcsv($fh,['rec_no','registry_rec_no','registry_rec_created','registry_rec_modified','org_name_full','org_name_short','org_inn','org_reg_no_and_date','org_planned_creation_date','org_dept','org_type','support_form','support_services','support_conditions','support_requirements','support_amount','support_cost','org_address_egrul','org_address','org_phone_number','org_email','org_web_site','org_head_name','org_head_phone_number','org_head_email','budget_act','budget_act_ref','msp_program','msp_program_ref','legal_act','legal_act_ref','org_cert','org_cert_issuer'],';');
    $cnt=0;
    while($result=$this->get_ByCode()){
      if(!isset($result->values['sub'])){
        $result->values['sub']=[[]];
      }
      for( $i =0; $i<count($result->values['sub']);$i++ ) {
        if(strlen(trim($_('_|0|8')))>3) break 2;
        fputcsv($fh, [++$cnt,// rec_no
          $_('_|0|1'), // registry_rec_no
          $_('_|0|2'), // registry_rec_created
          $_('_|0|3'), // registry_rec_modified
          $_('_|0|4'), // org_name_full
          $_('_|0|5'), // org_name_short
          $_('_|0|6'), // org_inn
          $_('_|0|7'), // org_reg_no_and_date
          $_('_|0|8'), // org_planned_creation_date

          $_('sub|'.$i.'|9'), // org_dept
          $_('sub|'.$i.'|10'), // огрн
          $_('sub|'.$i.'|11'), // огрн
          $_('sub|'.$i.'|12'), // огрн
          $_('sub|'.$i.'|13'), // огрн
          $_('sub|'.$i.'|14'), // огрн
          $_('sub|'.$i.'|15'), // огрн
          $_('sub|'.$i.'|16'), // огрн
          $_('_|0|17'), // адрес 1
          $_('_|0|18'), // inn
          $_('sub|'.$i.'|19'), // огрн
          $_('sub|'.$i.'|20'), // огрн
          $_('sub|'.$i.'|21'), // огрн
          $_('_|0|22'), // inn
          $_('_|0|23'), // inn
          $_('_|0|24'), // inn

          $_('low|0|26'), // inn
          $_('low|0|27'), // inn
          $_('low|1|26'), // inn
          $_('low|1|27'), // inn
          $_('low|2|26'), // inn
          $_('low|2|27'), // inn

          $_('_|0|28'), // inn
          $_('_|0|29') // inn
        ], ';');
      }
    }
    fclose($fh);
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

  /**
   * Тестировать c&p из Excel
   * @param $code - если указан - изменение, если не указан - добавление нового
   * @param $date
   * @param $a :textarea
   * @param $b :textarea
   */
  function do_test_excel_input($code = '', $date = '', $a, $b)
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
