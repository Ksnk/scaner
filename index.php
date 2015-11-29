<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once "autoload.php";

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

    /**
     * Сканирование логов по IP
     * @param string $log :select[:все|~dir(config.log_directory)] лог для поиска
     * @param string $ip :text IP для поиска
     */
    function do_parse_logs($log, $ip)
    {
        include_once 'scenario/lapsi.log/grep_logs_scenario.php';
        $scaner = new grep_logs_scenario();
        $scaner->initialize();
        $scaner->scan_access_log($log, $ip);
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['form'] = $_POST;
    ob_start();
    try {
        $res = x_parser::getParameters('', 'action');
        $action = __($_POST['action']);

        $a = new action();
        //ENGINE::debug($_POST);
        if (method_exists($a, $action)) {
            $param = array();
            foreach ($res['action'][$action]['param'] as $name => $par) {
                $param[] = __($_POST[$action][$name]);
            }
            call_user_func_array(array($a, $action), $param);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        print_r($_POST);
    }

    $_SESSION['x.result'] = ob_get_contents();
    ob_end_clean();
    $par = 'http://' . $_SERVER["HTTP_HOST"];
    if (!empty($direct)) {
        $par .= $direct;
    } else {
        $par .= $_SERVER["REQUEST_URI"];
    }

    if (!headers_sent()) {
        header("location:" . $par);
    } else
        echo "Please, press <a href='$par'>$par</a> to redirect.";

    exit;
}
header('Content-type: text/html; charset=utf-8');
?>

<pre><?= __($_SESSION['x.result']); ?></pre>
<style>

    input[type=text] {
        width: 90%;
    }

    label {
        display: block;
        clear: both;
        width: 99%;
    }

    label input[type=text], label textarea, label select {
        width: 70%;
        float: right;
    }

    label > label {
        width: 70%;
        float: right;
    }

    label > label input {

    }

    textarea {
        width: 90%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table, td, th {
        border: 1px solid gray;
    }

    th {
        background-color: lightgray;
        color: gray;
    }

    pre {
        wix-width: 100%;
        overflow-x: scroll;
        white-space: pre-wrap;
    }
</style>
<form action="" method="POST">
    <table>
        <tr>
            <th>action</th>
            <th>parameters</th>
        </tr>
        <?php
        $res = x_parser::getParameters('', 'action');
        unset($_SESSION['past_message']);
        foreach ($res['action'] as $method => $val) {

            if (!preg_match('/^do_(.*)$/', $method)) continue;
            echo '  <tr><th><button name="action" value="' . htmlspecialchars($method) . '">' . $val['title'] . '</button></th><td>';

            foreach ($val['param'] as $name => $par) {
                echo x_parser::createInput($par, __($_SESSION['form'], array()));
            }
            echo '</td></tr>';
        }
        ?>
    </table>
</form>