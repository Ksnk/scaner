<?php

/**
 * Базовй класс. Зачем он нужен - пока не представляю...
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
