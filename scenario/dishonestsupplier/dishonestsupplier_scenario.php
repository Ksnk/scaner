<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 04.02.16
 * Time: 12:41
 */

/**
 * загрузка файлов на стокке.ком и проверка наличия новых
 * Class sqlfiddle_scenario
 * @property spider spider
 * @tags Zakupki
 */
class dishonestsupplier_scenario extends scenario {
    /** @var spider  */
    var $spider,
    /** @var resource */
        $csv_handle;
    static $single=null;

    static function get($par){
        if (!self::$single) {
            self::$single = new self($par);
            self::$single->spider= new spider();
            self::$single->csv_handle = fopen('file.csv', 'a+');
        }
        return self::$single;
    }
    /**
     * @param bool $create
     * @return bool|ftp_transport
     */
    function get_transport($create=true){
        static $tr=false;
        if(empty($tr) && $create){
            $tr= new ftp_transport(array(
                'uri'=>'ftp://free:free@ftp.zakupki.gov.ru/fcs_fas/'
                // ftp://free@ftp.zakupki.gov.ru/fcs_fas/unfairSupplier
                // ftp://free@ftp.zakupki.gov.ru/fcs_fas/pprf615unfairContractor
            ));
        }
        return $tr;
    }

    function scan_xml($buf,$name, $filename){
        echo $name.' '.$filename.PHP_EOL;
        $this->spider->newbuf($buf);
        $res=[];
        $this->spider
            ->ifscan('~reason>(.+?)<~i',1,'reson')
            ->scan('/unfairSupplier/')->until('/unfairSupplier/');
        $scan=[
            'type'=>'~type>(.*?)</[^>]*type~i',
            'name'=>'~firmName>(.*?)<~i',
            'name2'=>'~fullName>(.*?)<~i',
            'inn'=>'~(?:taxPayerCode|inn)>\s*(.*?)\s*<~i'
        ];
        $pos=$this->spider->getpos();
        foreach($scan as $k=>$v){
            $this->spider->scan($v,1,$k);
            if(!$this->spider->found) {
                $this->spider->position($pos);
                $this->spider->scan($v, 1, $k);
            }
        }
            /**
             * <ns3:unfairSupplierInfo>
            <ns3:type>U</ns3:type>
            <ns3:fullName>ООО Группа Компаний "Три кита"</ns3:fullName>
            <ns3:taxPayerCode>5262261009</ns3:taxPayerCode>
            <ns3:kpp>526201001</ns3:kpp>
            - <ns3:foundersInfo>
            - <ns3:founderInfo>
            <ns3:names>Купричев Денис Сергеевич</ns3:names>
            <ns3:taxPayerCode>526310060952</ns3:taxPayerCode>
            </ns3:founderInfo>
            </ns3:foundersInfo>
            - <ns3:place>
            <ns3:deliveryPlace>603057, Нижегородская обл., г. Нижний Новгород, пр-т Гагарина, д. 27, офис 611</ns3:deliveryPlace>
            </ns3:place>
            </ns3:unfairSupplierInfo>
--------- <oos:reason>CANCEL_CONTRACT</oos:reason>
             * <?xml version="1.0" encoding="UTF-8"?><export xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://zakupki.gov.ru/oos/export/1" xmlns:oos="http://zakupki.gov.ru/oos/types/1"><unfairSupplier schemeVersion="5.0"><oos:registryNum>РНП.24120-15</oos:registryNum><oos:publishDate>2015-01-13T00:00:00Z</oos:publishDate><oos:approveDate>2015-01-13T00:00:00Z</oos:approveDate><oos:state>PUBLISHED</oos:state><oos:publishOrg><oos:regNum>01021000042</oos:regNum><oos:fullName>Управление Федеральной антимонопольной службы по Республике Бурятия</oos:fullName></oos:publishOrg>
             * <oos:reason>WINNER_DEVIATION</oos:reason>
             * <oos:unfairSupplier><oos:fullName>Общество с ограниченной ответственностью &quot;Пилот Групп&quot;</oos:fullName>
             * <oos:type>U</oos:type><oos:firmName>Общество с ограниченной ответственностью &quot;Пилот Групп&quot;</oos:firmName>
             * <oos:inn>0326497652</oos:inn><oos:kpp>032601001</oos:kpp><oos:place><oos:kladr><oos:kladrCode>030000010000341</oos:kladrCode><oos:fullName>Пушкина ул</oos:fullName><oos:subjectRF>Бурятия Респ</oos:subjectRF><oos:area>Улан-Удэ г</oos:area><oos:street>Пушкина</oos:street><oos:building>16</oos:building><oos:office>19</oos:office></oos:kladr><oos:zip>670024</oos:zip><oos:place></oos:place><oos:email></oos:email></oos:place></oos:unfairSupplier><oos:purchase><oos:purchaseNumber>0102200001612002153</oos:purchaseNumber><oos:purchaseObjectInfo>Поставка расходного материала для проведения коронарного стентирования для оказания высокотехнологичной медицинской помощи</oos:purchaseObjectInfo><oos:placingWayName>Открытый аукцион в электронной форме</oos:placingWayName><oos:protocolDate>2012-12-14</oos:protocolDate><oos:lotNumber>1</oos:lotNumber><oos:document><oos:date>2012-12-14</oos:date></oos:document></oos:purchase><oos:contract><oos:productInfo>Поставка расходного материала для проведения коронарного стентирования для оказания высокотехнологичной медицинской помощи</oos:productInfo><oos:signDate></oos:signDate><oos:currency><oos:code>RUB</oos:code><oos:name>Российский рубль</oos:name></oos:currency><oos:price>2479167.96</oos:price></oos:contract></unfairSupplier></export>
             */
        ;
        $result=$this->spider->getResult();

        if(!isset($result['name']) && isset($result['name2']))$result['name']=$result['name2'];
        if(isset($result['name']))$result['name']=htmlspecialchars_decode($result['name']);
        if(isset($result['reson'])){
            if($result['reson']=='CANCEL_CONTRACT'){
                $result['reson']="Расторжение контракта";
            } else if($result['reson']=='WINNER_DEVIATION') {
                $result['reson']="Уклонение победителя от заключения контракта";
            }
        }
        if(!isset($result['inn']) || !isset($result['type']) || !isset($result['name']) || !isset($result['reson'])){
            echo ' some variable absent';
            die(0);
        }
        fputcsv($this->csv_handle,[$result['inn'],$result['type'], $result['name'],$result['reson'],$name.' '.$filename]);
        echo var_export($result,true).PHP_EOL;
    }

    function download_zip($filename){
        echo $filename.PHP_EOL;
        $transport=$this->get_transport();
        $lfn=$transport->get($filename);
        if($lfn) {
            $this->open_zip($lfn,$filename);
        }
        unlink($lfn);
        return true;
    }

    function open_zip($lfn, $debug, $mask='*.xml'){
        if($lfn) {
            $regmask=UTILS::masktoreg($mask);
            if ($zip = zip_open($lfn)) {
                while ($zip_entry = zip_read($zip)) {

                    $name = zip_entry_name($zip_entry);
                    if(preg_match($regmask,$name)) {
                        if (zip_entry_open($zip, $zip_entry, "r")) {
                            $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                            $this->scan_xml($buf, $name, $debug);
                            zip_entry_close($zip_entry);
                        }
                    }
                }
                zip_close($zip);
            }
        }
    }

    /**
     * прочитать список файлов
     */
    function do_scan_dir(){
        ftruncate($this->csv_handle,0);
        fputcsv($this->csv_handle,['inn','type', 'name','reason','where']);

        // generate item files
        // $this->upload_file('test','items.xml');
        foreach(['unfairSupplier', 'pprf615unfairContractor'] as $dir) {
            $cnt=2;
            $transport = $this->get_transport();
            $raw = $transport->scan($dir);
            $times = array();
            foreach ($raw as $item) {
                if ($item['type'] == 1) {
                    printf('file `%s`, time: %s<br>' . PHP_EOL, $item['filename'], date('d-m-Y H:i:y', $item['mtime']));
                    $times[$item['filename']] = $item['mtime'];

                    $this->joblist->append_scenario('download_zip', [$dir.'/'.$item['filename']]);
                   // if($cnt--<=0)continue 2;
                }
            }
        }
    }

    /**
     * Тестировать
     * @param file $zipfile :select[~dir(../../webclient/*.zip)]  имя файла для обработки
     * @param $name
     */

    function do_test($zipfile, $name)
    {
        $this->open_zip($zipfile, ' ', $name);
    }

}