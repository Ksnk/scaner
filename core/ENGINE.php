<?php
/**
 * статический класс - преставитель CMS в космосе.
 * ----------------------------------------------------------------------------------
 * $Id: X-Site cms (2.0, LapsiTV build), written by Ksnk (sergekoriakin@gmail.com),
 * ver: , Last build: 1505081407
 * GIT: $
 * ----------------------------------------------------------------------------------
 * License MIT - Serge Koriakin - Jule 2012
 * ----------------------------------------------------------------------------------
 */

/*  --- point::ENGINE_top --- */

/**
 * Class xData -data-holder,
 * базовый класс для хранителя данных для шаблонов
 */
class xData implements  Iterator {

    /**
     * @var array
     */
    static $items=array();

    /**
     * система кэширования однотипных данных. Все данные различаются полем ID.
     * @static
     * @param $class
     * @param $id
     * @param array $data
     * @return mixed
     */
    static function get($class,$id,$data=array()){
        if(!isset(self::$items[$class]))
            self::$items[$class]=array();
        if(is_array($id)) {
            if(!isset(self::$items[$class][$id['id']]))
                self::$items[$id['id']]=new $class($id,$data);
            return self::$items[$class][$id['id']];
        } else if(!isset(self::$items[$class][$id])) {
            self::$items[$class][$id]=new $class($id,$data);
        }
        return self::$items[$class][$id];
    }

    protected $data=array();
    private $def='',$eoa=false;

    function getData(){
        return $this->data;
    }

    function __construct($data,$def=''){
        foreach($data as $k=>$v){
            if(is_array($v))
                $this->data[$k]=new self($v,$def);
            else
                $this->data[$k]=$v;
        }
        $this->def=$def;
    }

    protected function  &resolve($name){
        if(!array_key_exists($name,$this->data))
            $this->data[$name]=$this->def;
        return $this->data[$name];
    }

    function &__get($name){
        if(array_key_exists($name,$this->data))
            return $this->data[$name];
        else {
            $x=$this->resolve($name);
            return $x;
        }
    }
    public function __set($name, $value)
    {
        // echo "Setting '$name' to '$value'\n";
        $this->data[$name] = $value;
    }
    // итератор
    function rewind() {
        $this->eoa=count($this->data==0);
        reset($this->data);
    }

    function current() {
        return current($this->data);
    }

    function key() {
        return key($this->data);
    }

    function next() {
        return $this->eoa=(false!==next($this->data));
    }

    function valid() {
        return $this->eoa;
    }

}

/**
 * @method static bool has_rights
 * @method static run
 */
class ENGINE
{

    private static $class_list = array();
    private static $class_alias = array();
    private static $SUBDIR='';

    /*  --- point::ENGINE_header --- */

    /** @var Memcache null */
    private static $memcached = null;

    

    static private $options = array(); // пары: имя-значение
    static private $transport = array(); // пары: имя - механизм сохранения
    static public $default_transport = '';
    static private $transports = array(); // строка -> объект

    

    static public $session_started = false;

    

    static $cookie_name = 'testing';

    

    /** @var xDatabaseLapsi */
    static private $db = null;

    

    static $start_time ;

    

    static private $_logger_table = false;

    

    static $url_par = null;
    static $url_path = null;

    /**
     * простейший строковый шаблон для вывода минимально параметризованных строк.
     * a-la YII
     * @static
     * @param $msg
     * @param $arg
     * @return mixed
     */
    static public function _t($msg, $arg)
    {
        if (is_array($arg) && count($arg) > 0) {
            foreach ($arg as $k => $v) {
                if (is_object($v)) {
                    $v = get_class($v);
                }
                $msg = str_replace($k, $v, $msg);
            }
        }
        return $msg;
    }

    /**
     * функция вывода сообщения об ошибке. Управляется функция с помошью параметров
     * --error.handler и error.format
     * стандартный handler - printf
     * для ajax
     *
     * @param string $msg - собственно собщение
     * @param array $par - параметры для функции _t
     * @param array $backtrace - параметры для вывода backtrace
     * @return mixed
     */
    static function error($msg, $par = array(), $backtrace = array())
    {
        if (!error_reporting()) return;
        $error_handler = self::option('error.handler', 'echo');
        $error_format = self::option('error.format'
            , "<!--xxx-error-{backtrace}\n{msg}\n-->");
        $msg = self::_t($error_format, array(
            '{backtrace}' => self::backtrace($backtrace),
            '{msg}' => self::_t($msg, $par)
        ));
        if ($error_handler == 'echo' || $error_handler == 'printf')
            echo $msg;
        else
            call_user_func($error_handler, self::_t($error_format, array(
                '{backtrace}' => self::backtrace($backtrace),
                '{msg}' => self::_t($msg, $par)
            )));
        return false;
    }

    /*
    if (array_key_exists('error', self::$interface)) {
            return call_user_func(self::$interface['error'], $msg, $args, $rule);
        }

        $error = ENGINE::option('page.error');
        if (!empty($error)) $error .= "<hr>\n";
        ENGINE::set_option('page.error', $error . self::_t($msg, $args));
        //echo self::_t($msg, $args) . " <br>\n";
        return null;
    }
*/

    /**
     * выдать имя алиаса по имени класса
     * @static
     * @param $object
     * @return mixed|string
     */
    static function get_class($object)
    {
        if (method_exists($object, 'get_alias')) {
            $classname = $object->get_alias();
        } else {
            $classname = get_class($object);
            $key = array_search($classname, self::$class_alias);
            if ($key !== false) {
                $classname = $key;
            }
        }
        return $classname;
    }

    /**
     * place object in factory storage with selected name
     * @param $name
     * @param $obj
     */
    static function setObj($name, &$obj)
    {
        self::$class_list[$name] = $obj;
    }

    static function callFirst($method, $par)
    {
        foreach (self::$class_list as $k => &$v)
            if (method_exists($v, $method)) {
                if (!is_null($x = call_user_func_array(array(&$v, $method), $par)))
                    return $x;
            }
        return null;
    }

    static function callAll($method, $par = '')
    {
        $result = false;
        foreach (self::$class_list as $k => &$v)
            if (method_exists($v, $method)) {
                $result = $result || call_user_func(array($v, $method), $par);
            }
        return $result;
    }

    /**
     * @param $name
     * @return string - aliaced name of class
     */
    static function getAliace($name)
    {
        if (empty(self::$class_alias)) {
            self::$class_alias = ENGINE::option('engine.aliaces');
        }
        if (isset(self::$class_alias[$name]))
            return self::$class_alias[$name];
        return $name;
    }

    /**
     * get an object from alias record
     * @static
     * @param $name
     */
    static function getObj($name, $par = null)
    {
        $dir = '';
        if (($pos = strrpos($name, '/')) !== false) {
            $dir = substr($name, 0, $pos + 1);
            $name = substr($name, $pos + 1);
        }
        self::$SUBDIR=$dir;
        if (!isset(self::$class_list[$name])) {
            $class = self::getAliace($name);
            if (class_exists($class)) {
                self::$class_list[$name] = new $class($par);
            } else {
                if (!error_reporting()) return null; else
                if ($name == $class) {
                    self::error(
                        'class `{class}` not found in (cwd:{dir})',
                        array('{class}' => $class, '{dir}' => getcwd())
                    );
                } else {
                    self::error(
                        'class `{class}({name})` not found in (cwd:{dir})',
                        array(
                            '{name}' => $name,
                            '{class}' => $class,
                            '{dir}' => getcwd()
                        )
                    );
                }
                return null;
            }
        }
        return self::$class_list[$name];
    }

    /**
     * вызвать класс-метод.
     * Если класс из массива - вызывать объект из фабрики.
     *
     * @param callable $func
     * @param null|array $args
     * @param string $error_rule
     * @return mixed
     */
    static function exec(&$func, $args = array(), $error_rule = '')
    {
        if (is_array($func) && is_string($func[0])) {
            $func[0] = self::getObj($class = $func[0]);
        }
        if (is_callable($func)) {
            if (is_array($args))
                return call_user_func_array($func, $args);
            else
                return call_user_func($func);
        }

        if (empty($func)) {
            // попытка выковырять параметры со стека вызовов
            $db = debug_backtrace();
            switch ($db[1]['function']) {
                case '__callStatic':
                    $func = $db[1]['args'][0];
                    break;
                default:
                    $func = "I dont know how to dig for function name.";
            }
        }

        if (is_array($func)) {
            if (empty($class)) {
                $class = get_class($func[0]);
            }
            ENGINE::error('unresolved callable {{class}}({{real}})->{{method}}', array('{{class}}' => $class, '{{method}}' => $func[1], '{{real}}' => $class), $error_rule);
        } else
            ENGINE::error('unresolved callable {{function}}', array('{{function}}' => $func), $error_rule);
        return '';
    }

    static function _(&$val, $def = '')
    {
        return empty($val) ? $def : $val;
    }

    static function template($name, $method, $par = array())
    {
        static $cache;
        if (is_array($name)) {
            if ($method == '_') {
                $method = '_' . $name[1];
                $name = $name[0];
            } else {
                $name = $name[0];
            }
        }
//$par = array_merge($this->par, $par);
        if (empty($cache[$name])) {
            if (!class_exists($name)) {
                ENGINE::error('method {{method}} not found',
                    array('{{method}}' => $method . '::' . $name),
                    array('function' => 'template', 'count' => 4)
                );
                return '';
            }
            $cache[$name] = new $name();
        }
//debug($name,$par);
        if(!is_string($name)){
            self::debug('wtf?','~count|15');
        }
        $x = $cache[$name]->$method($par);
        return $x;
    }

    /*  --- point::ENGINE_body --- */

    /**
     * упрощенный доступ к memcached
     * @static
     * @param $key
     * @param null $value
     * @param int $time в секундах 28800 - 8 часов
     * @param array $tags - список дополнительных тегов.
     * @return bool|null
     */
    static function cache($key, $value = null, $time = null,$tags=null)
    {
        if(!class_exists('Memcache'))
            return false;
        if(is_null($time))$time=28800;
        if (empty(self::$memcached)) {
            self::$memcached = new Memcache;
            self::$memcached->connect('localhost', 11211);
            if (empty(self::$memcached)) return false;
        }
        if(!empty($tags)){
            foreach($tags as $tag){
                $x=self::$memcached->get($tag);
                if(empty($x)) self::$memcached->set($tag,$x=1);
                $key.='.'.$x;
            }
        }
        if (strlen($key) > 40) $key = md5($key);
        if (is_null($value)) {
            if (self::_($GLOBALS['SERVER_PORT']) == "8080") return false;
            return self::$memcached->get($key);
        } else {
            self::$memcached->set($key, $value, MEMCACHE_COMPRESSED,  $time);
        }
        return false;
    }
    

    /**
     * Выдать параметр по имени
     * @param string|array $name
     * @param mixed $default
     * @return mixed
     */
    static public function option($name = '', $default = '')
    {
        if (array_key_exists($name, self::$transport)) {
            $x = call_user_func(array(self::$transport[$name], 'get'), $name);
            if (is_null($x)) return $default;
            return $x;
        } else if (array_key_exists($name, self::$options)) {
            return self::$options[$name];
        } else {
            return $default;
        }
    }

    /**
     * @static
     * @param string|array $name
     * @param null $value
     * @param string|object $transport
     * @return bool|mixed
     */
    static public function set_option($name = '', $value = null, $transport = '')
    {
        if (is_array($name))
            $transport = $value;

        if (!empty($transport) && is_string($transport) && !isset(self::$transports[$transport])) {
            self::read_options($transport);
            $transport = self::$transports[$transport];
        }

        if (empty($name)) {
            foreach (self::$transports as $v) {
                if (is_callable(array($v, 'save')))
                    call_user_func(array($v, 'save'));
            }
            return true;
        } else if (is_array($name)) {
            foreach ($name as $k => $v) {
                if (!is_numeric($k))
                    self::set_option($k, $v, $transport);
                else
                    self::$transport[$v] = $transport;
            }
            return true;
        } else if (!empty($transport)) {
            self::$transport[$name] = $transport;
        }
        if (array_key_exists($name, self::$transport)) {
            $transport = self::$transport[$name];
        } else if (!empty(self::$default_transport)) {
            $transport = self::$default_transport;
        } else {
            if (!is_null($value)) {
                if (!is_array($value)
                    || !isset(self::$options[$name])
                    || !is_array(self::$options[$name])
                    || !array_key_exists($name, self::$options)
                ) {
                    self::$options[$name] = $value;
                } else {
                    array_merge_deep(self::$options[$name], $value);
                }
                return true;
            } else if (array_key_exists($name,self::$options))
                return self::$options[$name];
            else
                return null;
        }
        if(is_string($transport))
            class_exists($transport);
        if (!is_null($value)) {
            call_user_func(array($transport, 'set'), $name, $value);
            return true;
        } else {
            return call_user_func(array($transport, 'get'), $name);
        }
    }

    /**
     * Создать пакет параметров
     * @param string $transport
     */
    static public function read_options($transport = '')
    {
        if (!empty($transport)
            && !array_key_exists($transport, self::$transports)
        ) {
            // отделяем имя от параметра
            $x = explode('|', $transport . '|');
            $y=explode('~',$x[0].'~');
            if (is_callable(array('engine_options_' . $y[0], 'init')))
                self::$transports[$transport] =
                    call_user_func(array('engine_options_' . $y[0], 'init'), $y[1],$x[1]);
            else
                self::$transports[$transport] = 'engine_options_' . $y[0];
        }
    }

    static function slice_option($start)
    {
        $res = array();
        $reg = '#^' . preg_quote($start) . "(.*)$#";
        foreach (self::$options as $k => $v) {
            if (preg_match($reg, $k, $m)) {
                $res[$m[1]] = $v;
            }
        }
        return $res;
    }

    

    static public function _autoload($cls)
    {
        //static $include_path;
        $classes = ENGINE::option('engine.class_vocabular');
        if (is_array($classes) && isset($classes[$cls])) {
            $x = $classes[$cls];
            if (substr($x, -1, 1) == '/') {
                $x.=$cls;
            }
        } else {
            $x=$cls;
        }  ;
        $x.= '.php' ;
        if(!isset($include_path)) {
            $include_path=ENGINE::option('engine.include_path',array('.'));
        }
        foreach($include_path as $path) {
            if (is_readable($path .'/'.self::$SUBDIR. $x)){
                include_once($path .'/'.self::$SUBDIR. $x);
                return;
            }
        }
        if (function_exists("__autoload"))
        {
            __autoload($cls) ;
        }

    }
    

    static function start_session($log=true)
    {
        if (!self::$session_started) {
            $session_name = ENGINE::option('engine.sessionname');
            if (!empty($session_name)) {
                session_name($session_name);
            }
            session_set_cookie_params ( ENGINE::option('engine.session_lifetime',600),ENGINE::option('engine.session_path','/'),ENGINE::option('engine.session_domain',null));
            session_start();
            setcookie(session_name(),session_id(),time()+ENGINE::option('engine.session_lifetime',600),ENGINE::option('engine.session_path','/'),ENGINE::option('engine.session_domain',null));
            if($log){
            $log = array();
            foreach (array('REMOTE_ADDR', 'X-Forwarded-For', 'X-Real-IP') as $name) {
                if (isset($_SERVER[$name])) {
                    $log[$_SERVER[$name]] = $name;
                }
            }
            ENGINE::log(
                'Старт сессии.
IP:{{IP}}
REF:"{{HTTP_REFERER}}"
UA:"{{HTTP_USER_AGENT}}"', array('type' => 'session',
                    '{{IP}}' => implode(',', array_keys($log)),
                    '{{HTTP_REFERER}}' => ENGINE::_($_SERVER['HTTP_REFERER'], '-'),
                    '{{HTTP_USER_AGENT}}' => ENGINE::_($_SERVER['HTTP_USER_AGENT'], '-')
                )
            );
            }
            self::$session_started = true;
        }
    }

    static function startSessionIfExists()
    {
        if (self::$session_started) {
            return;
        }

        $session_name = ENGINE::option('engine.sessionname', session_name());
        if (array_key_exists($session_name, $_GET)
            && array_key_exists($session_name, $_COOKIE)
        ) {
            ENGINE::set_option('action', 'reload');
        }
        if (array_key_exists($session_name, $_GET)
            || array_key_exists($session_name, $_COOKIE)
        ) {
            ENGINE::start_session(false); //session_start();
        }
    }

    static function close_session()
    {
        if (self::$session_started) {
            session_write_close();
            self::$session_started = false;
        }
    }
    

    /**
     * инициализация системы и прописка основных обработчиков
     * @static
     * @param string|array $options
     */
    static function init($options)
    {
        ini_set('session.use_trans_sid', '0');
        ini_set('session.use_cookies', '1');
        ini_set('session.bug_compat_42', '0');
        ini_set('allow_call_time_pass_reference', '1');

        if (defined('ROOT_URI')) ENGINE::set_option('page.root', ROOT_URI);

        if (is_array($options)) {
            ENGINE::set_option($options);
        } else if (is_readable($options)) {
            ENGINE::set_option(include ($options));
        } else {
            ENGINE::error('Init: parameter failed');
        }
        //////////////////////////////////////////////////////////////////////////////////
// register all default interfaces
        foreach (ENGINE::option('engine.interfaces', array()) as $k => $v)
            ENGINE::register_interface($k, $v);

//////////////////////////////////////////////////////////////////////////////////
// include all classes from `engine.include_files`
        foreach (ENGINE::option('engine.include_files', array()) as $f)
            include_once($f);

        self::$class_alias = ENGINE::option('engine.aliaces', array());

        foreach (ENGINE::option('external.options', array()) as $k => $v)
            ENGINE::set_option($k, null, $v);

        foreach (ENGINE::option('engine.event_handler', array()) as $k => $v) {
            if (is_array($v) && count($v) > 0) {
                foreach ($v as $vv) {
                    ENGINE::register_event_handler($k, $vv);
                }
            }
        }
    }

    

    /**
     * функция получения информации о стеке вызовов.
     * @param array $opt - массив с ключами для вывода
     * @param int $count - количество позиций
     * @return string
     */
    static function backtrace($opt=array(),$count=1){
        $x=debug_backtrace();
        if(empty($opt)) $opt=array('function'=>'debug');
        if(isset($opt['count'])) $count=$opt['count'];
        $result=array();
        while(count($x)>0){
            foreach(array('function','class') as $xx) {
                if(isset($opt[$xx]) && isset($x[0][$xx]) && (0===strpos($x[0][$xx],$opt[$xx]))){
                    //array_shift($x);
                    break 2;
                }
                elseif(isset($opt['!'.$xx]) && isset($x[0][$xx]) && (false===strpos($x[0][$xx],$opt['!'.$xx])))
                    break 2;
            }
            array_shift($x);
        }
        if(empty($x)){
            $x=debug_backtrace();$count=max(3,$count);array_shift($x);
        } else if(!empty($opt['shift'])){
            array_shift($x);
        }
        while($count-- && (count($x)>0)) {
            $xx=array();
            $y=array_shift($x);
            foreach(array('function'=>'func','class'=>'cls','file'=>'file','line'=>'line') as $k=>$v) {
                if(isset($y[$k]))
                    if($k=='file')
                        $xx[]=$v.':'.str_replace($_SERVER['DOCUMENT_ROOT'],'',$y[$k]);
                    else
                        $xx[]=$v.':'.$y[$k];
            }
            if(!empty($xx))
                $result[]= implode(',',$xx);
        }
        return implode("\n|",$result);
    }

    static function debug()
    {
        $backtrace_options=array('function'=>'debug');$backtrace_count=1;
        $na=func_num_args();
        $out='';
        for ($i=0; $i<$na;$i++){
            $msg=func_get_arg($i);

            if(is_string($msg) && strlen($msg)>1 && $msg{0}=='~'){
                $x=explode('|',substr($msg,1).'||');
                $backtrace_options[$x[0]]=$x[1];
            } else {
                $out.=self::varlog($msg)."\r\n";
            }
        }
        if(empty($backtrace_options))$backtrace_count=4;
        echo '<!--xxx-debug-'.self::backtrace($backtrace_options,$backtrace_count).' '.str_replace('-->','--&gt;',trim(substr($out,0,16000))).'-->';
    }

    /**
     * лог объекта в печатную форму
     *
     * @param $varInput
     * @param string $var_name
     * @param string $reference
     * @param string $method
     * @param bool $sub
     *
     * @return string
     *
     * http://us2.php.net/manual/en/function.var-dump.php#76072
     */
    static function varlog(
        &$varInput, $var_name='', $reference='', $method = '=', $sub = false
    ) {

        static $output ;
        static $depth ;

        if ( $sub == false ) {
            $output = '' ;
            $depth = 0 ;
            $reference = $var_name ;
            $var = serialize( $varInput ) ;
            $var = unserialize( $var ) ;
        } else {
            ++$depth ;
            $var =& $varInput ;
        }

        // constants
        $nl = "\n" ;
        $block = 'a_big_recursion_protection_block';

        $c = $depth ;
        $indent = '' ;
        while( $c -- > 0 ) {
            $indent .= '|  ' ;
        }
        if($depth>5) return '...';
        // if this has been parsed before
        if ( is_array($var) && isset($var[$block])) {

            $real =& $var[ $block ] ;
            $name =& $var[ 'name' ] ;
            $type = gettype( $real ) ;
            $output .= $indent.$var_name.' '.$method.'& '.($type=='array'?'Array':get_class($real)).' '.$name.$nl;

            // havent parsed this before
        } else {

            // insert recursion blocker
            $var = Array( $block => $var, 'name' => $reference );
            $theVar =& $var[ $block ] ;

            // print it out
            $type = gettype( $theVar ) ;
            switch( $type ) {

                case 'array' :
                    $output .= $indent . $var_name . ' '.$method.' Array ('.$nl;
                    $keys=array_keys($theVar);
                    foreach($keys as $name) {
                        $value=&$theVar[$name];
                        self::varlog($value, $name, $reference.'["'.$name.'"]', '=', true);
                    }
                    $output .= $indent.')'.$nl;
                    break ;

                case 'object' :
                    $output .= $indent;
                    if(!empty($var_name)){
                        $output .= $var_name.' = ';
                    }
                    //$output .= '{'.var_export ($theVar,true).$indent.'}'.$nl;
                    //break;
                    //if( !class_exists('ReflectionClass')){
                        $output .= get_class($theVar).' {'.$nl;
                        foreach((array)$theVar as $name=>$value) {
                            self::varlog($value, $name, $reference.'->'.$name, '->', true);
                        }
                    /*} else {
                        $reflect = new ReflectionClass($theVar);
                        $output .= $reflect->getName().' {'.$nl;
                        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);

                        foreach ($props as $prop) {
                           // if()
                            self::varlog($theVar->{$prop}, $prop, $reference.'->'.$prop, '->', true);
                            print $prop->getName() . "\n";
                        }
                    }*/
                    $output .= $indent.'}'.$nl;
                    break ;

                case 'string' :
                    if($var_name!='' && strlen($theVar)>1000){
                        $output .= $indent . $var_name . ' '.$method.' "'.mb_substr($theVar,0,500,"UTF-8").'..."'.$nl;
                    } else {
                        $output .= $indent . $var_name . ' '.$method.' "'.$theVar.'"'.$nl;
                    }
                    break ;

                default :
                    $output .= $indent . $var_name . ' '.$method.' ('.$type.') '.$theVar.$nl;
                    break ;

            }

            // $var=$var[$block];

        }

        -- $depth ;

        if( $sub == false )
            return $output ;

    }

    


    /**
     * @param $flag
     * @return int
     * @sample if(DBG::hasflag('test1')){ ...
     */
    static function hasflag($flag)
    {
        return isset($_COOKIE) &&
        isset($_COOKIE[self::$cookie_name]) &&
        preg_match('/\b' . preg_quote($flag) . '\b/', $_COOKIE[self::$cookie_name]);
    }

    /**
     * setting up cookie in case user want to start debuging
     * @return bool
     * @sample if(DBG::initflags()) header('location: '.#clear URL from testing parameter#)
     */
    static function initflags()
    {
        if (isset($_GET) && isset($_GET[self::$cookie_name]) && !preg_match('/[^\w,\+\-\.\s]/', $_GET[self::$cookie_name])) {
            if(preg_match('/[\-\s\+]/',$_GET[self::$cookie_name])){
                if(isset($_COOKIE[self::$cookie_name])){
                    $cookie=preg_split('/[,]+/',$_COOKIE[self::$cookie_name]);
                } else {
                    $cookie=array();
                }
                if(preg_match_all('/([\s\-\+])([\w]*)/',$_GET[self::$cookie_name],$m))
                foreach($m[0] as $k=>$v){
                    if($m[1][$k]!='-') $cookie[]=$m[2][$k];
                    else {
                        $cookie=array_diff($cookie,array($m[2][$k])) ;
                    }
                }
                $cookie=implode(',',array_unique($cookie));
            } else {
                $cookie=trim($_GET[self::$cookie_name]);
            }
            if (!empty($cookie))
                setcookie(self::$cookie_name, $cookie);
            else
                setcookie(self::$cookie_name, "", time() - 3600);
            return true;
        }
        return false;
    }
    


    /**
     * хяндлер ENGINE::DB
     *
     * @param string $option строка параметров
     *
     * @return xDatabaseLapsi
     */
    static public function &db($option = '')
    {
        if (empty(self::$db)) {
            self::$db = self::getObj('Database', $option);
        }
        self::$db->set_option($option);
        return self::$db;
    }

    

    static public function _report()
    {
        echo '<!--';
        /*  --- point::ENGINE_final_report --- */

        if (!empty(self::$db)) {
            echo self::$db->report();
        }

        printf("%f sec spent (%s)"
            ,microtime(true)-self::$start_time,date("Y-m-d H:i:s"));
        echo '-->';
    }
    static public function _shutdown()
    {
        /*  --- point::ENGINE_shutdown --- */

        if (self::$memcached) {
            self::$memcached->close();
            self::$memcached = null;
        }

        if(ENGINE::option('noreport'))return;
        ENGINE::_report();
    }
    

    /**
     * вывод собощения в системный лог.
     * Записывается сессия пользователя, если она есть.
     * По дороге делается попытка проинициировать таблицу лога.
     *
     * @param string $msg сообщение
     * @param array  $arg параметры в стиле _t + параметр type -
     *
     * @return empty
     */
    static function log($msg, $arg = array())
    {
        if (!self::$_logger_table) {
            $uri = parse_url(
                ENGINE::option('engine.logger_uri', 'db://userlog')
            );
            if (!empty($uri['host'])) {
                $uri['path'] = $uri['host'] . ENGINE::_($uri['path']);                    }
            self::$_logger_table = ENGINE::option('engine.log_table', $uri['path']);
        }
        if (is_string($arg)) {
            $arg = array('type' => $arg);
        } else if (!isset($arg['type'])) {
            $arg['type'] = 'userlog';
        }
        if (!isset($arg['key'])) {
            $arg['key'] = session_id();
        }
        for($i=1;$i<2;$i++){
        $res=ENGINE::db()->query(
            'insert into `' . self::$_logger_table .
            '` set `msg`=?,`type`=?,`key`=?;',
            ENGINE::_t($msg, $arg), $arg['type'], $arg['key']
        );
            if(!$res){
                ENGINE::db()->query(
                    'create table if not exists `' . self::$_logger_table . '`(
`id` INT( 11 ) NOT NULL AUTO_INCREMENT ,
`date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
`msg` TEXT ,
`type` VARCHAR( 8 ) NOT NULL ,
`key` VARCHAR( 40 ) NOT NULL ,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `key` (`key`)
) ENGINE = MYISAM ;'
                );
            }
        }
    }
    

    /**
     * организация массива - регулярка, имена захваченных
     * @var array
     *
     */
    static function route($rules = null)
    {

        /** @var array $rules */
        if (empty($rules)) {
            /**
             * дефолтное правило - если нет роутинга в конфиге
             * - показываем стартовую страницу
             */
            $rules = ENGINE::option(
                'router.rules',
                array('', array('class' => 'Main', 'method' => 'do_Default'))
            );
        }

        /**
         * @var string $query_string - очищенная от стартового
         * каталога строка запроса
         */
        $query_string = preg_replace(
            '#^' . ENGINE::option('page.rootsite') . '#i',
            '',
            $_SERVER['REQUEST_URI']
        );

        /** аварийное правило, если никакое правило роутинга не подойдет  */
        ENGINE::set_option(
            array('class' => 'Main', 'method' => 'do_404')
        );

        foreach ($rules as $rule) {
            if (empty($rule[0]) || preg_match($rule[0], $query_string, $m)) {
                foreach ($rule[1] as $k => $v) {
                    if (is_int($k)) {
                        if (!empty($m[$k])) {
                            if ($v == 'method') {
                                $m[$k] = 'do_' . $m[$k];
                            }
                            ENGINE::set_option($v, $m[$k]);
                        }
                    } else {
                        ENGINE::set_option($k, $v);
                    }
                }
                break;
            }
        }
    }

    /**
     * строим ссылку, по полученным параметрам
     * @param string $z - адре для перехода
     * @param string|array $act - действие
     * @param null $par - дополнительные параметры
     * @return string
     */
    static function link($z = '', $act = '', $par = null)
    {
        if (is_null(self::$url_par)) {
            self::$url_par = $_GET;
        }
        if (is_null(self::$url_path)) {
            self::$url_path = preg_replace("/\?.*$/", "", $_SERVER['REQUEST_URI']);
        }
        //$uri = ENGINE::option('page.rootsite');
        $z = str_replace('\\', '/', $z);
        $host = 'http://'
            . $_SERVER["SERVER_NAME"]
            . (80 == $_SERVER["SERVER_PORT"] ? '' : ':' . $_SERVER["SERVER_PORT"]);
        if (!is_array($act)) {
            $act = array(array($act, $par));
        }
        foreach ($act as $x) {
            $action = $x[0];
            $param = self::_($x[1],'');
            switch ($action) {
                case '+':
                    if (empty($param))
                        self::$url_par = array();
                    self::$url_par = array_merge(self::$url_par, $param);
                    break;
                case '-':
                    if (empty($param)) {
                        self::$url_par = array();
                    } else {
                        self::$url_par = array_diff_key(self::$url_par, array_flip($param));
                    }
                    break;
                case 'file2url':
                    if(empty($par)){
                        $query='';
                    } else {
                        $query=$par;
                    }
                    if (!empty($query))
                        $query = '?' . $query;
                    $z=str_replace(str_replace('\\', '/',INDEX_DIR),'',$z);
                    return $z . $query;
                case 'root':
                    self::$url_path=ENGINE::option('page.rootsite', self::$url_path).$z;
                    break;
                case 'replace':
                    self::$url_par = $param;
                    break;
            }
        }
        if(!empty(self::$url_par))
            $query = http_build_query(self::$url_par);
        else
            $query='';
        if (!empty($query))
            $query = '?' . $query;
        return self::$url_path . $query;

    }

    


    static function relocate($link)
    {
        if (ENGINE::hasflag('norelock') || ENGINE::option('debug', false)) {
            echo '<a href="' . $link . '">Press link to redirect</a>';
        } else {
            header('location:' . $link);
        }
        exit;
    }

    static function  ajax_action()
    {
        ENGINE::set_option('ajax', true);
        header('Content-type: application/json; charset=UTF-8');
        $data = array();
        if ('POST' == $_SERVER['REQUEST_METHOD'] &&
            (array_key_exists('handler', $_POST) || !ENGINE::option('skip_post',false))
        ) {
            if (array_key_exists('handler', $_POST)) {
                preg_match(
                    '/^([^:]*)::([^:]+)(?::([^:]+))?(?::([^:]+))?(?::([^:]+))?$/',
                    $_POST['handler'], $m
                );
                if (empty($m[1])) {
                    $m[1] = 'Main';
                }
                if (empty($m[2])) {
                    ENGINE::error('Wrong handler.');
                }
                for ($i = 3; $i < 6; $i++) {
                    if (!array_key_exists($i, $m)) {
                        $m[$i] = '';
                    }
                }
                $act = array($m[1], 'do_' . $m[2]);
                $data = ENGINE::exec($act, array($m[3], $m[4], $m[5]));
            } else {
                //ENGINE::error('Wrong usage of POST method.');
                $data = self::getData();
            }
        } else {
            /*  --- point::BEFORE_GETDATA --- */
            $data = self::getData();
        }
        //ENGINE::debug('333',ENGINE::option('ajax'));

        $result = array('data' => $data);
        $x = ob_get_contents();
        $x .= trim(ENGINE::option('page.debug'));
        if (!empty($x)) {
            $result['debug'] = utf8_encode($x);
        }
        ob_end_clean();
        self::_finish_ajax($result);

    }

    static function _finish_ajax($result=array()){
        $data = ENGINE::slice_option('ajax.');
        if (!empty($data)) {
            $result = array_merge($result, $data);
        }
        $error = ENGINE::option('page.error');
        if (!empty($error))
            $result['error'] = utf8_encode($error);

        if (session_id() != "") {
            $result['session'] = array('name' => session_name(), 'value' => session_id());
        }

        // $q=ENGINE::db()->exec('Database',get_request_count
        ob_start();
        ENGINE::_report();
        $result['stat'] = ob_get_contents();
        ob_end_clean();
        ENGINE::set_option('noreport',1);
        //echo json_encode_cyr($result);
        if(isset($_POST['ajax']) && $_POST['ajax']=='iframe'){
            echo '<script type="text/javascript"> top.ajax_handle('.utf8_encode(json_encode($result)).'</script>';
        } else
            echo utf8_encode(json_encode($result));
    }

    static function getData()
    {
        $x = array(ENGINE::option('class', 'Main'), ENGINE::option('method', 'do_Default'));
        //if(class_exists())
        if(!ENGINE::getObj($x[0])){
            $x=array('Main','do_404');
        }
        return ENGINE::exec($x);
    }

static function action()
    {
        ob_start();
        $error = ENGINE::option('session.page.error');
        if (!empty($error)) {
            ENGINE::set_option('session.page.error', '');
            ENGINE::error($error);
        }
        if (is_callable('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = array();
        }
        //ENGINE::debug($headers);
        if ((isset($headers['X-Requested-With']) && $headers['X-Requested-With']=='XMLHttpRequest') || isset($_GET['ajax']) || (isset($_POST) && isset($_POST['ajax']) && $_POST['ajax']=='iframe')) {
            self::ajax_action();
            return;
        }
        header('Content-Type:text/html; charset=' . ENGINE::option('page.code', 'UTF-8'));
        header('X-UA-Compatible: IE=edge,chrome=1');

        if(isset($_SESSION['SAVE_POST'])){
            if('POST' != $_SERVER['REQUEST_METHOD']) {
                $_SERVER['REQUEST_METHOD']='POST';
                $_POST=$_SESSION['SAVE_POST'];
                $_FILES=$_SESSION['SAVE_FILES'];
            }
            unset($_SESSION['SAVE_POST'],$_SESSION['SAVE_FILES']);
        }

        if ('POST' == $_SERVER['REQUEST_METHOD'] &&
            (array_key_exists('handler', $_POST) || !ENGINE::option('skip_post',false))) {
            if (array_key_exists('handler', $_POST)) {
                preg_match('/^([^:]*)::([^:]+)(?::([^:]+))?(?::([^:]+))?(?::([^:]+))?$/'
                    , $_POST['handler'], $m);
                if (empty($m[1])) $m[1] = 'Main';
                if (empty($m[2])) ENGINE::error('Wrong handler.');
                for ($i = 3; $i < 6; $i++)
                    if (!array_key_exists($i, $m)) $m[$i] = '';
                $act = array($m[1], 'do_' . $m[2]);
                ENGINE::exec($act, array($m[3], $m[4], $m[5]));
            } else
                ENGINE::error('Wrong usage of POST method.');
            $error = ENGINE::option('page.error');
            if (!empty($error)) {
                ENGINE::set_option('session.page.error', $error);
            }
            ENGINE::relocate(ENGINE::link());
        }
        /*  --- point::BEFORE_GETDATA --- */

        $data = self::getData();

        $x = ENGINE::template(
            ENGINE::option('page_tpl', 'tpl_main')
            , ENGINE::option('page_macro', '_')
            , array_merge(array('data' => $data), ENGINE::slice_option('page.'))
        );
        if (!trim($x)) {
            ENGINE::error($x = ENGINE::_t('template `{{tpl}}::{{macro}}` not defined',
                array('{{tpl}}' => ENGINE::option('page_tpl', 'tpl_main'),
                    '{{macro}}' => ENGINE::option('page_macro', '_'))));
            $x = '<html><head><title>Oops</title></head><body>' . $x . '</body></html>';
        }
        echo $x;

//        unset($_SESSION['errormsg']);
    }

}

/*  --- point::ENGINE_bottom --- */

/**
 * умная склейка массивов в глубину
 * @param $tomerge
 * @param $part
 * @return bool
 */
function array_merge_deep(&$tomerge, $part)
{
    $result = false;
    // ассоциативный массив
    foreach ($part as $k => &$v) {
        if (array_key_exists($k, $tomerge)) {
            if (is_array($tomerge[$k]) && is_array($v)) {
                $result = array_merge_deep($tomerge[$k], $v) || $result;
            } elseif (is_null($v)) {
                if (isset($tomerge[$k])) {
                    unset($tomerge[$k]);
                    $result = true;
                }
            } else {
                if (isset($tomerge[$k]) && $tomerge[$k] != $v) {
                    $tomerge[$k] = $v;
                    $result = true;
                }
            }
        } elseif (!is_null($v)) {
            $tomerge[$k] = $v;
            $result = true;
        }
    }
    unset ($v);
    return $result;
}

/**
 * умная очистка значений в глубину
 * @param $tomerge
 * @param $part
 * @return bool
 */
function array_clear_deep(&$tomerge, &$part)
{
    $result = false;
    foreach ($part as $k => &$v) {
        if (array_key_exists($k, $tomerge)) {
            if (is_array($tomerge[$k]) && is_array($v)) {
                $result = array_clear_deep($tomerge[$k], $v) || $result;
                if(count($tomerge[$k])==0){
                    unset($tomerge[$k]);
                    $result = true;
                }
            } else {
                if (isset($tomerge[$k])) {
                    unset($tomerge[$k]);
                    $result = true;
                }
            }
        }
    }
    unset ($v);
    return $result;
}



spl_autoload_register('ENGINE::_autoload');



/**
 * класс для хранения параметров в сессии
 */
class engine_options_session
{

    function get($name)
    {
        if (ENGINE::$session_started)
            return $_SESSION[$name];
        else
            return null;
    }

    function set($name, $value = null)
    {
        ENGINE::start_session();
        if (empty($value))
            unset($_SESSION[$name]);
        else
            $_SESSION[$name] = $value;
    }

}



register_shutdown_function('ENGINE::_shutdown');
ENGINE::$start_time=microtime(true);
