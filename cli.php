<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
$baseDir = dirname(__FILE__);
if(preg_match('/\b(\w+\.phar)$/',__FILE__,$m))
    define('INDEX_DIR',"phar://".$m[1]);
else
    define('INDEX_DIR',$baseDir);
//echo $baseDir." ".__FILE__."\n";
include_once INDEX_DIR."/autoload.php";

function __(&$x, $default = '')
{
    return empty($x) ? $default : $x;
}

class action
{

    var $config = array(
        //'log_directory' => '/home/lapsi/data/logs/*.gz',
        'log_directory' => 'scenario/lapsi.log/*.{gz,log}',
        'articul_directory' => 'scenario/evrosvet/*.txt'
    );

    function __get($name){
        switch($name){
            case "joblist":
                return ($this->$name= new joblist());
        }
        return '';
    }

    /**
     * Сканирование логов по IP
     * @param string $log :select[:все|~dir(config.log_directory)] лог для поиска
     * @param string $ip :text IP для поиска
     */
    function do_parse_logs($log, $ip)
    {
        $this->joblist->append_scenario(array('dir'=>'scenario/lapsi.log/','class="grep_logs_scenario','method'=>'scan_access_log'),$log, $ip);
    }

    /**
     * Чтение товаров из Евросети
     * @param string $articul :select[:все|~dir(config.articul_directory)] лог для поиска
     */
    function do_readevroset($articul)
    {
        include_once 'scenario/evrosvet/evroset_scan_scenario.php';
        $obj = new evroset_scan_scenario();
        $obj->initialize(array(
            'archive' => 'archive.zip',
            'excelfile' => 'evrosvet.xlsx',
            'articul_txt' => iconv('utf-8','cp1251',$articul),
            'phpexcel_path'=>'../youlamp/PHPExcel.1.8/',
            'pclzip_path'=>'../youlamp/admin/libs/',
        ));
    }
}

$a = new action();

function parseParameters($noopt = array()) {
    $result = array();
    $params = $GLOBALS['argv'];
    // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
    reset($params);
    while (list($tmp, $p) = each($params)) {
        if ($p{0} == '-') {
            $pname = substr($p, 1);
            $value = true;
            if ($pname{0} == '-') {
                // long-opt (--<param>)
                $pname = substr($pname, 1);
                if (strpos($p, '=') !== false) {
                    // value specified inline (--<param>=<value>)
                    list($pname, $value) = explode('=', substr($p, 2), 2);
                }
            }
            // check if next parameter is a descriptor or a value
            $nextparm = current($params);
            if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
            $result[$pname] = $value;
        } else {
            // param doesn't belong to any option
            $result[] = $p;
        }
    }
    return $result;
}

if (''==ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

if (PHP_SAPI === 'cli'){
    $par=array(
        'iconv.input_encoding'=>'cp1251',
        'iconv.internal_encoding'=>'UTF-8',
        'iconv.output_encoding'=>'UTF-8//ignore',
    );
    if(empty($_ENV) || isset($_ENV['PROMPT']))
        $par['iconv.output_encoding']='cp866//ignore';
    ob_start('ob_iconv_handler');
    foreach($par as $k=>$v)ini_set($k,$v);

    $parameters=parseParameters();
   // print_r($parameters);
    $mask=UTILS::masktoreg('scenario/*/*_scenario.php');
    $result=UTILS::scanPharFile(__FILE__,$mask);
   // print_r($result);

    $scenariofiles=array_merge(
        glob(INDEX_DIR.'/scenario/*/*_scenario.php'),
        $result
    );
   //print_r($scenariofiles);
    try {
        $action = __($parameters[1]);
        list($class,$method,$empty)=explode('::',$action.'::::',3);
        $res=false;$dir='';
        foreach($scenariofiles as $sc){
            $classname=basename($sc,'.php');
            $resx = x_parser::getParameters('', $classname,$sc);
           // print_r($resx);
            if(isset($resx[$class])){
                $dir=$sc;
                $res=$resx;
                break;
            }
        }
        if($res){
          //  print_r($res);
            $top = array('class'=>$class,'method'=>$method,'dir'=>$dir);
            $param=array();

            foreach ($res[$class][$method]['param'] as $name => $par) {
                //$param[] = stripslashes(__($_POST[$action][$name]));
                if(isset($parameters[$name])){
                    $param[] = $parameters[$name];
                } else if(isset($par['default'])) {
                    $param[] = $par['default'];
                } else {
                    echo 'Missing parameter '.$name;
                    exit;
                }
    //            $param[] = __($par[$name],$par['default']);

            }
            $a->joblist->append_scenario($top,$param);
        }

    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        print_r($parameters);
    }

    while($a->joblist->donext()){;}
}
