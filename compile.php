<?php
/**
 * Сборка проекта в PHAR
 */

include_once "autoload.php";

$file = "build/scaner.phar";

if (file_exists($file)) {
    unlink($file);
}

$phar = new Phar($file, 0, 'scaner.phar');
$phar->setSignatureAlgorithm(Phar::SHA1);

$phar->startBuffering();
$files = array();
foreach (array('core/*.php','libs/*.php','template/*.php'
         ,'webclient/index.php','webclient/webclient.css'
         //, 'scenario/dishonestsupplier/*.php'
         //, 'scenario/testsftp/*.php'
             //'scenario/sqlfiddle/*.php'
         ) as $dir) {
    $mask = UTILS::masktoreg($dir);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . dirname($dir)), RecursiveIteratorIterator::CHILD_FIRST
    );
    /** @var \SplFileInfo $path */
    foreach ($iterator as $path) {
        if ($path->isFile() && preg_match($mask, $path->getPathname())) {
            $files[str_replace("\\", '/',
                str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path->getPathname()))] = $path;
        }
    }
}

$files['autoload.php'] = 'autoload.php';

foreach ($files as $f => $path) {
    $phar->addFromString($f, file_get_contents($path));
}

$binary = file(__DIR__ . '/cli.php');
//unset($binary[1]);
unset($binary[0]);
/*
$phar->setStub("#!/usr/bin/env php\n<?php Phar::mapPhar('" . $file . "');
" .
    str_replace(
        '$baseDir = dirname(dirname(__FILE__));',
        '$baseDir = __DIR__;',
        implode('', $binary)
    ) . "
__HALT_COMPILER();");
*/
$phar->setStub("<?php 
__HALT_COMPILER();?>");

$phar->stopBuffering();
//$phar->compress(Phar::GZ);
unset($phar);

//chmod($file, 0755);
