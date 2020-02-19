<?php
/**
 * Сборка проекта в PHAR
 */
chdir('build');$index=getcwd();
$file = "vendor.phar";

if (file_exists($file)) {
    unlink($file);
}
require_once "phar://scaner.phar/autoload.php";

$phar = new Phar($file, 0, 'vendor.phar');
$phar->setSignatureAlgorithm(Phar::SHA1);
$dirs=['vendor/composer/*.*'];
// список файлов composer.json для анализа.
$package_list=['composer.json'];
//$package_list=['vendor/phpoffice/phpspreadsheet/composer.json'];
//$dirs[]='vendor/phpoffice/phpspreadsheet/**';
$already_done=[];

while(!empty($package_list)){
    $next=array_shift($package_list);

    $data=json_decode(file_get_contents($next),true);
    if(!empty($data) && !empty($data['require'])){
        foreach($data['require'] as $package=>$ver){
            if(file_exists($file='vendor/'.$package.'/composer.json')) {
                if(!isset($already_done[$package])){
                    $already_done[$package]=true;
                }
                $dirs[]='vendor/'.$package.'/**';
                $package_list[] = $file;
            }
        }
    }

}

$phar->startBuffering();
$files = array();
foreach ($dirs as $dir) {
    $mask = UTILS::masktoreg($dir);
    $realdir=preg_replace('~/?[^/]*[\*\?].*$~','',$dir);
    echo $realdir."\n";
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($index . DIRECTORY_SEPARATOR . $realdir), RecursiveIteratorIterator::CHILD_FIRST
    );
    /** @var \SplFileInfo $path */
    foreach ($iterator as $path) {
        if ($path->isFile() && preg_match($mask, $path->getPathname())) {
            $files[str_replace("\\", '/',
                str_replace($index . DIRECTORY_SEPARATOR, '', $path->getPathname()))] = $path;
        }
    }
}

$files['vendor/autoload.php'] = 'vendor/autoload.php';
echo "so, let's store ".count($files).' files'."\n";
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
echo "so, let's gzip it "."\n";

$phar->compressFiles(Phar::GZ);
unset($phar);

//chmod($file, 0755);
