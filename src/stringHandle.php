<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 05.05.2018
 * Time: 23:57
 */

namespace Ksnk;


class stringHandle
{

    // буфер для хранения строки
    public $buf='';

    function construct($buf){
        if (is_array($buf)) { // source from `file` function
            $buf = implode("\n", $buf);
        }
    }

    public function pos ($pos) {

    }

    public function close () {
    }

}