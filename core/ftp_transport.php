<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 03.02.16
 * Time: 18:15
 */

/**
 * Class sftp_transport - транспорт для общения по sftp
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
        preg_match("/ftp:\/\/(?:(.*?):)?(?:(.*?)@)?(.*?)(\/.*)/i", $this->options['uri'], $match);
        $this->conn_id = ftp_connect($match[3]);
        //or die("Не удалось установить соединение с $ftp_server");
        if(!$this->conn_id) return false;

        if (ftp_login($this->conn_id, $match[1], $match[2]))
        {
            // Change the dir
            ftp_chdir($this->conn_id, $match[4]);

            // Return the resource
            return $this->conn_id;
        }
    }

    function get_net_ftp(){
        if(empty($this->conn_id)){
            $this->login();
        }
        return $this->conn_id;
    }

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
     * загрузка рессурса по sftp
     * @param $contents
     * @param $destination
     * @param bool $is_filename
     */
    function upload($contents,$destination,$is_filename=false){
        $sftp=$this->get_net_ftp();

// puts a three-byte file named filename.remote on the SFTP server
        if($is_filename){
            $sftp->put($this->options['root'].$destination,$contents, NET_SFTP_LOCAL_FILE);
        } else {
            $sftp->put($this->options['root'].$destination, $contents);
        }
// puts an x-byte file named filename.remote on the SFTP server,
// where x is the size of filename.local
//       $sftp->put('/ecommerce/test4.txt', dirname(__FILE__).'/test.txt', NET_SFTP_LOCAL_FILE);

    }
}