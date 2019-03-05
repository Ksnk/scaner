<?php
/**
 * Created by PhpStorm.
 * Транспорт ftp для качки-закачки файлов по ftp.
 * Доступ к транспорту идет по URI.
 * todo: добавить sftp и ftp over ssl, так как они тоже хорошо цепляются по URI
 */
namespace Ksnk\scaner;
/**
 * Class ftp_transport - транспорт для общения по ftp
 */
class ftp_transport {

    private
        $conn_id,
        $options;

    public function __construct($opt)
    {
        $this->options=$opt;
    }

    function login(){
        preg_match("/(s?ftp):\/\/(?:(.*?):)?(?:(.*?)@)?(.*?)(\/.*)/i", $this->options['uri'], $match);
        if($this->conn_id = ftp_connect($match[4])) {
            if (ftp_login($this->conn_id, $match[2], $match[3])) {
                // Change the dir
                ftp_chdir($this->conn_id, $match[5]);

                // Return the resource
                return $this->conn_id;
            }
        }
        return false;
    }

    function get_net_ftp(){
        if(empty($this->conn_id)){
            $this->login();
        }
        return $this->conn_id;
    }

    /**
     * получение списка файлов из стандартного вывода Unix
     * @param string $dir
     * @return array
     */
    function scan($dir='.'){
        $conn_id=$this->get_net_ftp();
        $result=[];
        $lines=ftp_rawlist($conn_id, $dir);
        if(empty($lines)) return $result;
        foreach($lines as $l){
            preg_match('/^([^\s]+)\s+(.)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+(.+?)\s+([^\s]+)$/',$l, $m);
            $result[]=
                [
                    'type'=>$m[2],
                    'filename'=>$m[7],
                    'mtime'=>strtotime($m[6]),
                    'size'=>$m[5]
                ];
        }
        return $result;
    }

    /**
     * получение файла по ftp
     * @param $filename
     * @param $destination
     * @return string
     */
    function get($filename,$destination=''){
        $sftp=$this->get_net_ftp();
        $p=pathinfo($filename);
        $ln=empty($destination)?dirname(__FILE__).'/'.$p['basename']:$destination;
        if(!ftp_get ( $sftp , $ln, $filename, FTP_BINARY )) return '';
        return $ln;
    }
}