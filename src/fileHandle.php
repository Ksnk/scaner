<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 05.05.2018
 * Time: 22:14
 */

namespace Ksnk;

/**
 * Class fileHandle - класс - файловый хендл для
 * @package Ksnk
 */
class fileHandle implements scanerHandle
{
    /**
     * @var resource
     */
    private $handle=null;

    public function pos ($pos) {

    }

    public function close () {
        if(!empty($this->handle)) {
            fclose($this->handle);
            $this->handle=null;
        }
    }

}