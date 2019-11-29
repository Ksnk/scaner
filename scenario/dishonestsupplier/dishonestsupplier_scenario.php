<?php
/**
 * Created by PhpStorm.
 * пример парсинга xml с помощью syntax
 * пример использования ftp_transport
 */

namespace Ksnk\scaner;

/**
 * @property spider spider
 * @tags Zakupki
 */
class dishonestsupplier_scenario extends scenario {

    /** @var resource */
    var    $csv_handle;
    static $single=null;

    static function get($par){
        if (!self::$single) {
            self::$single = new self($par);
           // self::$single->scaner= new spider(); // не нужно инициировать, если нет параметров ?
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
            ));
        }
        return $tr;
    }

    function scan_xml($buf,$name, $filename){
        echo $name.' '.$filename.PHP_EOL;
        $this->scaner->newbuf($buf);
        $this->scaner
            ->ifscan('~reason>(.+?)<~i',1,'reson')
            ->scan('/unfairSupplier/')->until('/unfairSupplier/');
        $res=$this->scaner->getresult();
        $res['_opens']=0;
        $this->scaner->syntax([
            'ns' =>'[^//>]*:|',
            'close' =>'/?',
            'tag' => '\w+',
        ], '~<:close:?:ns::tag:>(?<fin>)~sm',
            function ($line) use (&$res) {
                //echo htmlspecialchars(print_r($line, true));
                if(empty($line['close'])) {
                    $res['_opens']++;
                    return true;
                }
                $res['_opens']--;
                if($res['_opens']>0) return true;
                switch($line['tag']){
                    case 'fullName':
                        $res['name']=trim(htmlspecialchars_decode($line['_skiped']));
                        break;
                    case 'type':
                        $res['type']=trim(htmlspecialchars_decode($line['_skiped']));
                        break;
                    case 'inn':
                        if(preg_match('/(\d{9,12})/',$line['_skiped'],$m )){
                            if(strlen($m[1])==9 || strlen($m[1])==11) $m[1]='0'.$m[1];
                            $res[$line['tag']]=$m[1];
                        };
                        break;
                    case 'taxPayerCode':
                        if(empty($res['inn']))
                            $res['inn']=trim(htmlspecialchars_decode($line['_skiped']));
                        break;
                }

                return true;
            });
        if(isset($res['reson'])){
            if($res['reson']=='CANCEL_CONTRACT'){
                $res['reson']="Расторжение контракта";
            } else if($res['reson']=='RESPONSIBLE_DECISION_CANCEL_CONTRACT') {
                $res['reson']="Расторжение контракта";
            } else if($res['reson']=='PARTICIPANT_DEVIATION_IF_WINNER_DEVIATION') {
                $res['reson']="Уклонение победителя от заключения контракта";
            } else if($res['reson']=='ONE_WINNER_DEVIATION') {
                $res['reson']="Уклонение победителя от заключения контракта";
            } else if($res['reson']=='WINNER_DEVIATION') {
                $res['reson']="Уклонение победителя от заключения контракта";
            }
        }
        if(!empty($res['inn'])) {
            if (!isset($res['inn']) || !isset($res['type']) || !isset($res['name']) || !isset($res['reson'])) {
                echo ' some variable absent'.PHP_EOL;
//                die(0);
            }
            fputcsv($this->csv_handle, [$res['inn'], $res['type'], $res['name'], $res['reson'], $name . ' ' . $filename]);
        } else {
            echo ' INN missed'.PHP_EOL;
        }
        echo var_export($res,true).PHP_EOL;
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
            $regmask=\UTILS::masktoreg($mask);
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
        fwrite($this->csv_handle,"\xEF\xBB\xBF");
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
     * @param file $zipfile :select[~dir(../../core/*.zip)]  имя файла для обработки
     * @param $name
     */
    function do_test($zipfile, $name)
    {
        echo $zipfile.PHP_EOL;
        $this->open_zip($zipfile, ' ', $name);
    }

    /**
     * загрузить файл
     * @param $ftpfile
     */
    function do_uploadtest($ftpfile)
    {
        $transport=$this->get_transport();
        echo $transport->get($ftpfile).PHP_EOL;
    }

}