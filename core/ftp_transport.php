<?php
/**
 * Created by PhpStorm.
 * Транспорт ftp для качки-закачки файлов по ftp.
 * Доступ к транспорту идет по URI.
 */
namespace Ksnk\scaner;
/**
 * Class ftp_transport - транспорт для общения по ftp
 */
class ftp_transport extends base{

    protected
        $mode='',
        $conn_id,
        $uri='',
        $ftp_root='',
        $tmp_dir='',
   // public
        /** difference between local time() and remote filemtime */
        $timediff=0;

    public function __construct($opt=[])
    {
        if(is_string($opt)) $opt=array('uri'=>$opt);
        parent::__construct($opt);
        if(empty($this->tmp_dir)){
            $this->tmp_dir=realpath(dirname(__FILE__).'/../data');
        }
    }

    function login(){
        preg_match("/^\s*(s?ftp):\/\/(?:(.*?):)?(?:(.*?)@)?(.*?)(\/.*)/i", $this->uri, $match);
        if($match[1]=='sftp'){
            $this->mode='sftp';
            $this->conn_id = ssh2_connect($match[4], 22);
            ssh2_auth_password($this->conn_id, $match[2], $match[3]);
            $this->ftp_root= $match[5];
            if(!empty($this->ftp_root) && !preg_match('~/$~', $this->ftp_root))
                $this->ftp_root.='/';
            return $this->conn_id;
        } else {
            $this->mode='ftp';
            if ($this->conn_id = ftp_connect($match[4])) {
                if (ftp_login($this->conn_id, $match[2], $match[3])) {
                    // Change the dir
                    ftp_chdir($this->conn_id, $match[5]);

                    // Return the resource
                    return $this->conn_id;
                }
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
        switch($this->mode){
            case 'sftp':
                $com ="date +%m/%d/%Y%t%T";
                $stream = ssh2_exec($conn_id, $com );
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                $this->timediff=time()-strtotime(trim($output));

                echo $output.PHP_EOL . $this->timediff.PHP_EOL;

                $com ="ls -lt ".$this->ftp_root.$dir;
                $stream = ssh2_exec($conn_id, $com );
                stream_set_blocking($stream, true);
                $output = stream_get_contents($stream);
                $lines=explode("\n",$output);
                break;

            case 'ftp':
                $lines=ftp_rawlist($conn_id, $dir);
                break;
        }

        if(empty($lines)) return $result;
        foreach($lines as $l){
            if(preg_match('/^([^\s]+)\s+(.)\s+([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+(.+?)\s+([^\s]+)$/',$l, $m)) {
                $result[] =
                    [
                        'type' => $m[2],
                        'filename' => $m[7],
                        'mtime' => strtotime($m[6]),
                        //'date' =>date('Y-m-d H:i:s', strtotime($m[6])),
                        'raw' =>$l,
                        'size' => $m[5]
                    ];
            }
        }
        return $result;
    }

    /**
     * получение файла по ftp
     * @param $filename
     * @param $destination
     * @return string
     */
    function get($filename,$destination='', $timestamp=0){
        $sftp=$this->get_net_ftp();
        $p=pathinfo($filename);
        if(preg_match('~[\\/]$~', $destination)){
            $dir=$destination;
            $destination='';
        } else {
            $dir = $this->tmp_dir;
        }
        $ln=empty($destination)?$dir.'/'.$p['basename']:$destination;
        switch($this->mode){
            case 'ftp':
                if(!ftp_get ( $sftp , $ln, $filename, FTP_BINARY ))
                    return '';
                break;
            case 'sftp':
                if(!ssh2_scp_recv ( $sftp,$this->ftp_root.$filename,$ln))
                    return '';
                if($timestamp>0) {
                    touch($ln, $timestamp + $this->timediff);
                }
                break;
        }

        return $ln;
    }
}

/**

//error_reporting(0);

$file        ='';
$arr2        ='';
$arr1        ='';
$ldir         = '';

$remotedirs[0]     = "Domain name or IP address/";
$remotedirs[1]     = "Domain name or IP address/";
$remotedirs[2]     = "Domain name or IP address/";
$remotedirs[3]     = "Domain name or IP address/";

$fh = fopen("c:\dirlist.txt","w");
fwrite($fh, " ");
fclose($fh);
foreach ($remotedirs as $val){
echo $remotedir = "/data/www/$val/";
$localdir     = "\\\\192.168.0.234\\C$\\xampp\\htdocs\\$val\\";
backupwebsites($remotedir,$localdir);
}
function backupwebsites($remotedir,$localdir){
$connection = ssh2_connect(Host IP or Domain, 22);
$com ="ls -R -lt $remotedir";
ssh2_auth_password($connection, 'user', 'password');
$stream = ssh2_exec($connection, $com );
stream_set_blocking($stream, true);
$output = stream_get_contents($stream);

$fh = fopen("c:\dirlist.txt","a+");
fwrite($fh, $output);
fclose($fh);
$handle = @fopen('c:\dirlist.txt', "r");
if ($handle) {
while (!feof($handle)) {
$lines[] = fgets($handle, 4096);
}
fclose($handle);
}
foreach ($lines as $val)
{
$yr = date('Y-m-d');
$i++;
$arr1=split("200",$val);
$arr2=explode(" ",$arr1[1]);
if("200".$arr2[0]==$yr)
{
//if("200".$arr2[0]=='2008-04-21'){    //for testing
$remotedir = $remotedir.$arr2[2];
$cpy=$arr2[2];
$file = $localdir;
glue($connection,$remotedir,$localdir,$cpy);
}
}
}
//echo $i;
function glue($connection,$remotedir,$localdir,$cpy){
$ldir[0] = "$localdir";
$ldir[1]="$cpy";
$file = $ldir[0].$ldir[1];
$file = trim($file);
$file;
gop($connection,$remotedir,$file);
}
function gop($connection,$remotedir,$file){
echo $file;

ssh2_scp_recv(
$connection,
$remotedir,
$file);
}

 */