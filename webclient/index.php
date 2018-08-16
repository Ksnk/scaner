<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('INDEX_DIR',dirname(__DIR__));

include_once "../autoload.php";

    define('TEMP_DIR','../temp/');

function __(&$x, $default = '')
{
    return empty($x) ? $default : $x;
}


session_start();
$joblist = new joblist();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    //  "continue"
    if(UTILS::val($_GET,'target')=='iframe'){ //?callback=log&target=iframe)
        ob_start();
        while ($joblist->donext()) {
            ;
        }
        $result = trim(ob_get_contents());
        ob_end_clean();
        echo '<script type="text/javascript"> top.'.UTILS::val($_GET,'callback','ajax_handle').'('.utf8_encode(json_encode(array(
                'cnt'=>$joblist->jobcount()>0?  sprintf('to be continued...(%s jobs in queue)',$joblist->jobcount()):'',
                'log'=>$result
            ))).')</script>';
        exit;
    }

    $_SESSION['form'] = $_POST;
  /*  $x=glob(TEMP_DIR.'/.up*');
    while(count($x)>0){
        unlink($x[0]);
        array_shift($x);
    }*/
    ob_start();
    $headers=UTILS::getallheaders();
    $response=array();

    if(!empty($_FILES) && UTILS::val($headers,"X-Requested-With")== "XMLHttpRequest"){
        //echo realpath(TEMP_DIR);
        // попытка загрузить файлы
        // налистываем файлы
        $uploaded=UTILS::uploadedFiles();

        foreach($uploaded as &$v){
           // print_r ($v);
            $xname = UTILS::translit($v['name']);
            $y=TEMP_DIR . $xname ;

            if(isset($_POST['chunked']) && $_POST['chunked']>0){
                $x=tempnam(TEMP_DIR,'.tp');
                move_uploaded_file(
                    $v['tmp_name'],
                    $x
                );
                $h=fopen($y,'a');
                fwrite($h,file_get_contents($x));
                fclose($h);
                unlink($x);
                $v['chunked']=$_POST['chunked'];
            } else {
                move_uploaded_file(
                    $v['tmp_name'],
                    $y
                );
            }
            $v['file']=$y;
        }
        $response['uploaded']=$uploaded;
    }

    try {
        $action = UTILS::val($_POST,'action','');
        list($class, $method, $empty) = explode('::', $action . '::::', 3);
        if(!empty($action)){
            $top = array('class' => $class, 'method' => $method, 'dir' => realpath(UTILS::val($_POST[$action],'_')));
        //var_dump($action);//var_dump($top['dir']);
        //if($class=='braindesign_scan_scenario')
        $res = x_parser::getParameters('', $class, $top['dir']);
        $param = array();
        foreach ($res[$class][$method]['param'] as $name => $par) {
           /*if($par['type']=='file' ){
                $res=array();
                if(''!=UTILS::val($_FILES,$action.'|name|'.$name,'')){
                    if( 0==UTILS::val($_FILES,$action.'|error|'.$name,0)){
                        $test_file=tempnam(TEMP_DIR,'.up');
                        move_uploaded_file($_FILES[$action]['tmp_name'][$name],$test_file);
                        $res=array(
    $test_file=>$_FILES[$action]['name'][$name]
                        );
                    }
                }
                $param[]=$res;
            } else if($par['type']=='files' ){
                $res=array();
                if(isset($_FILES[$action]['name'][$name])){
                    foreach($_FILES[$action]['name'][$name] as $k=>$v){
                        if( empty($_FILES[$action]['error'][$name][$k])){
                            $test_file=tempnam(TEMP_DIR,'.uploaded');
                            move_uploaded_file($_FILES[$action]['tmp_name'][$name][$k],$test_file);
                            $res[$test_file]=$v;
                        }
                    }
                }
                $param[]=$res;
            } else {*/
                $param[] = __($_POST[$action][$name]);
           // }
        }
        $joblist->append_scenario($top, $param);
        }
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        print_r($_POST);
    }

    $debug = ob_get_contents();
    ob_end_clean();
    $par = 'http://' . $_SERVER["HTTP_HOST"];
    if (!empty($direct)) {
        $par .= $direct;
    } else {
        $par .= $_SERVER["REQUEST_URI"];
    }

    if(UTILS::val($headers,"X-Requested-With")== "XMLHttpRequest"){
        //header("Content-Type: application/json");
        if (!empty($debug))
            $response['debug']= $debug;
        echo utf8_encode(json_encode($response,JSON_UNESCAPED_UNICODE+JSON_ERROR_NONE));
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

    'file' => '<div class="row file_upload dropzone">'.
        '<label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label>'.
        '<div class="col-xs-' . (12 - $lsize) . '"><div class=" input-group">'.
            '<select name="{{name}}" id="{{UID}}" class="input-sm form-control" {{attr}} >{{radio}}</select>'.
            '<span class="input-group-btn">
<span class="btn btn-default btn-file" style="line-height: 1.2em;">
    + <input type="file" name="_{{name}}">
</span>'.
        '</div>'.'</div>'.
    '</div>',

    'files' => '<div class="row file_upload dropzone">'.
        '<label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label>'.
        '<div class="col-xs-' . (12 - $lsize) . ' input-group">'.
        '<select name="{{name}}[]" size=1 multiple="multiple" id="{{UID}}" class="form-control" {{attr}} >{{radio}}</select>'.
        '<span class="input-group-btn">
<span class="btn btn-default btn-file">
+ <input type="file" name="_{{name}}[]">
</span>'.
        '</div>'.
        '</div>',

// блок радио и чекбоксов
    'radiogroup' => '<div class="row"><label class="control_label col-xs-' . ($lsize) . ' control-label">{{label}}</label><div class="col-xs-' . (12 - $lsize) . '">{{radio}}</div></div>',

    'radio_option' => ' <label class="radio-inline"><input type="radio" name="{{name}}" value="{{value}}" {{checked}}>{{label}}</label>',

    'checkbox' => ' <label class="checkbox-inline"><input type="checkbox" name="{{name}}[]" value="{{value}}" {{checked}}>{{label}}</label>',

    'select' => '<div class="row"> <label for="{{UID}}" class="col-xs-' . ($lsize) . ' control-label">{{label}}</label><div class="col-xs-' . (12 - $lsize) . '"><select name="{{name}}" id="{{UID}}" class="form-control input-sm" {{attr}} >{{radio}}</select></div></div>',

    'select_option' => '<option value="{{value}}" class="form-control"{{selected}}>{{label}}</option>',

// обрамление элементов формы
    '_' => '<a name="{{method}}"></a><div class="panel panel-primary" id="{{method}}" style="display:none;"><div class="panel-heading">
            <span class="input-group-btn"><span class="input-group-btn">
            <button  class="input-sm form-control" type="submit" name="action" value="{{method}}"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button></span>
            <input type="hidden" name="{{method}}[_]" value="{{sc}}">
            <h3 class="input-group-addon" style="color: white;background: none;border: none;">{{title}}</h3>
<a href="#" data-toggle="popover" title="Popover Header" data-content="Some content inside the popover">Toggle popover</a></span>
            </div>{% if res %}<div class="panel-body">{{res}}</div>{% endif %}</div>'
);

$filter =UTILS::val($_GET,'filter');
if(!empty($filter)){
    $filter=UTILS::masktoreg($filter);
} else {
    $filter='/./';
}


$data = array();
$scenariofiles = glob('../scenario/*/*_scenario.php');
$tag='unknown';
if(!empty($_GET['tag'])){
    $tag=$_GET['tag'];
}

$tags=array();

foreach ($scenariofiles as $sc) {
    $classname = basename($sc, '.php');
    $res = x_parser::getParameters('', $classname, realpath($sc));
    //var_export($tag);
    foreach ($res[$classname] as $method => $val) {
        if (preg_match('/^do_(.*)$/', $method)) {
            $tags = array_merge($tags, $res['tags']);
            break;
        }
    }
    if(!in_array($tag,$res['tags'])){
       // var_export($res['tags']);
        continue;
    }
    if (empty($res) || empty($res[$classname]) || !preg_match($filter,$classname)) {
        //ENGINE::debug($filter,$res);
        continue;
    }
    foreach ($res[$classname] as $method => $val) {

        if (!preg_match('/^do_(.*)$/', $method)) continue;

        //  echo '<!--xxx-- '.print_r($val,true).' -->';
        $res = '';
        foreach ($val['param'] as $name => $par) {
            $par['class'][] = "form-control";
            $par['parname'] = $name;

            $res .= x_parser::createInput($par, __($_SESSION['form'], array()));
        }
        $data[] = array(
            'title' => $val['title'],
            'method' => htmlspecialchars($classname . '::' . $method),
            'res' => trim($res),
            'sc' => htmlspecialchars($sc),
            'anchor' => urlencode($val['title']),
        );
    }
}

ob_start();
while ($joblist->donext()) {;}
$result = trim(ob_get_contents());
ob_end_clean();

Autoload::register(['~/libs/template','~/template']);

template_compiler::checktpl(array(
    'templates_dir'=> '../template/',
    'TEMPLATE_PATH'=> '../template/',
    'PHP_PATH'=> '../template/',
    'TEMPLATE_EXTENSION'=>'twig',
//    'FORCE'=>1 // для обязательной перекомпиляции щаблонов
));

echo ENGINE::template('tpl_webclient','_',array(
    'result'=>$result,
    'data'=>$data,
    'tags'=>array_unique($tags),
    'joblist'=>$joblist,
));
