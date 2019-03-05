<?php

namespace Ksnk\scaner;

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
            case 'scaner':
                $this->$name = new scaner();
                break;
            case 'spider':
                $this->$name = new spider();
                break;
            default:
                return null;
        }
        return $this->$name;
    }

}