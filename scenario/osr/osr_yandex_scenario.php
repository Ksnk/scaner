<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

//require '../../autoload.php';
namespace Ksnk\scaner;

use \osr\osr_micro;

/**
 * Обучение OSR
 *
 * @tags osr
 */
class osr_yandex_scenario extends scenario
{


    function recognizeone($url){
      $this->outstream(self::OUTSTREAM_HTML_FRAME);
      try {
      $osr = new osr_micro();
      $osr->_out(function($m){echo $m;})
        // ->_error(function($m){echo $m;})
        ->vocabular( \Autoload::find('~/libs/osr/yandex.voc.json'));
      $imgpath=__DIR__.'/../../data/img/';
      $imgurl='/scaner/data/img/';

      $img_file = $imgpath. $url . '.bw.png';//'ksnk.500mb.net.bw.png';
      if (!file_exists(\UTILS::_2sys($img_file))) {
        $s = file_get_contents(sprintf('https://yandex.ru/cycounter?%s&theme=light&lang=ru', $url));
        $im = imagecreatefromstring($s);
        if ($im && imagefilter($im, IMG_FILTER_GRAYSCALE)) {
          imagepng($im, \UTILS::_2sys($img_file));
        }
      }

      $osr->newImage(\UTILS::_2sys($img_file));

      printf("<div class='col-sm-6 col-lg-4 col-6'><img src='%s'> %s, %s</div>\n",  $imgurl.urlencode($url ). '.bw.png', $osr->recognize(30, 1),$url);

      } catch (\Exception $e) {
        echo 'обломинго:' . $e->getMessage();
      }
    }

    /**
     * вАлшебник
     */
    function do_wizard($skiptill=""){
      $csv = CSV::getcsv(\Autoload::find('~/tests/data/urls.csv'));
      while ($row = $csv->nextRow()) {
        $this->joblist->append_scenario('recognizeone',[$row[0]]);
      }
    }
}
