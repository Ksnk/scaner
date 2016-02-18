<?php
/**
 * CLI клиент для запуска из консоли
 */

include_once "../autoload.php";

function __(&$x, $default = '')
{
    return empty($x) ? $default : $x;
}

function glob_recursive($pattern, $flags = 0){
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern).'/*',
        GLOB_ONLYDIR|GLOB_NOSORT) as $dir){
        $files = array_merge($files, glob_recursive
        ($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

class action
{

    var $config = array(
        //'log_directory' => '/home/lapsi/data/logs/*.gz',
        'log_directory' => '../scenario/lapsi.log/*.{gz,log}',
        'articul_directory' => '../scenario/evrosvet/*.txt'
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
        $this->joblist->append_scenario(array('dir'=>'../scenario/lapsi.log/','class="grep_logs_scenario','method'=>'scan_access_log'),$log, $ip);
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

session_start();
$a = new action();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['form'] = $_POST;
    ob_start();
    try {
        $action = __($_POST['action']);
        list($class,$method,$empty)=explode('::',$action.'::',3);
        $top = array('class'=>$class,'method'=>$method,'dir'=>realpath(__($_POST[$action]['_'])));
        // var_dump($top);var_dump($top['dir']);
        $res = x_parser::getParameters('', $class,$top['dir']);
        $param=array();
        foreach ($res[$class][$method]['param'] as $name => $par) {
            //$param[] = stripslashes(__($_POST[$action][$name]));
            $param[] = __($_POST[$action][$name]);
        }

        $a->joblist->append_scenario($top,$param);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        print_r($_POST);
    }

    $debug= ob_get_contents();
    ob_end_clean();
    $par = 'http://' . $_SERVER["HTTP_HOST"];
    if (!empty($direct)) {
        $par .= $direct;
    } else {
        $par .= $_SERVER["REQUEST_URI"];
    }

    if (empty($debug) && !headers_sent()) {
        header("location:" . $par);
    } else
        echo "Please, press <a href='$par'>$par</a> to redirect.";

    exit;
}
header('Content-type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>Комплект утилит</title>
    <link rel="stylesheet" type="text/css" href="webclient.css" />
</head>
<body>

<pre><?php
    while($a->joblist->donext()){;}
    // print_r($_SESSION);
    // var_export($a->joblist->list);
    $scenariofiles=glob('../scenario/*/*_scenario.php');
    ?>
</pre>

<form action="" method="POST">
    <table>
        <tr>
            <th>action</th>
            <th>parameters</th>
        </tr>
        <?php
        foreach($scenariofiles as $sc){
            $classname=basename($sc,'.php');
            $res = x_parser::getParameters('', $classname,realpath($sc));
            if(empty($res) || empty($res[$classname])) continue;
            foreach ($res[$classname] as $method => $val) {

                if (!preg_match('/^do_(.*)$/', $method)) continue;
                echo '  <tr><th><button name="action" value="' . htmlspecialchars($classname.'::'.$method) . '">' . $val['title'] . '</button></th><td><input type="hidden" name="'.htmlspecialchars($classname.'::'.$method) . '[_]" value="'.htmlspecialchars($sc).'">';

                //  echo '<!--xxx-- '.print_r($val,true).' -->';

                foreach ($val['param'] as $name => $par) {
                    echo x_parser::createInput($par, __($_SESSION['form'], array()));
                }
                echo '</td></tr>';
            }
        }
        ?>
    </table>
</form>
</body>
</html>