<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.05.2018
 * Time: 2:31
 */

namespace Ksnk\scaner;


class handleGZfile extends handleFile
{

    public function __construct($handle)
    {
        parent::__construct();
        $_handle = fopen($handle, "rb");
        // $x = unpack("S", fread($_handle, 2));
        // if($x[1]!=0x8b1f) return ;
        fseek($_handle, filesize($handle) - 4);
        $x = unpack("L", fread($_handle, 4));
        $this->finish = $x[1];
        fclose($_handle);
        $this->handle = gzopen(
            $handle, 'r'
        );
    }

}