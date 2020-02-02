<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 21.12.2019
 * Time: 10:44
 */

namespace Ksnk\scaner;


class editor
{

    var $trace = [];
    var $filename = '';

    function __construct($filename = '')
    {
        $this->filename = $filename;
    }

    function longedit($pos, $len, $newval)
    {
        echo $pos, ' ', $len, ' ', $newval, "\n";
        $this->trace[] = [$pos, $len, $newval];
    }

    function update($copytosrc=false)
    {
        $src = fopen($this->filename, 'r+');
        $dst = fopen($this->filename . '(1)', 'w+');
        $start = 0;
        foreach ($this->trace as $t) {
            stream_copy_to_stream($src, $dst, $t[0] - $start);
            $start = $t[0] + $t[1];
            fseek($src, $t[1], SEEK_CUR);
            fwrite($dst, $t[2]);
        }
        stream_copy_to_stream($src, $dst);
/*              rewind ($dst);rewind ($src);
              $x=stream_copy_to_stream($dst,$src);
              echo $x;*/
        fclose($src);
        fclose($dst);
        if($copytosrc) {
            $src = fopen($this->filename, 'w+');
            $dst = fopen($this->filename . '(1)', 'r+');
            stream_copy_to_stream($dst, $src);
            fclose($src);
            fclose($dst);
        }
    }

}