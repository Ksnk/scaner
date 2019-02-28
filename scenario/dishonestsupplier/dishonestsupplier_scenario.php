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
 * @tags Zakupki
 */
class dishonestsupplier_scenario extends scenario {

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

    function scan_xml($buf,$name){
        echo $name.PHP_EOL;
    }

    function download_zip($filename){
        echo $filename.PHP_EOL;
        $transport=$this->get_transport();
        $lfn=$transport->get($filename);
        if($lfn) {
            if ($zip = zip_open($lfn)) {
                while ($zip_entry = zip_read($zip)) {

                    $name = zip_entry_name($zip_entry);

                    if (zip_entry_open($zip, $zip_entry, "r")) {
                        $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                        $this->scan_xml($buf, $name);
                        zip_entry_close($zip_entry);
                    }
                }
                zip_close($zip);
            }
        }
        unlink($lfn);
        return true;

    }

    /**
     * прочитать список файлов
     */
    function do_scan_dir(){
        // generate item files
        // $this->upload_file('test','items.xml');
        foreach(['unfairSupplier', 'pprf615unfairContractor'] as $dir) {
            $transport = $this->get_transport();
            $raw = $transport->scan($dir);
            $times = array();
            $date = date('Y-m-d-H-i-s');
            foreach ($raw as $item) {
                if ($item['type'] == 1) {
                    printf('file `%s`, time: %s<br>' . PHP_EOL, $item['filename'], date('d-m-Y H:i:y', $item['mtime']));
                    $times[$item['filename']] = $item['mtime'];

                    $this->joblist->append_scenario('download_zip', [$dir.'/'.$item['filename']]);
                    continue 2;
                }
            }
        }
    }
}