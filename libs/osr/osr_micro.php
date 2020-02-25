<?php
/**
 * Простенький OSR. изначально затачивался для распознавания Яндекс ИКС и его
 * картинок-индикаторов. Ибо остальные сервисы либо платны либо на них банют ;(
 * Структура данных - json с полями
 * - version: 1.0
 * - width: 8
 * - height:10 - размер матрицы символа, за пределами которой символ искать не нужно
 * - data - массив изображений символов
 *  - [simbol: <<cbvdjk>>, mask: индексы массива в матрице wxh, перенумерованные слева направо сверхувниз]
 * Date: 31.01.2020
 * Time: 8:06
 */

namespace osr;

use \Ksnk\scaner\traitHandledClass;

class osr_micro
{
    use traitHandledClass;
    /**
     * габаритные размеры распознаваемого символа
     * @var array
     */
    var $font = [];

    /**
     * набор изображений символов, с которыми сравниваем картинку
     * @var array
     */
    var $digits = [];

    /**
     * обслуживание распознаваемой картинки
     * @var null
     */
    var $img = null,
        $img_filename = '',
        $img_width = 0,
        $img_height = 0;

    /**
     * операция загрузки словаря
     * @param $json_file
     * @return osr_micro
     * @throws \Exception
     */
    function vocabular($json_file)
    {
        $json_file = preg_replace('/^~/', __DIR__, $json_file);
        if (!is_readable($json_file)) {
            $this->error('не найден словарь `%s` ', $json_file);
            return $this;
        }

        $data = json_decode(file_get_contents($json_file), true);
        if (!empty($data) && isset($data['version']) && $data['version'] == '1.0') {
            $this->font['width'] = $data['width'];
            $this->font['height'] = $data['height'];
            foreach ($data['data'] as $d) {
                $d['mask'] = array_flip($d['mask']);
                $this->digits[] = $d;
            }
        } else {
            $this->error('поврежденные данные в словаре `%s`', $json_file);
            return $this;
        }
        return $this;
    }

    /**
     * предьявить новую картинку для распознавания
     * @param $img_filename
     * @return mixed
     * @throws \Exception
     */
    function newImage($img_filename)
    {
        $this->destroyImage();
        $this->img_filename = $img_filename;
        if (!is_readable($img_filename)) {
            $this->error('отсутствует файл `%s`', $img_filename);
            return false;
        }

        $this->img = imagecreatefrompng($img_filename);
        if (false === $this->img) {
            $this->error('битая картинка `%s`', $img_filename);
            return false;
        }

        $this->img_width = imagesx($this->img);//x
        $this->img_height = imagesy($this->img);//y
        return true;
    }

    /**
     * почистить хвосты
     */
    function destroyImage()
    {
        if (!empty($this->img)) {
            imagedestroy($this->img);
            $this->img = null;
        }
    }

    function __destruct()
    {
        $this->destroyImage();
    }

    private function scansymbol($m, &$same = null)
    {
        $same = 0;
        $symbol = '';
        foreach ($this->digits as $e) {
            // накладываем маску на матрицу
            $csame = 0;
            $cdiff = 0;
            foreach ($e['mask'] as $k => $v) {
                if (isset($m[$k]))
                    $csame++;
                else
                    $cdiff++;
            }
            $xsame = $csame / (count($m));
            if ($same < $xsame) {
                $same = $xsame;
                $symbol = $e['simbol'];
            }
        }
        return $symbol;
    }

    /**
     * выковыриваем изображение символа, на одной строке,
     * примерно в этих габаритах
     *
     * @param $x
     * @param $y - стартовая точка
     * @param int $depth
     * @param int $width - размеры области, в которой ожидаем строку для распознавания
     * @return string
     * @throws \Exception
     */
    function recognize($x, $y, $depth = 0, $width = 0)
    {
      if(empty($this->font))
        return $this->error('Не загружен словарь');
      // корррекция габаритов области поиска
        if (empty($depth)) $depth = $this->img_height - 1 - $y;
        if (empty($width)) $width = $this->img_width - 1 - $x;

        // суммируем все пиксели в 2 линии. Простенько, но для яндекса подходит
        $linex = [];
        $liney = [];
        for ($j = $y; $j < $y + $depth; $j++) {
            $liney[$j] = 0;
        }
        for ($i = $x; $i < $x + $width; $i++) {
            $linex[$i] = 0;
            for ($j = $y; $j < $y + $depth; $j++) {
                // черное на белом ?
                $pix = 0xFF & imagecolorat($this->img, $i, $j);
                if ($pix < 0x40) {
                    //радикально черный
                    $linex[$i] += 4;
                    $liney[$j] += 4;
                }
                if ($pix < 0x80) { //серость
                    $linex[$i] += 1;
                    $liney[$j] += 1;
                } // else - фон
            }
        }
        // в итоге нарезали картинку по границам символов
        // выковыриваем символы поматрично
        //
        $word = '';

        do {
            // пропускаем фон сначала
            while (isset($linex[$x]) && $linex[$x] == 0) {
                $x++;
            }
            if (!isset($linex[$x])) break;
            while (isset($liney[$y]) && $liney[$y] == 0) {
                $y++;
            }
            $matrix = [];
            for ($i = $x; $i < $x + $this->font['width']; $i++) {
                if (!isset($linex[$i])) break 2;
                if ($linex[$i] == 0) break;
                for ($j = $y; $j < $y + $this->font['height']; $j++) {
                    $pix = 0xFF & imagecolorat($this->img, $i, $j);
                    if ($pix < 0x80) {
                        $matrix[($j - $y) * $this->font['width'] + ($i - $x)] = 1;
                    }
                }
            }
            $x = $i;
            $same = 0;
            if(empty($matrix)) continue;
            $simbol = $this->scansymbol($matrix, $same);
            if ($same < 0.9) {
                $this->out(" не распознано, похоже на %s (%s)\n%s\n", $simbol, $same, ',{"simbol":"x","mask":[' . implode(",", array_keys($matrix)) . ']}');
            }
            $word .= $simbol;
        } while (true);

        return $word;
    }

}