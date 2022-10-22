<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */

use
    \Ksnk\scaner\joblist,\Ksnk\scaner\scaner;

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('USE_NAMESPACE', 'Ksnk\\scaner\\');
define('INDEX_DIR', __DIR__);
define('TEMP_DIR', 'tmp/');

include_once "autoload.php";

Autoload::register(
    [
        '~/core', '~/libs'
    ],
    [
        USE_NAMESPACE => '',
        'Ksnk\\' => '/',
    ]
);
if (\UTILS::is_cli()) {
// вызваны в режиме cli
    //exit;
    $scaner=new scaner();
    $_res=[];
    $scaner
        ->newbuf(implode(' ',$GLOBALS['argv']))
        ->syntax([ // x_parser_scenario::do_22 2 -b='Hello world!'
        'name' => '\w+',
        'value' => "'[^']+'|\"[^\"]+\"|[^\s]+",
                'par' => '\-:name:=:value:',
                'opt' => '\-:name:',
                'param' => ':par:|:opt:|:value:',
        ]
        , '/\s*:param:\s*/m', function ($line) use (&$_res) {
            if(''==$line['par'] and $line['value']!='' ){
                $_res[]=trim($line['value'],"'\""); // косяк, если придется передавать кавычки
            } else if(!empty($line['name'])){
                $_res[$line['name']]=trim($line['value'],"'\"");
            }
 //           return false;
    });
    ENGINE::set_option('arguments', $_res);

} else {

    ENGINE::initflags();

    ENGINE::route([
        ['#^/?([^\?\&/]*)#', function ($m) {
            if (!empty($m[1]) && '' != ($f = \Autoload::find('~/scenario/' . $m[1]))) {
                ENGINE::set_option('scenario', $m[1]);
                return true;
            }
            return false;
        }],
    ]);

}

$scenario = ENGINE::option('scenario', 'default');
Autoload::register('~/scenario/' . $scenario);
ENGINE::set_option(array(
    'engine.aliaces' => array(
        'Main' => '\\Ksnk\\scaner\\' . $scenario . '_scenario'
    ),
    //'database.debug'=>1
));
// инициализация объекта Main по правилам scenario
$joblist = new joblist();
$main=ENGINE::getObj('Main',$joblist);
$main->route();//ENGINE::exec(['Main','route']);

ENGINE::startSessionIfExists();
ob_start();
try {
    ENGINE::action();
} catch (Exception $e) {
    error_log($e->getMessage());
}
$content=ob_get_contents();
ob_end_clean();
ENGINE::printgzip($content);
