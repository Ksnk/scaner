<?php
//
// Start the process in the background
//
$bat_filename='D:\\projects\\scaner\\tests\\start.bat';
$exe = "start /b ".$bat_filename;
if( pclose(popen($exe, 'r')) ) {
    return true;
}