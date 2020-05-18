<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */

use
    \Ksnk\scaner\joblist,
    \Ksnk\scaner\x_parser;

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('USE_NAMESPACE', 'Ksnk\\scaner\\');
define('INDEX_DIR', dirname(__DIR__));
define('TEMP_DIR', '../tmp/');

include_once "../autoload.php";

\Autoload::map([USE_NAMESPACE => '']);

$joblist = new joblist();

// акция до старта сессии. Это важно.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * кнопки пауза-стоп
     */
    if (($action = \UTILS::val($_POST, 'todo')) !== '') {
        joblist::action($action);
        exit;
    }
}
ENGINE::start_session();
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * запрос на довыполнение очередного цикла выполнения
     */
    if (UTILS::val($_GET, 'target') == 'iframe') { //?callback=log&target=iframe)
        $joblist->donext();
        $result=$joblist->getResult();
        echo '<script type="text/javascript"> top.' . UTILS::val($_GET, 'callback', 'ajax_handle') . '(' . utf8_encode(json_encode(array(
                'cnt' => $joblist->jobcount() > 0 ? sprintf('to be continued...(%s jobs in queue)', $joblist->jobcount()) : '',
                'log' => $result
            ))) . ')</script>';
        exit;
    }
    /**
     * начало выполнения
     */
    $_SESSION['form'] = [];
    foreach($_POST as $k=>$v){
        if(is_string($v) && strlen($v)>100000){
            continue;
        }
        if(is_array($v)){
            foreach($v as $kk=>$vv){
                if(is_string($vv) && strlen($vv)>100000){
                    continue;
                }
                $_SESSION['form'][$k][$kk]=$vv;
            }
            continue;
        }
        $_SESSION['form'][$k]=$v;
    }
    //$_POST;
    ob_start();
    $headers = UTILS::getallheaders();
    $response = array();

    /**
     * загрузка-дозагрузка файлов
     */
    if (!empty($_FILES) && UTILS::val($headers, "X-Requested-With") == "XMLHttpRequest") {
        // попытка загрузить файлы
        // налистываем файлы
        $uploaded = UTILS::uploadedFiles();

        foreach ($uploaded as &$v) {
            // print_r ($v);
            $xname = UTILS::translit($v['name']);
            $y = TEMP_DIR . $xname;

            if (isset($_POST['chunked']) && $_POST['chunked'] > 0) {
                $x = tempnam(TEMP_DIR, '.tp');
                move_uploaded_file(
                    $v['tmp_name'],
                    $x
                );
                $h = fopen($y, 'a');
                fwrite($h, file_get_contents($x));
                fclose($h);
                unlink($x);
                $v['chunked'] = $_POST['chunked'];
            } else {
                move_uploaded_file(
                    $v['tmp_name'],
                    $y
                );
            }
            $v['file'] = $y;
        }
        $response['uploaded'] = $uploaded;
    }

    try {
        $action = UTILS::val($_POST, 'action', '');
        list($class, $method, $empty) = explode('::', $action . '::::', 3);
        if (!empty($action)) {
            $top = array('class' => $class, 'method' => $method, 'dir' => realpath(\UTILS::val($_POST[$action], '_')));
            $res = x_parser::getParameters('', $class, $top['dir']);
            $param = array();
            foreach ($res[$class][$method]['param'] as $name => $par) {
                $param[] = \UTILS::val($_POST, $action . '|' . $name);
            }
            $joblist->append_scenario($top, $param);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        print_r($_POST);
    }

    $debug = ob_get_contents();
    ob_end_clean();
    $par = (!empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

    if (\UTILS::val($headers, "X-Requested-With") == "XMLHttpRequest") {
        //header("Content-Type: application/json");
        if (!empty($debug))
            $response['debug'] = $debug;
        echo utf8_encode(json_encode($response, JSON_UNESCAPED_UNICODE + JSON_ERROR_NONE));
    } else {
        if (empty($debug) && !headers_sent()) {
            header("location:" . $par);
        } else
            echo $debug;
        echo "Please, press <a href='$par'>$par</a> to redirect.";
    }
    exit;
}
header('Content-type: text/html; charset=utf-8');
$lsize = 4;
x_parser::$templates = array(

// блок элементов
    'textarea' => '<div class="row"><label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label><div class="col-xs-' . (12 - $lsize) . '"><textarea class="input-sm form-control" {{attr}} id="{{UID}}">{{value}}</textarea></div></div>',

    'labeledinput' => '<div class="row"> <label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label><div class="col-xs-' . (12 - $lsize) . '"><input id="{{UID}}" type="text" name="{{name}}" value="{{value}}" class="input-sm form-control"></div></div>',

    'file' => '<div class="row file_upload dropzone">' .
        '<label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label>' .
        '<div class="col-xs-' . (12 - $lsize) . '"><div class=" input-group">' .
        '<select name="{{name}}" id="{{UID}}" class="input-sm form-control" {{attr}} >{{radio}}</select>' .
        '<span class="input-group-btn">
<span class="btn btn-default btn-file" style="line-height: 1.2em;">
    + <input type="file" name="_{{name}}">
</span>' .
        '</div>' . '</div>' .
        '</div>',

    'files' => '<div class="row file_upload dropzone">' .
        '<label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label>' .
        '<div class="col-xs-' . (12 - $lsize) . ' input-group">' .
        '<select style="height:10.4em;" name="{{name}}[]" size=1 multiple="multiple" id="{{UID}}" class="form-control" {{attr}} >{{radio}}</select>' .
        '<span class="input-group-btn">
<span class="btn btn-default btn-file">
+ <input type="file" name="_{{name}}[]">
</span>' .
        '</div>' .
        '</div>',

// блок радио и чекбоксов
    'radiogroup' => '<div class="row"><label class="control_label col-xs-' . ($lsize) . ' control-label">{{label}}</label><div class="col-xs-' . (12 - $lsize) . '">{{radio}}</div></div>',

    'radio_option' => ' <label class="radio-inline"><input type="radio" name="{{name}}" value="{{value}}" {{checked}}>{{label}}</label>',

    'checkbox' => ' <label class="checkbox-inline"><input type="checkbox" name="{{name}}[]" value="{{value}}" {{checked}}>{{label}}</label>',

    'select' => '<div class="row"> <label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label><div class="col-xs-' . (12 - $lsize) . '"><select name="{{name}}" id="{{UID}}" class="form-control input-sm" {{attr}} >{{radio}}</select></div></div>',

    'select_option' => '<option value="{{value}}" class="small form-control"{{selected}}>{{label}}</option>',

// обрамление элементов формы
    '_' => '<a name="{{method}}"></a><div class="panel panel-primary" id="{{method}}" style="display:none;"><div class="panel-heading">
            <span class="input-group-btn"><span class="input-group-btn">
            <button  class="input-sm form-control" type="submit" name="action" value="{{method}}"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button></span>
            <input type="hidden" name="{{method}}[_]" value="{{sc}}">
            <h3 class="input-group-addon" style="color: white;background: none;border: none;">{{title}}</h3>
<a href="#" data-toggle="popover" title="Popover Header" data-content="Some content inside the popover">Toggle popover</a></span>
            </div>{% if res %}<div class="panel-body">{{res}}</div>{% endif %}</div>'
);

$filter = \UTILS::val($_GET, 'filter');
if (!empty($filter)) {
    $filter = \UTILS::masktoreg($filter);
} else {
    $filter = '/./';
}

$tag = 'unknown';
if (!empty($_GET['tag'])) {
    $tag = $_GET['tag'];
}

$tags = array();

$data = array();
\UTILS::findFiles('../scenario/*/*_scenario.php', function ($sc) use (&$tags, &$filter, &$tag, &$data) {
    $classname = USE_NAMESPACE . basename($sc, '.php');
    $res = x_parser::getParameters('', $classname, realpath($sc));
    foreach ($res[$classname] as $method => $val) {
        if (preg_match('/^do_/', $method)) {
            $tags = array_merge($tags, $res['tags']);
            break;
        }
    }
    if (!in_array($tag, $res['tags'])) {
        // var_export($res['tags']);
        return 0;
    }
    if (empty($res) || empty($res[$classname]) || !preg_match($filter, $classname)) {
        return 0;
    }
    foreach ($res[$classname] as $method => $val) {
        if (!preg_match('/^do_(.*)$/', $method))
            continue;

        $r = '';
        foreach ($val['param'] as $name => $par) {
            $par['class'][] = "form-control";
            $par['parname'] = $name;

            $r .= x_parser::createInput($par, \UTILS::val($_SESSION, 'form', []));
        }
        $data[] = array(
            'title' => $val['title'],
            'method' => htmlspecialchars($classname . '::' . $method),
            'res' => trim($r),
            'sc' => htmlspecialchars($sc),
            'anchor' => urlencode($val['title']),
        );
    }
    return null;
});

ob_start();
$joblist->donext();
$result = [];//$joblist->getResult();
$result=[];$lastpre='';
foreach($joblist->getResult() as $r){
    if($lastpre!==$r[0]){
        $lastpre=$r[0];
        $result[]=$r;
        $ref_result=&$result[count($result)-1];
    } else {
        $ref_result[2].=$r[2];
    }
}

if ('' != ($_r = trim(ob_get_contents()))) {
    $result[] = [$joblist::OUTSTREAM_PRE,'', $_r];
}
ob_end_clean();


Autoload::register(['~/libs/template', '~/template']);

template_compiler::checktpl(array(
    'templates_dir' => '../template/',
    'TEMPLATE_PATH' => '../template/',
    'PHP_PATH' => '../template/',
    'TEMPLATE_EXTENSION' => 'twig',
//    'FORCE'=>1 // для обязательной перекомпиляции щаблонов
));

echo ENGINE::template('tpl_webclient', '_', array(
    'result' => $result,
    'data' => $data,
    'tags' => array_unique($tags),
    'joblist' => $joblist,
));
