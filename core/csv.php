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
 * читатель csv, возможно в не той кодировке, возможно с нетеми делимитерами и нетеми обрамлениями
 */
class csv extends scaner
{

    const BOM = "\xEF\xBB\xBF"; // utf-8
    const LE16 = "\xFF\xFE"; // utf-16

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
     * функция выдает класс csv с правильно определенной кодировкой и делимитерами
     * @param $nameresource
     * @param $headers - количество строк на заголовок.
     * @return csv
     */
    static function getcsv($nameresource, $headers = 1)
    {
        ini_set("pcre.backtrack_limit", "2000000");
        ini_set("pcre.recursion_limit",	"200000");
        ini_set("pcre.jit",0);

        $class = new self();
        $class->newhandle($nameresource);

        $class->prepare();
        $buf = $class->getbuf();
        if ($encoding = mb_detect_encoding($buf, 'utf-8', true)) {
            $encoding = 'utf-8';
        } else {
            $encoding = 'cp1251';
        }
        // первые 2-3 символа - не БОМ?
        if (preg_match('~^(' . self::BOM . '|' . self::LE16 . ')~', $buf, $m)) {
            if ($m[0] == self::BOM) {
                $encoding = 'utf-8';
                $class->hasbom=3;
                $class->position(3);
            } else {
                $encoding = 'utf-16RLE';
                $class->hasbom=2;
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
                    '~(?:(")(?:[^"]|"")*"|.*?)(,|(\r\n|\n|\r))()~s'));
            //if ($encoding == 'utf-8') $line .= 'u';
            while (preg_match($line, $buf, $m, PREG_OFFSET_CAPTURE, $start)) {
                if ($start != $m[0][1]) {
                    // пропустили кусок текста, проблемы, однако
                    continue 2;
                }
                if ($m[1][1]>0 && $quote != $m[1][0]) {
                    // не подходит QUOTE
                    continue 2;
                }
                if (!empty($m[3])) {
                    // so newline
                    if ($cols == 0) $cols = $row;
                    else if (($cols == 0 && $row == 0) || $cols !== $row) {
                        // количество столбцов разное
                        continue 2;
                    }
                    $row = 0;
                    if (++$rows > 6) {
                        // проверка пройдена, заканчиваем
                        $class->param([
                            'delimiter' => $delim,
                            'quote' => $quote,
                            'encoding' => $encoding,
                            'detected' => true
                        ]);
                        break 3;
                    }
                    $start = $m[3][1] + mb_strlen($m[3][0], '8bit');
                    continue;
                } else {
                    $start = $m[2][1] + 1;
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
        if (empty($this->_reg)) {
            $this->_reg = str_replace('"', $this->quote,
                str_replace(',', $this->delim, '~(.*?)(,|(\r\n|\n|\r))()~s'));
            if ($this->encoding == 'utf-8') $this->_reg .= 'u';
        }
        $this->prepare(false);
        if($this->finish-$this->filestart-$this->start<=0) return [];
        $cols = [];
        $m=[];
        while (
            $this->buf{$this->start}==$this->quote
            ||
            preg_match($this->_reg, $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start)) {
            if(empty($m)){
                // попытка прочитать поврежденный текст
                $this->start++;$state=0;
                do {
                    if($state>2) break;
                    if (!preg_match('~((?:' . $this->quote . '' . $this->quote . '|[^' . $this->quote . '])*)' . $this->quote . '(' . $this->delim . '|(\r\n|\n|\r))()~s', $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start)) {
                        if ($state == 0) {
                            $this->prepare();
                            $state++;
                            continue;
                        }
                    }
                    if(empty($m))
                        preg_match('~(*?)(' . $this->delim . '|(\r\n|\n|\r))()~s', $this->buf, $m, PREG_OFFSET_CAPTURE, $this->start);
                    if ($this->start != $m[0][1]) {
                        // пропустили кусок текста, проблемы, однако
                        $this->prepare();$state++;
                        continue;
                    }
                    $col = preg_replace('/' . $this->quote . $this->quote . '/', $this->quote, $m[1][0]);
                    break;
                } while(true);
            } else {

                $col = $m[1][0];
            }
            if ($this->encoding != 'utf-8') $col = iconv($this->encoding, 'utf-8//ignore', $col);
            $cols[] = $col;
            $this->start = $m[4][1];
            if ($m[3][1] >= 0) {
                break;
            }
            $m=[];
        }
        return $cols;
    }
}
