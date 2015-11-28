<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 24.11.15
 * Time: 18:53
 */

/**
 * список задач для паучка
 * Class joblist
 */
class joblist extends base
{

    var $list = array(), $scenario;

    private function append($type, $args)
    {
        $this->list[] = array($type, $args);
        return $this;
    }

    function append_scenario()
    {
        $this->append('scenario', func_get_args());
        return $this;
    }

    /**
     * @return bool
     */
    function donext()
    {
        if (count($this->list) == 0)
            return false;
        $task = array_shift($this->list);
        $scn = array_shift($task[1]);
        switch ($task[0]) {
            case "scenario":
                if (method_exists($this->scenario, $scn)) {
                    call_user_func_array(array($this->scenario, $scn), $task[1]);
                }
                break;
        }
        return true;
    }

}