<?php

namespace Ksnk\scaner;

use \Ksnk\scaner\base;

class scenario extends base
{
    /**
     * @var joblist
     * @property scaner scaner
     * @property spider spider
     */
    var $joblist;

    function __construct($joblist = null)
    {
        parent::__construct();
        $this->joblist = $joblist;
    }

    /**
     * Просто результат операции, чтобы было что выковыривать.
     * @var
     */
    var $result;

    function __get($name)
    {
        switch ($name) {
/*            case 'scaner':
                $this->$name = new scaner();
                break;
            case 'spider':
                $this->$name = new spider();
                break;*/
            default:
                if(class_exists(__NAMESPACE__.'\\'.$name)) {
                    $c=__NAMESPACE__.'\\'.$name;
                    $this->$name = new $c();
                } else
                    return null;
        }
        return $this->$name;
    }

}