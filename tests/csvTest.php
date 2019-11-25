<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 25.11.2019
 * Time: 21:44
 */

use Ksnk\scaner\csv;

class csvTest extends PHPUnit_Framework_TestCase
{

    function testClassExists(){
        $this->assertNotEmpty(new csv());
    }

    public function testRegtest(){
        $data= '"Программа: ""Предоставление микрозайма субъектам малого и среднего предпринимательства «STARTUP». Срок регистрации субъекта МСП – менее 6 месяцев. Сумма: от 50 000 рублей до 300 000 рублей. Срок до 36 месяцев. Порядок погашения процентов ежемесячно. Порядок погашения основного долга согласно графику. Возможно установление льготного периода, не превышающего срок предоставления микрозайма, за исключением последних 3-х месяцев, в течение которого Заёмщик уплачивает только проценты по Микрозайму. По окончании льготного периода погашение Микрозайма и процентов по нему осуществляется в соответствии с Правилами предоставления Микрозаймов. % ставка – 9,5 %. Цель: - Приобретение основных средств, - Пополнение оборотных средств. Обеспечение: - Поручительство учредителя (-ей), руководителя юридического лица (в случае принятия Комитетом решения о предоставлении поручительства), - Поручительство юридического лица (в случае принятия Комитетом решения о предоставлении поручительства), - Поручительство супруга (-и) индивидуального предпринимателя (в случае принятия Комитетом решения о предоставлении поручительства), - Залог ликвидного имущества на сумму микрозайма и процентов по нему, рассчитанных за период не более 12 месяцев, или Договор страхования риска невозврата займа (в случае принятия Комитетом решения о предоставлении залога или Договора страхования риска невозврата займа). Дополнительные условия (для ИП) Страхование жизни заемщика. ПРОГРАММА: ""Предоставление микрозайма субъектам малого и среднего предпринимательства «Стандарт». Срок регистрации субъекта МСП более 6 месяцев. Сумма от 100 000 рублей до 3 000 000 рублей. Срок до 36 месяцев. Порядок погашения процентов ежемесячно. Порядок погашения основного долга согласно графику. Льготный период единовременно может устанавливаться на срок до 3 (трех) месяцев включительно и предоставляться Заёмщику не более двух раз за каждый год пользования Микрозаймом. По окончании льготного периода погашение Микрозайма и процентов по нему осуществляется в соответствии с Правилами предоставления Микрозаймов. %ставка – 9,5 %. Цель: - Приобретение основных средств, - Пополнение оборотных средств. Обеспечение: - Поручительство учредителя (-ей), руководителя юридического лица (в случае принятия Комитетом решения о предоставлении поручительства), - Поручительство юридического лица (в случае принятия Комитетом решения о предоставлении поручительства), - Поручительство супруга (-и) индивидуального предпринимателя (в случае принятия Комитетом решения о предоставлении поручительства), - Залог ликвидного имущества на сумму микрозайма и процентов по нему, рассчитанных за период не более 12 месяцев, или Договор страхования риска невозврата займа Дополнительные условия (для ИП) Страхование жизни заемщика.";';
        $reg='~((")(?:[^"]|"")*"|.*?)(;|(\r\n|\n|\r))()~su';
        //$reg='~(")(?:[^"]|"")*"~su';
        echo mb_strlen($data,'8bit');
        preg_match($reg, $data, $m);
        print_r($m);
    }

    public function testGetcsv()
    {
        $csv=csv::getcsv('data/data-20191112-structure-20171024.csv');
//$csv=csv::getcsv('test/xcraft.txt');
//$csv=csv::getcsv('test/xcraft.3.txt'); utf-16LE
        $cnt=0;
        while($row=$csv->nextRow()){
            //print_r($row);
            if($cnt%100==0) {
            echo $cnt . ' ';
            print_r($row);
            }
            $cnt++;
        }
        echo $cnt;
    }
}
