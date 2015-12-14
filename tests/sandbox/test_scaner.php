<?php
/**
 * тест сканера.
 * Date: 30.11.15
 * Time: 9:09
 */


ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once "../autoload.php";

$log='../scenario/lapsi.log/lapsi.msk.ru.access.log.0.gz';
$ip='62.133.162.140';//
//$ip='76.195.13.12';

include_once '../scenario/lapsi.log/grep_logs_scenario.php';
$config = (object)array();
$config->scaner = new scaner();
$config->joblist = new joblist();
$config->scenario = new grep_logs_scenario();

$config->scaner->init($config);
$config->joblist->init($config);
$config->scenario->init($config);
$config->scenario->scan_access_log($log, $ip);
echo implode("\n\n",$config->scenario->result);

