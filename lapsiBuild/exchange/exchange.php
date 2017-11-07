<?php
/**
 * головной файл проекта обмена инфорацией со стокке
 */

require_once "../autoload.php";
Autoload::register(dirname(__FILE__));

$handle = new Stokke_Exchange();

$method= 'handle_'.
    (isset($_REQUEST['type'])?$_REQUEST['type']:'0').'_'
    .(isset($_REQUEST['mode'])?$_REQUEST['mode']:'0');

$handle->log('method '.$method);
if(method_exists($handle,$method)){
    echo $handle->$method();
} else
    echo 'method not found';