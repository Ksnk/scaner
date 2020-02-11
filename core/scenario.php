<?php

namespace Ksnk\scaner;

class scenario extends base
{
    /**
     * @property scaner scaner
     * @property spider spider
     * @var joblist
     */
    var $joblist;

    function __construct($joblist = null)
    {
        parent::__construct();
        $this->joblist = $joblist;
    }

    function outstream($streamcns,$parameter=''){
        $this->joblist->outstream($streamcns,$parameter);
    }

    /**
     * Просто результат операции, чтобы было что выковыривать.
     * @var
     */
    var $result;

    function __get($name)
    {
        switch ($name) {
            default:
                if(class_exists($name)) {
                    $c=$name;
                    $this->$name = new $c();
                } else if(class_exists(__NAMESPACE__.'\\'.$name)) {
                    $c=__NAMESPACE__.'\\'.$name;
                    $this->$name = new $c();
                } else
                    return null;
        }
        return $this->$name;
    }

}