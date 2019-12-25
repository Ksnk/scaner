<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 29.11.15
 * Time: 17:25
 */
use
    \Ksnk\scaner\joblist,
    \Ksnk\scaner\x_parser;

ini_set('display_errors', 1);
error_reporting(E_ALL);

define('USE_NAMESPACE', 'Ksnk\\scaner\\');
define('TEMP_DIR', 'temp/');

$baseDir = dirname(__FILE__);

if (preg_match('/\b(\w+\.phar)$/', __FILE__, $m))
    define('INDEX_DIR', "phar://" . $m[1]);
else
    define('INDEX_DIR', $baseDir);

include_once INDEX_DIR . "/autoload.php";

function parseParameters($noopt = array())
{
    $result = array();
    $params = $GLOBALS['argv'];
    // could use getopt() here (since PHP 5.3.0), but it doesn't work relyingly
    reset($params);
    while (list($tmp, $p) = each($params)) {
        if ($p{0} == '-') {
            $pname = substr($p, 1);
            $value = true;
            if ($pname{0} == '-') {
                // long-opt (--<param>)
                $pname = substr($pname, 1);
                if (strpos($p, '=') !== false) {
                    // value specified inline (--<param>=<value>)
                    list($pname, $value) = explode('=', substr($p, 2), 2);
                }
            }
            // check if next parameter is a descriptor or a value
            $nextparm = current($params);
            if (!in_array($pname, $noopt) && $value === true && $nextparm !== false && $nextparm{0} != '-') list($tmp, $value) = each($params);
            $result[$pname] = $value;
        } else {
            // param doesn't belong to any option
            $result[] = $p;
        }
    }
    return $result;
}

if ('' == ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

if (PHP_SAPI === 'cli') {
    $par = array(
        'input_encoding' => 'cp1251',
        'internal_encoding' => 'UTF-8',
        'output_encoding' => 'UTF-8//ignore',
    );
    if (empty($_ENV) || isset($_ENV['PROMPT']))
        $par['output_encoding'] = 'cp866//ignore';
    ob_start('ob_iconv_handler');
    foreach ($par as $k => $v) ini_set($k, $v);

    $parameters = parseParameters();
    $mask = UTILS::masktoreg('scenario/*/*_scenario.php');
    $urlp=pathinfo(__FILE__);
    if($urlp['extension']=='phar')
        $result = UTILS::scanPharFile(__FILE__, $mask);
    $data = array();
    \UTILS::findFiles($mask, function ($sc) use (&$tags, &$filter, &$tag, &$data) {
        $classname = USE_NAMESPACE . basename($sc, '.php');
        $res = x_parser::getParameters('', $classname, realpath($sc));
        foreach ($res[$classname] as $method => $val) {
            if (preg_match('/^do_/', $method)) {
                $tags = array_merge($tags, $res['tags']);
                break;
            }
        }
        if (!in_array($tag, $res['tags'])) {
            // var_export($res['tags']);
            return 0;
        }
        if (empty($res) || empty($res[$classname]) || !preg_match($filter, $classname)) {
            return 0;
        }
        foreach ($res[$classname] as $method => $val) {
            if (!preg_match('/^do_(.*)$/', $method))
                continue;

            $res = '';
            foreach ($val['param'] as $name => $par) {
                $par['class'][] = "form-control";
                $par['parname'] = $name;

                $res .= x_parser::createInput($par, \UTILS::val($_SESSION, 'form', []));
            }
            $data[] = array(
                'title' => $val['title'],
                'method' => htmlspecialchars($classname . '::' . $method),
                'res' => trim($res),
                'sc' => htmlspecialchars($sc),
                'anchor' => urlencode($val['title']),
            );
        }
        return null;
    });
    $scenariofiles = array_merge(
        glob(INDEX_DIR . '/scenario/*/*_scenario.php'),
        $result
    );
    //print_r($scenariofiles);
    // -- Ksnk\scaner\monitoring_scenario::do_renumber
    try {
        $action = UTILS::val($parameters,1);
        list($class, $method, $empty) = explode('::', $action . '::::', 3);
        $res = false;
        $dir = '';
        foreach ($scenariofiles as $sc) {
            $classname = basename($sc, '.php');
            $resx = x_parser::getParameters('', $classname, $sc);
            // print_r($resx);
            if (isset($resx[$class])) {
                $dir = $sc;
                $res = $resx;
                break;
            }
        }
        if ($res) {
            //  print_r($res);
            $top = array('class' => $class, 'method' => $method, 'dir' => $dir);
            $param = array();

            foreach ($res[$class][$method]['param'] as $name => $par) {
                if (isset($parameters[$name])) {
                    $param[] = $parameters[$name];
                } else if (isset($par['default'])) {
                    $param[] = $par['default'];
                } else {
                    echo 'Missing parameter ' . $name;
                    exit;
                }
            }
            $a->joblist->append_scenario($top, $param);
        }

    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
        print_r($parameters);
    }

    $a->joblist->donext();
}
