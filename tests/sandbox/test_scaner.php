<?php
/**
 * тест сканера.
 * Date: 30.11.15
 * Time: 9:09
 */

use \Ksnk\scaner\scaner;
use \Ksnk\scaner\joblist;

ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once "../../autoload.php";

$log='../../scenario/lapsi.log/lapsim.ru.access.log.1.gz';
$ip='213.171.199.22';//
//$ip='76.195.13.12';

include_once '../../scenario/lapsi.log/grep_logs_scenario.php';
$config = (object)array();
$config->scaner = new scaner();
$config->joblist = new joblist();
$config->scenario = new grep_logs_scenario();

$config->joblist->init($config);
$config->scenario->init($config);
$config->scenario->do_scanlog($log,'', $ip);
//print_r($config->scaner->getresult());
//echo implode("\n\n",$config->scaner->getresult());

