<?php

namespace Ksnk\scaner;

/**
 * Базовый класс. Зачем он нужен - пока не представляю...
 */
class base
{
    const
        OUTSTREAM_PRE='text.pre',
        OUTSTREAM_HTML='html',
        OUTSTREAM_HTML_FRAME='html.frame';

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
