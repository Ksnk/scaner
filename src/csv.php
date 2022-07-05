<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 22.11.2019
 * Time: 17:10
 */

namespace Ksnk\scaner;

/**
 * Class csv
 * @package Ksnk\scaner
 * читатель csv, возможно в не той кодировке, возможно с не теми делимитерами
 * и не теми обрамлениями, сам все найдет и прочитает как надо.
 * кодировки понимает только cp1251 и utf-8, но в моей реальности этого хватает.
 */
class csv extends scaner
{

    const BOM = "\xEF\xBB\xBF"; // utf-8
    const LE16 = "\xFF\xFE"; // utf-16LE на будущее. Армагедон не за горами ?

    var $delim = ';',
        $quote = '"',
        $hasbom = 0,
        $newline = '',
        $detected = false,
        $encoding = 'windows-1251',
        $_reg;

    function param($array)
    {
        foreach ($array as $k => $v) {
            if (isset($this->{$k}))
                $this->{$k} = $v;
        }
    }

    /**
     * Упрощенная генерация класса для распарсинга строки
     * @param $string
     * @param array $param
     * @return csv
     *
     * ключи параметра
     * 'delim','quote','encoding','detected'
     * Для вставки одного столбца из Excel -  [delim:"\t",]
     */
    static function csvStr($string, $param = [])
    {
        $class = new self();
        if (!preg_match('/[\n\r]$/', $string)) $string .= "\n";
        $class->newbuf($string);
        if (!isset($param['encoding'])) {
            $param['encoding'] = strtolower(mb_detect_encoding($string, 'utf-8', true))
                ? 'utf-8' : 'cp1251';
        }
        $class->param($param);
        return $class;
    }

    /**
     * функция выдает класс csv с правильно определенной кодировкой и делимитерами
     * @param $nameresource
     * @param $headers - количество строк на заголовок.
     * @return csv
     */
    static function getcsv($nameresource, $headers = 1)
    {
        $class = new self();
        $class->newhandle($nameresource);

        $class->prepare();
        $buf = $class->getbuf();
        if ($encoding = strtolower(mb_detect_encoding($buf, 'utf-8', true))) {
            $class->param(['encoding' => strtolower($encoding)]);
        }
        // первые 2-3 символа - не БОМ?
        if (preg_match('~^(' . self::BOM . '|' . self::LE16 . ')~', $buf, $m)) {
            if ($m[0] == self::BOM) {
                $class->param(['encoding' => 'utf-8']);
                $class->hasbom = 3;
                $class->position(3);
            } else {
                $encoding = 'utf-16RLE';//todo сделать ?
                $class->hasbom = 2;
                $class->position(2);
            }
        }
        $the_start = $class->getpos();
        foreach (['"', "'"] as $quote) foreach ([',', ";", "\t"] as $delim) {
            $start = $the_start;
            $cols = 0;
            $rows = 0;
            $row = 0;
            $line = str_replace('"', $quote,
                str_replace(',', $delim,
                    '~(?:(")(?:[^"]|"")*"|.*?)(,|(\r\n|\n|\r|$))()~s'));
            //if ($encoding == 'utf-8') $line .= 'u';
            while (preg_match($line, $buf, $m, PREG_OFFSET_CAPTURE, $start)) {
                if ($start != $m[0][1]) {
                    // пропустили кусок текста, проблемы, однако
                    continue 2;
                }
                if ($m[1][1] > 0 && $quote != $m[1][0]) {
                    // не подходит QUOTE
                    continue 2;
                }
                if ($m[3][1] > 0) {
                    // so newline
                    if ($cols == 0 && $row == 0) {
                        // количество столбцов разное
                        continue 2;
                    } else if ($cols == 0) $cols = $row;
                    else if ($cols !== $row) {
                        // количество столбцов разное
                        continue 2;
                    }
                    $row = 0;
                    $start = $m[4][1];
                    if (++$rows > 6 || $start > scaner::BUFSIZE >> 1 || $start >= $class->finish) {
                        // проверка пройдена, заканчиваем
                        $class->param([
                            'delim' => $delim,
                            'quote' => $quote,
                            'encoding' => $encoding,
                            'detected' => true
                        ]);
                        break 3;
                    }
                    continue;
                } else {
                    $start = $m[4][1];
                    if ($delim != $m[2][0]) {
                        // не подходит DELIM.
                        continue 2;
                    }
                }
                $row++;
            }
        }
        return $class;
    }


    function nextRow()
    {
        $key = '';
        $last = $this->getpos();
        if (empty($this->_reg)) {
            $this->_reg0 = str_replace('"', $this->quote,
                str_replace(',', $this->delim, '~((")(?:[^"]|"")*"|.*?)(,|(\r\n|\n|\r|$))()~s'));
            $this->_reg = str_replace('"', $this->quote,
                str_replace(',', $this->delim, '~(.*?)(,|(\r\n|\n|\r|$))()~s'));
            if ($this->encoding == 'utf-8') {
                $this->_reg0 .= 'u';
                $this->_reg .= 'u';
            }
        }
        $this->prepare(false);
        if ($this->finish - $this->filestart - $this->start <= 0) return [];
        $cols = [];
        $m = [];
        while (true) {
            $col = '';
            if (preg_match($this->_reg0, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start)) {
                $col = preg_replace(['/^' . $this->quote . '/', '/' . $this->quote . '$/', '/' . $this->quote . $this->quote . '/'], ['', '', $this->quote], $m[1][0]);
                array_shift($m);
            } else // иногда, из за большого объема данных в поле, регулярка не выедает все в первый раз
                if (isset($this->buf[$this->start]) && $this->buf[$this->start] == $this->quote ||
                    preg_match($this->_reg, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start)) {
                    if (empty($m)) {
                        // попытка прочитать поврежденный текст
                        $this->start++;
                        $col = '';
                        while (preg_match('~[^' . $this->quote . ']*' . $this->quote . '()~s' . $key, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start)) {
                            $this->start = $m[1][1];
                            if ($this->buf{$this->start} == $this->quote) {
                                $this->start++;
                                $col .= $m[0][0];
                            } else {
                                $col .= mb_substr($m[0][0], 0, -1, '8bit');
                                break;
                            }
                        }
                        preg_match('~()(' . $this->delim . '|(\r\n|\n|\r))()~su', $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start);
                    } else {
                        $col = $m[1][0];
                    }
                }
            if ($this->encoding != 'utf-8') $col = iconv($this->encoding, 'utf-8//ignore', $col);
            $cols[] = $col;
            if (empty($m)) {
                break;
            } else {
                $this->start = $m[4][1];
                if ($m[3][1] >= 0) {
                    break;
                }
            }
            $m = [];
        }
        if ($last == $this->getpos()) return []; // иногда глючит регулярка и тормозится в конце кривого файла

        return $cols;
    }
}
