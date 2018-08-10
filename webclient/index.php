<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
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
    /**
     * I'v hate iconv. Don't ask me why. I am using this function only once to translit
     * file name typed in russian
     */
    function translit($text)
    {
        $ar_latin=array('a', 'b', 'v', 'g', 'd', 'e', 'jo', 'zh', 'z', 'i', 'j', 'k',
            'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh', 'shh',
            '', 'y', '', 'je', 'ju', 'ja', 'je', 'i');
        $text = trim(str_replace(array('а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к',
            'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ',
            'ъ', 'ы', 'ь', 'э', 'ю', 'я', 'є', 'ї'),
            $ar_latin, $text));
        $text = trim(str_replace(array('А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К',
            'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ',
            'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я', 'Є', 'Ї')
            , $ar_latin, $text));
        return $text;
    }

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
foreach ($scenariofiles as $sc) {
    $classname = basename($sc, '.php');
    $res = x_parser::getParameters('', $classname, realpath($sc));
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

?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Комплект утилит</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"
          integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css"
          integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
    <script  src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"
            integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS"
            crossorigin="anonymous"></script>
    <script  src="js/dropzone.js"></script>

    <style>
        .panel-primary > .panel-heading {
            overflow: hidden;
        }

        /* Body padding required for fixed navbar */
        body {
            padding-top: 70px;
        }

        /* Popover */
        .popover {
            border: 2px dotted red;
        }

        /* Popover Header */
        .popover-title {
            background-color: #73AD21;
            color: #FFFFFF;
            font-size: 28px;
            text-align: center;
        }

        /* Popover Body */
        .popover-content {
            background-color: coral;
            color: #FFFFFF;
            padding: 25px;
        }

        /* Popover Arrow */
        .arrow {
            border-right-color: red !important;
        }

        .label {
            display: inline-block;
            white-space: pre-wrap;
        }

        .btn-file {
            position: relative;
            overflow: hidden;
        }
        .btn-file input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: inherit;
            display: block;
        }
        .wrapper_timeout {
            display: none;
            position: fixed;
            height: 100%;
            width: 100%;
            top: 0;
            background: #000;
            left: 0;
            z-index: 1100;
            opacity: 0.6; }

        .wrapper_timeout img {
            width: 60px;
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -30px;
            margin-top: -30px;
            animation-name: timeout_mobile;
            animation-duration: 1200ms;
            animation-iteration-count: infinite;
            animation-timing-function: linear; }
        @keyframes timeout_mobile {
            /** oneclick */
            from {
                transform: rotate(0deg); }
            to {
                transform: rotate(360deg); } }
    </style>
    <script type="text/javascript">

        function log(obj){
            console.log(obj.cnt);
            $('#continue input').attr("value",obj.cnt);
            $('#tasklog').append(obj.log);
            if(obj.cnt!=''){
                setTimeout(function(){
                    $('#continue input').trigger('click');
                },1000);
            }
        }

        function _scrollIntoView(el, options) {
            el = $(el);
            var topdisp = el.height() + 2,
                tdisp = options && options.topdisp || 10,// смещение до верха
                bdisp = options && options.botdisp || 10; // смещение до низа
            if (el.length > 0) {
                var xpos = $(window).scrollTop(),
                    ofs = el.offset();
                if (ofs.top < xpos + tdisp) { // tdisp - высота тулбара админки Joomla
                    $(window).scrollTop(ofs.top - tdisp);
                } else if (ofs.top + topdisp > xpos + $(window).height() - bdisp) {// bdisp - высота футера админки Joomla
                    $(window).scrollTop(ofs.top + 10 + bdisp + topdisp - $(window).height());
                }
            }
        }
        function _goto(anchor){
            console.log(anchor);
            var el=document.getElementById(anchor) || false;
            $('.panel.panel-primary').hide();
            $(el).show();
            location.hash=anchor;
            _scrollIntoView(el, {topdisp: 60});
        }
        $(function () {
            $(document).on('click', '.anchorlink', function () {
                var h = $(this).data('href').match(/#(.*)$/);
                h && h[1] && _goto(h[1]);
                return false;
            });
            console.log(location.hash);
            if(location.hash){
                _goto(location.hash.replace(/^\#/,''));
            }
            $('[data-toggle="popover"]').popover();
        })
    </script>
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand"
               data-toggle="modal"
               data-target="#basicModal"
               href="remote-page.html"><span class="glyphicon glyphicon-wrench" aria-hidden="true"></span></a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <?php
            ob_start();
            while ($joblist->donext()) {
                ;
            }
            $result = trim(ob_get_contents());
            ob_end_clean();

            ?>
            <ul class="nav navbar-nav">

            </ul>

        </div>
    </div>
    <!-- /.navbar-collapse -->
</nav>
<div class="container bs-docs-container">

    <div class="row">
        <div class="col-sm-6 col-lg-4 col-6" style="padding-bottom:30px;">
            <?php
            foreach ($data as $d) {
                echo '<span data-href="#' . $d['method'] . '" class="anchorlink label label-primary">' . $d['title'] . '</span> ';
            }
            ?>
        </div>
        <div class="col-sm-6 col-lg-4 col-6" style="overflow:auto;">
            <form action="" method="POST" enctype="multipart/form-data">
                <?php
                foreach ($data as $d) {
                    echo x_parser::tpl($d, x_parser::$templates['_']);
                }
                ?>
            </form>
        </div>
    </div>
    <div class="modal fade" id="basicModal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button class="close" type="button" data-dismiss="modal">x</button>
                    <h4 class="modal-title" id="myModalLabel">Конфигурация</h4>
                </div>
                <div class="modal-body">
                    <!--<h3>Содержимое модального окна</h3>-->

                </div>
                <div class="modal-footer">
                    <button class="btn btn-default" type="button" data-dismiss="modal">Закрыть</button>
                    <button class="btn btn-primary" type="button">Сохранить изменения</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    if($joblist->jobcount()>0){
        ?>
        <iframe name="cexecution" id="cexecution" style="display:none"></iframe>
        <form id="continue" action="?callback=log&target=iframe" target ="cexecution" method="POST" enctype="multipart/form-data">
        <input type="submit" value="<?=sprintf('to be continued...(%s jobs in queue)',$joblist->jobcount())?>">
        </form>
        <?php
    }

    if ('' != $result) {
        ?>
        <pre id="tasklog" class="well col-xs-12">
                <?= $result ?></pre>
    <?php }

    ?>

    <div class="wrapper_timeout" style="display: none;"><img src='data:image/svg+xml;utf-8,<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" height="64" viewBox="0 0 64 64"><path d="M64 32c-0.080-4.179-0.986-8.345-2.661-12.154-1.669-3.813-4.091-7.266-7.065-10.12-2.972-2.856-6.496-5.114-10.312-6.603-3.813-1.495-7.912-2.209-11.962-2.123-4.051 0.080-8.083 0.961-11.771 2.585-3.691 1.619-7.034 3.967-9.796 6.848-2.764 2.88-4.947 6.294-6.387 9.987-1.445 3.691-2.133 7.657-2.047 11.579 0.080 3.922 0.935 7.822 2.508 11.388 1.568 3.569 3.842 6.802 6.632 9.472 2.788 2.672 6.092 4.782 9.663 6.17 3.57 1.394 7.403 2.056 11.196 1.97 3.794-0.081 7.56-0.91 11.005-2.432 3.447-1.518 6.57-3.718 9.148-6.415 2.58-2.696 4.615-5.89 5.953-9.339 0.815-2.091 1.367-4.275 1.659-6.487 0.078 0.005 0.156 0.008 0.235 0.008 2.209 0 4-1.791 4-4 0-0.112-0.006-0.223-0.015-0.333h0.015zM57.644 42.622c-1.467 3.325-3.593 6.337-6.199 8.824-2.604 2.488-5.688 4.449-9.015 5.737-3.327 1.292-6.893 1.903-10.43 1.818-3.538-0.081-7.037-0.858-10.239-2.28-3.203-1.417-6.105-3.468-8.5-5.982-2.396-2.512-4.283-5.485-5.52-8.691-1.242-3.205-1.827-6.638-1.742-10.047 0.081-3.41 0.833-6.776 2.203-9.856 1.366-3.081 3.344-5.873 5.765-8.176 2.421-2.304 5.283-4.117 8.367-5.303 3.084-1.191 6.383-1.75 9.664-1.665 3.281 0.082 6.514 0.807 9.473 2.127 2.959 1.316 5.641 3.219 7.852 5.549 2.212 2.329 3.951 5.081 5.087 8.043 1.141 2.962 1.674 6.129 1.589 9.281h0.015c-0.009 0.11-0.015 0.221-0.015 0.333 0 2.063 1.562 3.761 3.568 3.977-0.389 2.176-1.033 4.301-1.924 6.312z" fill="#ffffff"></path>
</svg>'></div>

</div>
<?php
if($joblist->jobcount()>0){
?>
<script type="text/javascript">
$(function(){
    setTimeout(function(){
        $('#continue input').trigger('click');
    },1000);
})
</script>
<?php
}
?>
</body>
</html>