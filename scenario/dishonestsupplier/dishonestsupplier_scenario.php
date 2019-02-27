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

    /**
     * Загрузка файла на сайт стокке.ком в специальное место
     * @param $contents
     * @param $contents
     * @param $name
     */
    function upload_file($contents,$name,$is_filename=false){
        $transport=$this->get_transport();
        if($transport){
            $transport->upload($contents,$name,$is_filename);
        }
    }

    function upload_zip($filename){
        echo $filename.PHP_EOL;
        $transport=$this->get_transport();
        $transport->get($filename);
//        function receivefile($filename,$dir='tmp'){
/*
            $src = fopen("php://input", 'r');
            if(!is_dir($dir))
            {
                mkdir($dir, 0755, true);
            }
            $dest = fopen($dir.'/'.$filename, 'wb');
            stream_copy_to_stream($src, $dest);// . " байт скопировано в first1k.txt\n";
            fclose($dest);
            $result=true;
            if (substr($filename,-4)=='.zip'){
                $zip = zip_open($dir.'/'.$filename);

                $dir.='/'.basename($filename,'.zip');
                $files = 0;
                $folders = 0;

                if ($zip) {
                    while ($zip_entry = zip_read($zip)) {

                        $name = zip_entry_name($zip_entry);

                        $path_parts = pathinfo($name);
                        # Создем отсутствующие директории
                        if(!is_dir($dir.'/'.$path_parts['dirname']))
                        {
                            mkdir($dir.'/'.$path_parts['dirname'], 0755, true);
                        }

                        //$log->addEntry(array('comment' => 'unzip 2 ' . $name));
                        if (zip_entry_open($zip, $zip_entry, "r")) {
                            $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

                            $file = fopen($dir.'/'.$name, "wb");
                            if ($file) {
                                fwrite($file, $buf);
                                fclose($file);
                                $this->gotafile($dir.'/'.$name);
                                $files++;
                            } else {
                                $result=false;
                                //$log->addEntry(array('comment' => 'error unzipopen file '.$name));
                            }
                            zip_entry_close($zip_entry);
                        }
                    }
                    zip_close($zip);
                } else {
                    // error
                    $result=false;
                }

            } else {
                $this->gotafile($dir.'/'.$filename);
            }
            return $result;
//        }
*/
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

                    $this->joblist->append_scenario('upload_zip', [$dir.$item['filename']]);
                    continue 2;
                }
            }
        }
    }
}