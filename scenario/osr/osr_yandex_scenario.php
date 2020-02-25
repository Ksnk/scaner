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

    var $vocabular='',
        $imgurl='/scaner/data/img/';

    function __construct($joblist=null){
        parent::__construct($joblist);
        $this->vocabular=\Autoload::find('~/libs/osr/yandex.voc.json');
    }

    function recognizeone($url)
    {
        $this->outstream(self::OUTSTREAM_HTML_FRAME);
        try {
            $osr = new osr_micro();
            $osr->vocabular($this->vocabular);
            $imgpath = __DIR__ . '/../../data/img/';

            $img_file = $imgpath . $url . '.bw.png';//'ksnk.500mb.net.bw.png';
            if (!file_exists(\UTILS::_2sys($img_file))) {
                $s = file_get_contents(sprintf('https://yandex.ru/cycounter?%s&theme=light&lang=ru', $url));
                $im = imagecreatefromstring($s);
                if ($im && imagefilter($im, IMG_FILTER_GRAYSCALE)) {
                    imagepng($im, \UTILS::_2sys($img_file));
                }
            }

            $osr->newImage(\UTILS::_2sys($img_file));

            printf("<div class='col-sm-6 col-lg-4 col-6'><img src='%s'> %s, %s</div>\n", $this->imgurl . urlencode($url) . '.bw.png', $osr->recognize(30, 1), $url);

        } catch (\Exception $e) {
            echo 'обломинго:' . $e->getMessage();
        }
    }

    /**
     * Проверка распознавания по списку
     */
    function do_wizard()
    {
        $osr = new osr_micro();
        $osr->vocabular($this->vocabular);
        $csv = CSV::getcsv(\Autoload::find('~/tests/data/urls.csv'));
        while ($row = $csv->nextRow()) {
            $this->joblist->append_scenario('recognizeone', [$row[0]]);
        }
    }

    /**
     * Проверка распознавания одного элемента
     * @param $img :file[*.png|../data/img/*.png] картинки
     */
    function do_scanImage($img, $startwith=30)
    {
        $this->outstream(self::OUTSTREAM_HTML_FRAME);
        $osr = new osr_micro();
        $osr->vocabular($this->vocabular);
        $osr->newImage(\UTILS::_2sys($img));

        $url=preg_replace('~^.*?/scaner/~','/scaner/',$img);
      $url=implode('/',array_map (function($a){
        return urlencode($a);
      },explode('/',preg_replace('~^.*?/scaner/~','/scaner/',$img))));
      printf("<div class='col-sm-6 col-lg-4 col-6'><img src='%s'> %s, %s</div>\n", $url , $osr->recognize($startwith, 1), $url);
    }

    /**
     * Вывести все символы
     * @param int $simb для символа
     */
    function do_printMask($simb=4)
    {
        $this->outstream(self::OUTSTREAM_HTML_FRAME);
        $data=json_decode(file_get_contents($this->vocabular),true);
        foreach($data['data'] as $simbol){
            if($simbol['simbol']==$simb) {
                printf("<table style='line-height: 0.5em;'>");
                for($i=0;$i<$data['height'];$i++){
                    printf("<tr>");
                    for($j=0;$j<$data['width'];$j++){
                        printf("<td>%s</td>", in_array($i*$data['width']+$j,$simbol['mask'])?'*':'');
                    }
                    printf("</tr>");
                }
                printf("</table><hr>");
            }
        }
    }
}
