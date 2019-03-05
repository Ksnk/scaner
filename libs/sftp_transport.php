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
class sftp_transport {

    private $options,$net_ftp=false;


    public function __construct($opt)
    {
        include_once dirname(__FILE__).'/../libs/PHPSeclib/phpseclib.php';
        $this->options=$opt;
    }

    function get_net_ftp(){
        if(empty($this->net_ftp)){
            $sftp = new Net_SFTP($this->options['host']);
            if (!$sftp->login($this->options['name'],$this->options['password'])) {
                exit('Login Failed');// todo: заменить на возврат сообщения об ошибке
            }
            $this->net_ftp=$sftp;
        }
        return $this->net_ftp;
    }

    function scan($dir=''){
        $sftp=$this->get_net_ftp();
        return $sftp->rawlist($this->options['root'].$dir);
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