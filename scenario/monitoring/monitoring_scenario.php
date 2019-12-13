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
 * Работа с сайтом http://www.rfbr.ru/rffi/ru/ - сканирование части разделов, книг и конкурсов
 * Class monitoring_scenario
 *
 * <tr class='row_org row_reg_37'>
 * <td rowspan='3'><div>651</div></td>
 * <td rowspan='3'><div>1370668</div></td>
 * <td rowspan='3'><div>07.06.2018</div></td>
 * <td rowspan='3'><div></div></td>
 *
 * <td rowspan='3'><div>Автономная некоммерческая организация «Центр координации поддержки экспортно ориентированных субъектов малого и среднего предпринимательства Ивановской области»</div></td>
 * <td rowspan='3'><div>АНО «Центр поддержки экспорта Ивановской области»</div></td>
 * <td rowspan='3'><div>3702199512</div></td>
 * <td rowspan='3'><div>1183700000285; 17.05.2018</div></td>
 * <td rowspan='3'><div>×</div></td>
 * <td rowspan='3'><div>-</div></td>
 * <td rowspan='3'><div>Агентство по поддержке экспорта товаров</div></td>
 * <td rowspan='3'><div>Консультационнная</div></td>
 * <td rowspan='3'><div>1. Консультационные услуги с привлечением стронних профильных экспертов по тематике внешнеэкономической деятельности; 2. Проведение семинаров, вебинаров, мастер-классов и других обучающих мероприятий; 3. Создание на иностранном языке или модернизация существующих сайтов экспортно ориентированных субъектов малого и среднего предпринимательства в информационно-телекомуникационной сети "Интернет" на иностранном языке; 4. Организация и проведение международных бизнес-миссий; Организация участия субъектов МСП в выставочно-ярмарочных мероприятиях</div></td>
 * <td rowspan='3'><div>Поддержка оказывается только субъектам МСП осуществляющим или планирующим осуществлять экспортную деятельность зарегистрированным на территории Ивановской области</div></td>
 * <td rowspan='3'><div>Субъект МСП, соответствущий требованиям 209 ФЗ, осуществляющий или планирующий осуществлять экспортную деятельность и зарегистрирован на территории Ивановской области</div></td>
 * <td rowspan='3'><div>1. Не более 3 консультаций для 1 СМСП; 2. Не ограничено; 3. Не более 150 тыс. рублей, не более 1 сайта для 1 СМСП; 4. Не более 1 услуги в течение 1 года</div></td>
 * <td rowspan='3'><div>1, 2,4 Бесплатно; 3. На условиях софинансирования</div></td>
 * <td rowspan='3'><div>153037 г. Иваново, пр-т Шереметьевский, д. 85г помещение 1012 (9)</div></td>
 * <td rowspan='3'><div>153037, Ивановская область, г. Иваново, пр-т Шереметьевский, д. 85г помещение 1012 (9)</div></td>
 * <td rowspan='3'><div>-</div></td>
 * <td rowspan='3'><div>az.export37@gmail.com</div></td>
 * <td rowspan='3'><div>-</div></td>
 * <td rowspan='3'><div>Зиновьева Александра Юрьевна</div></td>
 * <td rowspan='3'><div>-</div></td>
 * <td rowspan='3'><div>az.export37@gmail.com</div></td>
 * <td rowspan='1'><div class='cell-small'>закон (решение) о бюджете, предусматривающий выделение бюджетных ассигнований из федерального бюджета, бюджета субъекта Российской Федерации и (или) местного бюджета на создание организации инфраструктуры</div></td>
 * <td rowspan='1'><div class='cell-small'>Закон Ивановской области от 11.12.2017 № 96-ОЗ "Об областном бюджете на 2018 год и на плановый период 2019 и 2020 годов"</div></td>
 * <td rowspan='1'><div class='cell-small'>приложение № 8</div></td>
 * <td rowspan='3'><div>-</div></td>
 * <td rowspan='3'><div>-</div></td>
 * </tr>
 *
 * <tr class='row_org row_reg_37'>
 * <td rowspan='1'><div class='cell-small'>государственная (муниципальная) программа (подпрограмма), иная программа развития МСП, предусматривающая создание организации инфраструктуры полностью или частично за счет средств федерального бюджета, бюджетов субъектов Российской Федерации и (или) местных бюджетов</div></td>
 * <td rowspan='1'><div class='cell-small'>Постановление Правительства Ивановской области от 13.11.2013 N 459-п "Об утверждении государственной программы Ивановской области "Экономическое развитие и инновационная экономика Ивановской области"</div></td>
 * <td rowspan='1'><div class='cell-small'>Приложение 1</div></td>
 * </tr>
 *
 * <tr class='row_org row_reg_37'>
 * <td rowspan='1'><div class='cell-small'>закон, иной нормативный правовой акт, устанавливающий требования к организации инфраструктуры либо предусматривающий право организации выполнять функции организаций инфраструктуры</div></td>
 * <td rowspan='1'><div class='cell-small'>Приказ Минэкономразвития России от 25.03.2015 N 167 (ред. от 28.11.2016) "Об утверждении условий конкурсного отбора субъектов Российской Федерации, бюджетам которых предоставляются субсидии из федерального бюджета на государственную поддержку малого и среднего предпринимательства, включая крестьянские (фермерские) хозяйства, и требований к организациям, образующим инфраструктуру поддержки субъектов малого и среднего предпринимательства"</div></td>
 * <td rowspan='1'><div class='cell-small'>Раздел III</div></td>
 * </tr>
 *
 * @tags msp
 */
class monitoring_scenario extends scenario
{

    function csvquote($data,$quote='"'){
      if(is_array($data)){
        $result=[];
        foreach($data as $d) $result[]=$this->csvquote($d);
        return $result;
      }
      if(preg_match('/[\s'.preg_quote($quote).']/s',$data)){
        return $quote.str_replace($quote,$quote.$quote,$data).$quote;
      } else
        return $data;
    }

    const reghtml =
        //'d:/projects/monitoring/monitoring.corpmsp.ru/webapps/StartPage/orgreg.html';
        '../data/monitoriing/orgreg.html';
    //   data/monitoriing/orgreg_tpp.html'

    function get_s(){
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

    function get_ByCode($id){
      /** @var scaner $scaner */
      $_ = $this->get_s();

      $scaner = $this->scaner;
      $scaner->newhandle(self::reghtml);
      $scaner
        ->scan('~<tr class=\'row_org\s+row_reg_(?:(?!<tr).)*?>' . $id . '</div>~smi');
      if ($scaner->found) {
        $scaner->position($scaner->reg_begin - 1);
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
              if ($line['name'] == 'rowspan') {
                $rowspan = trim($line['quoted'], '"\'');
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
                    sprintf('%s', $_->cur);
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
     * прочитать страницу с ID="xxx" и вывести информацию
     * @param $id
     */
    function do_edititem($id)
    {
        $result=$this->get_ByCode($id);
        $k=0;
        while(true) {
          if(!isset($result->values['_'][$k])) break;
          echo "\nИмпорт организаций\n";
          echo ''.implode("\t", $this->csvquote([
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
          $j=0;
          while(true){
            if(!isset($result->values['sub'][$j])) break;
          echo ''.implode("\t", $this->csvquote([
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
            ])),"\n";
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
 * @param $a :textarea
 * @param $b :textarea
 */
    function do_test_excel_input($code='',$a,$b){
      if(empty($code)) {
        // читаем последнюю запись в файле
        $scaner = $this->scaner;
        $scaner->newhandle(self::reghtml);
        $scaner
          ->until('/Создаваемые организации инфраструктуры/ui')
          ->doscan('~<tr class=\'row_org\s+row_reg_(?:(?!<tr).)*?>(1[\d]+)</div>~smi', 1, 'num');
        $res = $scaner->getResult();
        end($res['doscan']);
        $r = current($res['doscan']);
        $result=$this->get_ByCode($r['num']);
      } else {
        $result = $this->get_ByCode($code);
      }
    //  print_r($result);

      $fp = fopen("php://memory", 'r+');
      fputs($fp, $a);
      rewind($fp);
      $scaner=csv::getcsv($fp,0);
      $scaner->delim="\t"; // неправильно детектирует
      $r=$scaner->nextRow();

// создание нового
      $inn=$r[2]; if(9==strlen($inn)) $inn='0'.$inn;
      $regcode=substr($inn,0,2);

      $result->values['_'][0][0]=$result->values['_'][0][0]+1;
      $result->values['_'][0][1]='1'.$regcode.str_pad(substr($result->values['_'][0][1],3)+1,4, "0", STR_PAD_LEFT);
      $result->values['_'][0][2]=date('d.m.Y');
      $result->values['_'][0][3]='';
      $result->values['_'][0][4]=$r[0];
      $result->values['_'][0][5]=$r[1];
      $result->values['_'][0][6]=$inn;
      $result->values['_'][0][7]=$r[3];
      $result->values['_'][0][8]='×';
      $result->values['_'][0][17]=$r[4];
      $result->values['_'][0][18]=$r[5];
      $result->values['_'][0][22]=$r[6];
      $result->values['_'][0][23]=$r[7];
      $result->values['_'][0][24]=$r[8];

      $result->values['low'][0][26]=$r[9];
      $result->values['low'][0][27]=$r[10];
      $result->values['low'][1][26]=$r[11];
      $result->values['low'][1][27]=$r[12];
      $result->values['low'][2][26]=$r[13];
      $result->values['low'][2][27]=$r[14];

      $result->values['_'][0][28]=$r[15];
      $result->values['_'][0][29]=$r[16];

      $fp = fopen("php://memory", 'r+');
      fputs($fp, $b);
      rewind($fp);
      $scaner=csv::getcsv($fp,0);
      $scaner->delim="\t"; // неправильно детектирует
      $result->values['sub']=[];
      while(!empty($r=$scaner->nextRow())){
        $result->values['sub'][]=[
          9=>$r[2],
          10=>$r[3],
          11=>$r[4],
          12=>$r[5],
          13=>$r[6],
          14=>$r[7],
          15=>$r[8],
          16=>$r[9],
          19=>$r[10],
          20=>$r[11],
          21=>$r[12],
        ];
      }
      // вариант создания нового документа
      // выводим HTML рыбу
      $rows=3*count($result->values['sub']);
      $rowsx3=count($result->values['sub']);
      // первая строка
      echo '<tr class=\'row_org row_reg_'.$regcode.'\'>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][0].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][1].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][2].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][3].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][4].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][5].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][6].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][7].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][8].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][9].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][10].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][11].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][12].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][13].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][14].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][15].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][16].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][17].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][18].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][19].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][20].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][0][21].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][22].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][23].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][24].'</div></td>
<td rowspan=\''.$rowsx3.'\'><div class=\'cell-small\'>закон (решение) о бюджете, предусматривающий выделение бюджетных ассигнований из федерального бюджета, бюджета субъекта Российской Федерации и (или) местного бюджета на создание организации инфраструктуры</div></td>
<td rowspan=\''.$rowsx3.'\'><div class=\'cell-small\'>'.$result->values['low'][0][26].'</div></td>
<td rowspan=\''.$rowsx3.'\'><div class=\'cell-small\'>'.$result->values['low'][0][27].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][28].'</div></td>
<td rowspan=\''.$rows.'\'><div>'.$result->values['_'][0][29].'</div></td>
</tr>
';
      for($i=1;$i<$rows;$i++){
        echo '<tr class=\'row_org row_reg_'.$regcode.'\'>';
        if($i%3==0){
          $x=round($i/3);
          echo '
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][9].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][10].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][11].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][12].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][13].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][14].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][15].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][16].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][19].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][20].'</div></td>
<td rowspan=\'3\'><div>'.$result->values['sub'][$x][21].'</div></td>';
        }
        if($i%$rowsx3==0){
          $x=round($i/$rowsx3);
          $txt=$x==1
            ?'государственная (муниципальная) программа (подпрограмма), иная программа развития МСП, предусматривающая создание организации инфраструктуры полностью или частично за счет средств федерального бюджета, бюджетов субъектов Российской Федерации и (или) местных бюджетов'
            :'закон, иной нормативный правовой акт, устанавливающий требования к организации инфраструктуры либо предусматривающий право организации выполнять функции организаций инфраструктуры';
          echo '
<td rowspan=\''.$rowsx3.'\'><div class=\'cell-small\'>'.$txt.'</div></td>
<td rowspan=\''.$rowsx3.'\'><div class=\'cell-small\'>'.$result->values['low'][$x][26].'</div></td>
<td rowspan=\''.$rowsx3.'\'><div class=\'cell-small\'>'.$result->values['low'][$x][27].'</div></td>';
        }
        echo '</tr>
';
      }
      echo json_encode($b,JSON_UNESCAPED_UNICODE);
    }
}

//$test= new monitoring_scenario();
//$test->do_edititem('35');