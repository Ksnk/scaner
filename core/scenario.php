<?php

namespace Ksnk\scaner;

class scenario extends base
{
    /**
     * @property scaner scaner
     * @property spider spider
     * @var joblist
     */
    var $joblist = null;

    /**
     * область сохранения данных
     * @var null
     */
    var $data = [];

    function __construct($joblist = null)
    {
        parent::__construct();
        $this->joblist = $joblist;
    }

    /**
     * переходник для указателя вывода
     * @param $streamcns
     * @param string $parameter
     */
    function outstream($streamcns, $parameter = '')
    {
        $this->joblist->outstream($streamcns, $parameter);
    }

    /**
     * Автоматическая обработка библиотечных классов, this->scaner, this->spider и так далее
     * @param $name
     * @return null
     */
    function __get($name)
    {
        // switch ($name) {
        //     default:
        $c = $name;
        if (!class_exists($c) && class_exists(__NAMESPACE__ . '\\' . $c)){
            $c = __NAMESPACE__ . '\\' . $name;
        }
        if (class_exists($c)){
            $this->$name = new $c();
        } else
            return null;
        // }
        return $this->$name;
    }

}