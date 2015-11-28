<?php

/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 26.11.15
 * Time: 11:55
 */
class base
{

    function __construct($opt = array())
    {
        $this->init($opt);
    }

    function init($opt = array())
    {
        if (!empty($opt)) {
            foreach ($opt as $k => $v) {
                if (property_exists($this, $k))
                    $this->$k = $v;
            }
        }
    }

}