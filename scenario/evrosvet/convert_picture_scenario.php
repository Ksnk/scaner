<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 07.04.16
 * Time: 13:00
 */

/**
 * конвертирование картинок с шершавым фоном.
 * Class convert_picture_scenario
 */
class convert_picture_scenario extends scenario
{

    /**
     * шумодав на коленке для маленьких жипегчиков .
     * @param file $imgfile :select[~dir(*.jpg)]  имя файла для обработки
     * @param string $tmpname :text имя файла с результатом, без расширения
     */
    function do_convert($imgfile,$tmpname='tmp'){
        $orig=imagecreatefromjpeg($imgfile);
        $width = imagesx($orig);
        $height = imagesy($orig);
        $mask=imagecreatetruecolor($width,$height);
        $img=imagecreatetruecolor($width,$height);
        // копируем оригинал
        imagecopy($mask,$orig,0,0,0,0,$width,$height);
        imagecopy($img,$orig,0,0,0,0,$width,$height);
        // делаем маску
        imagefilter($mask,IMG_FILTER_EDGEDETECT);
        imagefilter($mask,IMG_FILTER_GRAYSCALE);
        // imagefilter($img,IMG_FILTER_CONTRAST,-100); // не надо, становится хуже :(

        // Гаусс блур для  $img
        //imagefilter($img,IMG_FILTER_GAUSSIAN_BLUR);
        // или сильный блур для остальной картинки
        imagefilter($img,IMG_FILTER_SELECTIVE_BLUR);

        for($i=0;$i<$width;$i++)for($j=0;$j<$height;$j++){
            $rgb=imagecolorat($mask,$i,$j);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $col=abs(128-($r+$g+$b)/3);
            if($col>110){
                $c=imagecolorat($orig,$i,$j);
                imagesetpixel($img,$i,$j,$c);
            }
        }
        // $img - готовая картинка с уменьшенным шумом
        imagepng($img,dirname($imgfile).'/'.$tmpname.'.png');
        imagedestroy($mask);
        imagedestroy($orig);
        imagedestroy($img); // порядок прежде всего
        //echo dirname($imgfile).'/'.$tmpname.'.png';
    }
}