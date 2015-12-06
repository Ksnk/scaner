<?php
/**
 * тест сканера.
 * Date: 30.11.15
 * Time: 9:09
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once "../autoload.php";

$joblist = new joblist();
//*
$joblist->append_scenario(
    array('dir'=>'../scenario/sphinx.log/sphinx2db_scenario.php','class'=>'sphinx2db_scenario','method'=>'do_todb'),
    array('D:\\projects\\scaner\\scenario\\sphinx.log\\query.log','-3 month','-2 month')
);/**/
// jumps:13,nomatch:164530,match:164465  15.675897 sec spent (2015-12-06 02:06:06)
/*
$joblist->append_scenario(
    array('dir'=>'../scenario/sphinx.log/sphinx2db_scenario.php','class'=>'sphinx2db_scenario','method'=>'do_todb'),
    array('D:\\projects\\scaner\\scenario\\sphinx.log\\query.log.gz','-3 month','-2 month')
);/**/
while($joblist->donext()){;}



