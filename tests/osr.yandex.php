<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 31.01.2020
 * Time: 7:29
 */
/**
 * в нашей реальности картинки 88 на 31. логотип начинается со смещения 30
 * в них шрифтом на 11px высотой и примерно 6-8  шириной с размытием
 * смещение 10px сверху, выравнивается по пикселю 80 справа
 * DADADA - цвет фона 2e - цвет текста
 */
require_once "../autoload.php";

use \Ksnk\scaner\csv,
    \osr\osr_micro;

$osr = new osr_micro();

try {
    $osr->_out(function($m){echo $m;})
       // ->_error(function($m){echo $m;})
        ->vocabular( '~/yandex.voc.json');
    $csv = CSV::getcsv('data/urls.csv');
    $row = $csv->nextRow();
    $cnt = 0;
    while ($row = $csv->nextRow()) {
        $url = $row[0];
        $img_file = 'img/' . $url . '.bw.png';//'ksnk.500mb.net.bw.png';
        if (!file_exists($img_file)) {
            $s = file_get_contents(sprintf('https://yandex.ru/cycounter?%s&theme=light&lang=ru', $url));
            $im = imagecreatefromstring($s);
            if ($im && imagefilter($im, IMG_FILTER_GRAYSCALE)) {
                imagepng($im, $img_file);
            }
        }

        $osr->newImage($img_file);
        printf("%s)\t%s\t%s\n", ++$cnt, $url, $osr->recognize(30, 1));
    }
} catch (Exception $e) {
    echo 'обломинго:' . $e->getMessage();
}
