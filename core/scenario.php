<?php


class scenario extends base {
    /**
     * @var joblist
     * @property scaner scaner
     */
    var $joblist;

    function __construct($joblist=null){
        $this->joblist=$joblist;
    }

    /**
     * Просто результат операции, чтобы было что выковыривать.
     * @var
     */
    var $result;

    function __get($name){
        switch ($name){
            case 'scaner':
                $this->$name=new scaner();
                break;
            default:
                return null;
        }
        return $this->$name;
    }

}