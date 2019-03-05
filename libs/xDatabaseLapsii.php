<?php
/**
 * класс базы данных проекта. Вариант с mysqli драйвером
 * Внешние зависиости
 *  ENGINE::debug, ::error, ::option, ::cache
 * <%=POINT::get('hat','comment');%>






 */

/**
 * класс, собирающий длинный insertValues
 */
class dbInsertValues
{

    /** @var string */
    private $_sql_start = '', $_sql_finish = '';

    /** @var xDatabaseLapsii */
    private $_parentDb;

    /** @var array */
    private $_result_values = array();

    /** @var int - длина результирующего запроса */
    private $_result_length = 0;

    /** @var int */
    private $_max_result_length = 32000;

    /**
     * конструктор
     *
     * @param string $start кусок sql сначала поля value
     * @param string $finish кусок sql после поля value
     * @param xDatabaseLapsii $parent - родительский датабейз
     */
    public function __construct($start, $finish, $parent)
    {
        $this->_sql_start = $start;
        $this->_sql_finish = $finish;
        $this->_parentDb = $parent;
        $this->_result_length = strlen($start) + strlen($finish);
    }

    /**
     * вставить очередную порцию даных
     *
     * @param array $values данные
     *
     * @return null
     */
    public function insert($values)
    {
        $v = $this->_parentDb->_(array('(?[?2])', $values));
        $this->_result_length += strlen($v) + 1;
        if ($this->_result_length > $this->_max_result_length) {
            $this->flush();
        }
        $this->_result_values[] = $v;
    }

    /**
     * выполнение настоящего запроса.
     *
     * @return null
     */
    public function flush()
    {
        if (count($this->_result_values) > 0) {
            $this->_parentDb->query(
                $this->_sql_start .
                implode(',', $this->_result_values) .
                $this->_sql_finish
            );
            $this->_result_values = array();
            $this->_result_length = strlen($this->_sql_start) +
                strlen($this->_sql_finish);
        }
    }

    /**
     * дык, деструктор
     */
    function __destruct()
    {
        $this->flush();
    }
}

/**
 * класс, возвращаемый в ответ на длинный select
 */
class dbIterator implements Iterator
{
    private $_position = 0;
    private $_dbresult = null;
    private $_data = null;
    /** @var xDatabase */
    private $_parent = null;

    /**
     * конструкт. Допускается только внутриклассовое конструирование объекта.
     *
     * @param resource $dbresult датабаза
     * @param $_parent
     */
    public function __construct($dbresult, $_parent)
    {
        $this->_dbresult = $dbresult;
        $this->_position = 0;
        $this->_parent = $_parent;
    }

    /**
     * интерфейс - поддержка итератора. Перемотай вначало
     *
     * @return null
     */
    function rewind()
    {
        $this->next();
        $this->_position = 0;
    }

    /**
     * интерфейс - поддержка итератора. дай данные
     *
     * @return mixed
     */
    function current()
    {
        return $this->_data;
    }

    /**
     * интерфейс - поддержка итератора. Дай ключик
     *
     * @return int
     */
    function key()
    {
        return $this->_position;
    }

    /**
     * интерфейс - поддержка итератора. Перемотай дальше
     *
     * @return null
     */
    function next()
    {
        $this->_data = $this->_parent->fetch($this->_dbresult);
    }

    /**
     * интерфейс - поддержка итератора. А чо это?
     *
     * @return boolean
     */
    function valid()
    {
        return is_array($this->_data);
    }

    /**
     * Деструктор, всех расстрелять.
     */
    public function __destruct()
    {
        $this->_parent->free($this->_dbresult);
    }

}


/**
 * общий наследник всех базоданческих драйверов
 */
class xDatabase_parent
{
    protected $debug = array();
    protected $once_options = array();

    protected $_prefix='xsite';
    /**
     * @var bool - флаг - нужно ли инициализироваться на старте.
     * Устанавливать соединение и т.д.
     */
    protected $_init = true;

    /** @var bool - флаг - не исполнять запросы, а просто дебажиться.... */
    protected $_test = false;

    /** @var int - счетчик выполненных запросов */
    protected $q_count = 0;

    /** @var null|resource - сопроводительная переменная - линк работы с
     * открытой базой
     */
    protected $db_link = null;

    /** @var string -временная переменная для хранения ключа кэша */
    protected $cachekey = '';

    /**
     * конструирование объекта
     * -- попишем инстанс,  непонтно зачем
     * -- инициализация коннекта
     *
     * @param string $option параметры
     */
    function __construct($option = '')
    {
        if (!empty($option)) {
            $this->set_option($option);
        }
    }

    function error($msg)
    {
        echo $msg;
    }

    function fetch($res)
    {
        $this->error('unrealised awhile ');
        return array();
    }

    function notempty($res)
    {
        $this->error('unrealised awhile ');
        return false;
    }

    function escape($str)
    {
        $this->error('unrealised awhile ');
        return '';
    }

    function free($res)
    {
        $this->error('unrealised awhile ');
    }

    function affectedrow()
    {
        $this->error('unrealised awhile ');
        return false;
    }

    function insertid()
    {
        $this->error('unrealised awhile ');
        return false;
    }

    /**
     * установить параметры. Параметры ставятся в виде строки со словами через
     * пробел.
     * параметр - внутреняя логичесская переменная класса с именем `_параметр`
     * в природе бывают параметры
     * - init(*), noinit
     * - debug, nodebug(*)
     * - cache(*), nocache
     *
     * @param string $option строка с параметраи, через пробел
     *
     * @return null
     */
    function set_option($option)
    {
        $prop = array();
        $once = false;
        if (is_array($option)) {
            $prop = $option;
        } else if (is_string($option)) {
            foreach (explode(' ', $option) as $o) {
                if ($o == 'once') {
                    $once = true;
                    continue;
                } else if (strpos($o, '~') === 0) {
                    $this->debug[] = $o;
                    continue;
                } else if (strpos($o, 'no') === 0) {
                    $o = substr($o, 2);
                    $val = false;
                } else {
                    $val = true;
                }
                $prop[$o] = $val;
            }
        }

        /*        if(isset($prop['debug'])){
                    ENGINE::debug('db debug:'.$prop['debug'],'~count|10');
                }
        */
        if (!empty($this->once_options)) {
            foreach ($this->once_options as $o => $val) {
                $this->$o = $val;
            }
            $this->once_options = array();
        }
        if (!empty($prop)) {
            foreach ($prop as $o => $val) {
                if (property_exists($this, $o = '_' . $o) && ($this->$o != $val)) {
                    if ($once) {
                        $this->once_options[$o] = $this->$o;
                    }
                    $this->$o = $val;
                }
            }
        }
    }

    /**
     * вывод финального репорта про количество запросов.
     *
     * @param string $format - формат вывода репорта. Вдруг пригодится.
     *
     * @return string
     */
    function report($format = "%s queries,")
    {
        return sprintf($format, $this->q_count);
    }

    /**
     * выбрать первое поле в результатах запроса. LIMIT 1 ДОЛЖЕН присутствовать.
     *
     * @return mixed
     */
    function selectCell()
    {
        $result = $this->_query(func_get_args(), true);
        if (!$this->notempty($result)) {
            return $result;
        }
        $rows = $this->fetch($result);
        $this->free($result);
        if (!$rows) {
            return false;
        }
        return $this->cache($this->cachekey, array_pop($rows));
    }

    /**
     * выбрать первое поле в результатах запроса. LIMIT 1 РЕКОМЕНДУЕТСЯ.
     *
     * @return mixed
     */
    function selectCol()
    {
        $result = $this->_query(func_get_args(), true);
        if (!$this->notempty($result)) {
            return $result;
        }
        $res = array();
        while ($row = $this->fetch($result)) {
            $res[] = array_shift($row);
        }
        $this->free($result);
        return $this->cache($this->cachekey, $res);
    }

    /**
     * выбрать первую строку.
     * @return mixed
     */
    function selectRow()
    {
        $result = $this->_query(func_get_args(), true);
        //ENGINE::debug($result);
        if (!$this->notempty($result)) {
            return false; //$result;
        }
        $rows = $this->fetch($result);
        $this->free($result);
        return $this->cache($this->cachekey, $rows);
    }

    function selectAll()
    {
        $result = $this->_query(func_get_args(), true);
        if (!$this->notempty($result)) {
            return $result;
        }
        $res = array();
        while ($row = $this->fetch($result)) {
            $res[] = $row;
        }
        $this->free($result);
        return $this->cache($this->cachekey, $res);
    }

    /**
     * Выбрать все, каждая строка - ассоциативный массив.
     *
     * @return resource|boolean
     */
    function select()
    {
        $result = $this->_query(func_get_args(), true);
        if (!$this->notempty($result)) {
            return $result;
        }
        $res = array();
        while ($row = $this->fetch($result)) {
            $res[] = $row;
        }
        $this->free($result);
        return $this->cache($this->cachekey, $res);
    }

    /**
     * функция кэширования запроса по sql
     *
     * @param string $name
     * @param bool $data
     *
     * @return bool
     */
    function cache($name, $data = false)
    {
        return $data;
    }

    /**
     * Выбрать все из большого запроса, вернуть итератор.
     *
     * @return boolean|dbIterator
     */
    function selectLong()
    {
        $result = $this->_query(
            func_get_args(), false // не кэшировать большие запросы
        );
        if (!$this->notempty($result)) {
            return $result;
        }
        return new dbIterator($result, $this);
    }

    /**
     * Выбрать все из запроса, вернуть массив с инлексами.
     *
     * @param int $idx параметр для ключика
     *
     * @return array
     */
    function selectByInd($idx)
    {
        $arg = func_get_args();
        array_shift($arg);
        $result = $this->_query($arg, true);
        if (!$this->notempty($result)) {
            return $result;
        }
        $res = array();
        while ($row = $this->fetch($result)) {
            if (!empty($row[$idx])) {
                $res[$row[$idx]] = $row;
            }
        }
        $this->free($result);
        return $this->cache($this->cachekey, $res);
    }

    /**
     * Вставить. Вернуть последний вставленный индекс.
     */
    function insert($query)
    {
        $result = $this->_query(func_get_args());
        $this->free($result);
        return $this->insertid();
    }

    /**
     * удалить. Вернуть колиество или качество удаленных записей
     *
     * @return int
     */
    function delete()
    {
        $result = $this->_query(func_get_args());
        $this->free($result);
        return $this->affectedrow();
    }

    /**
     * выполнить запрос с параметрами.
     * sql парсится и дополняется параметрами.
     *
     * @param array $arg - Запрос + параметры запроса
     * @param string $options - опции запроса
     *
     * @return resource
     */
    protected function _query($arg, $options = '')
    {
        $this->error('unrealised awhile ', $arg, $options);
        return null;
    }

    /**
     * обновить, ничего не возвращать.
     *
     * @return null
     */
    function update()
    {
        $result = $this->_query(func_get_args());
        $this->free($result);
        return $this->affectedrow();
    }

    /**
     * выполнить небольшое количество sql запросов. Ничего не возвращать
     *
     * @param string $sql собственно дамп
     *
     * @return null
     */
    public function sql_dump($sql)
    {
        foreach (explode(";\n", str_replace("\r", '', $sql)) as $s) {
            $s = trim(preg_replace('~^\-\-.*?$|^#.*?$~m', '', $s));
            if (!empty($s)) {
                $this->query($s);
            }
        }
    }

    /**
     * выполнить запрос, вернуть результат в зависимости от вида запроса
     *
     * @param $query
     *
     * @return mixed
     */
    function query($query)
    {
        $result = $this->_query(func_get_args());
        $this->free($result);
    }

    /**
     * helper-заполнятель sql конструкций.
     * список подстановок
     *  ?_ - подставить префикс таблицы, указатель парамемтров не перемещается
     *  ?12x - подставить 12 по счету параметр. Указатель параметров не перемещается
     *      без номера - указатель перемещается на следующий параметр
     *  ?x - подставить параметр без обработки
     *  ?d, ?i - параметр - чиcло. Явно приводится к числовому значению, каычек нет.
     *  ?k - параметр - имя поля, обрамляется `` кавычками
     *  ?s - параметр - строка - выводится в двойных кавычках,
     *      делается escape_string
     *  ? - анализируется значение, для чисел не вставляются кавычки,
     *      для строк делается ескейп
     *  ?[...] - параметр - массив, для каждой пары ключ-значение массива
     *      применяется формат из скобок. Разделяются запятыми
     *
     * @example
     * простой insert
     *    - $db->_(array('insert into ?k (?(?k)) values (?2(?2))','x_table'
     *           ,array('one'=>1,'two'=>2,'three'=>'облом')))
     *     ==> insert into `x_table` (`one`,`two`,`three`) values (1,2,"облом")
     *
     * insert on duplicate key
     *    - $db->_(array('insert into ?k (?(?k)) values (?2(?2))
     *      on duplicate key set ?2(?k=?)','x_table'
     *      ,array('one'=>1,'two'=>2,'three'=>'облом')))
     *     ==> insert into `x_table` (`one`,`two`,`three`) values (1,2,"облом")
     *      on duplicate key set `one`=1,`two`=2,`three`="облом"
     *  - $db->query(
     *      'insert into `laptv_video` (`LASTUPDATE`,?[?k]) values (NOW(),?1[?2])'.
     *      'on duplicate key update  `LASTUPDATE`=NOW(),?1[?1k=VALUES(?1k)];'
     *      ,$data);
     *
     * генерация простыни
     *   - $x=array(
     *         array('x'=>1,'y'=>2,'z'=>3),
     *         array('x'=>1,'y'=>2,'z'=>3),
     *         array('x'=>1,'y'=>2,'z'=>3),
     *       ...
     *    )
     *    $part=array();
     *    foreach($x as $xx) $part[]=...->_(array(array('(?(?2))',$xx)));
     *    ->_(array('insert into ?k (?(?k)) values ?3(?2x);','table',$x[0],$part)))
     *
     * @param array $args нулевой параметр - формат
     *
     * @return string
     */
    function _($args)
    {
        static $pref;
        //$args=func_get_args();
        $format = $args[0];
        $cnt = 1;
        $start = 0;
        while (preg_match('/(?<!\\\\)\?(\d*)([id\#ayxk_sq]|\[([^\]]+)\]|)/i'
            , $format, $m, PREG_OFFSET_CAPTURE, $start)
        ) {
            $x = '';
            $cur = $m[1][0];
            if (empty($cur)) {
                $cur = $cnt++;
            }
            if (empty($m[2][0])) {
                if ('' === $args[$cur]) {
                    $x = '""';
                } elseif (0 === $args[$cur]) {
                    $x = 0;
                } elseif ('0' === $args[$cur]) {
                    $x = 0;
                } elseif (empty($args[$cur])) {
                    $x = 'null';
                } elseif (is_int($args[$cur]) || ctype_digit($args[$cur])) {
                    $x = (0 + $args[$cur]);
                } else {
                    $x = '"' . $this->escape($args[$cur]) . '"';
                }
                $xx = '';
            } else {
                switch ($xx = $m[2][0]) {
                    case '_':
                        if (!isset($pref)) {
                            $pref = $this->_prefix;
                        }
                        if (empty($m[1][0])) {
                            $cnt--;
                        }
                        $x = $pref;
                        break;
                    case 'i':
                    case 'd':
                        $x = (0 + $args[$cur]);
                        break;
                    case 'x':
                        $x = $args[$cur];
                        break;
                    case 'k':
                        $x = '`' . str_replace("`", "``", $args[$cur]) . '`';
                        break;
                    case 's':
                        $x = '"' . $this->escape($args[$cur]) . '"';
                        break;
                    case 'q':
                        $x = "'" . $this->escape($args[$cur]) . "'";
                        break;
                    case 'l': //for like
                        $x = '"%' . addCslashes($args[$cur], '"\%_') . '%"';
                        break;
                    case 'y':
                        $x = $this->escape($args[$cur]);
                        break;
                    default: //()
                        $explode = ',';
                        if ($xx == 'a') { // ?a
                            reset($args[$cur]);
                            if (key($args[$cur])) {
                                $tpl = '?k=?';
                            } else {
                                $tpl = '?2';
                            }
                        } else if ($xx == '#') { //?#
                            $tpl = '?2k';
                        } else { // массив в параметрах
                            $tpl = $m[3][0]; //if(!empty($m[4][0]))$tpl.=$m[4][0];
                            if (false === ($pos = strpos($tpl, '|'))) {
                                $explode = ', ';
                            } else {
                                $explode = substr($tpl, $pos + 1);
                                $tpl = substr($tpl, 0, $pos);
                            }
                        }
                        if (is_array($args[$cur])) {
                            if (empty($args[$cur])) {
                                return 'null';
                            }
                            $s = array();
                            foreach ($args[$cur] as $k => $v) {
                                $s[] = $this->_(array($tpl, $k, $v));
                            }
                            $x = implode($explode, $s);
                        }
                }
            }
            $format = substr($format, 0, $m[0][1]) . $x .
                substr($format, $m[2][1] + strlen($xx));
            $start = $m[0][1] + strlen($x);
        }
        return $format;
    }

    /**
     * Вставить. Сгенерировать очень длинную простыню.
     * @example
     *   $x=array(...);
     *   $i=$db->insertValues('insert into `table` (?1[?1k])
     *      values () on duplicate key update ?1[?1k=VALUES(?1k)]',$x[0]);
     *   foreach($x as $xx)
     *      $i->insert($xx);
     *
     * @return dbInsertValues
     */
    function insertValues()
    {
        $sql = $this->_(func_get_args());
        list($start, $finish) = explode('()', $sql);
        return new dbInsertValues($start, $finish, $this);
    }

}

/**
 * Class xDatabase - завязка на кэш от ENGINE и сопроводительный сервис
 */
class xDatabase extends xDatabase_parent
{
    public $_cache = true;
    protected $c_count = 0;

    protected
        $_host = 'localhost',
        $_port = 9306,
        $_user = '',
        $_password = '',
        $_base = '',
        $_code = 'UTF8';

    /**
     * Кэширование. Паразитирует на системном кэшировании, с использованием
     * собственных флагов
     *
     * @param string $name имя
     * @param bool $data значение
     * @param int $time на время
     *
     * @return bool|mixed
     */
    function cache($name, $data = false, $time = 28800)
    {
        if (!$this->_cache) {
            return $data;
        }
        if (false === $data) {
            if (false !== ($result = ENGINE::cache($name))) {
                $this->c_count += 1;
                return unserialize($result);
            }
            return false;
        } else {
            ENGINE::cache($name, serialize($data), $time);
        }
        return $data;
    }

    /**
     * вывод финального репорта про количество запросов.
     *
     * @param string $format строк формата
     *
     * @return string
     */
    function report($format = "mysql:[%s(%s) queries] ")
    {
        return sprintf($format, $this->q_count, $this->c_count);
    }

    function error($msg)
    {
        ENGINE::error($msg);
    }
}

/**
 * вариант xDatabase для работы с mysqli
 */
class xDatabaseLapsii extends xDatabase
{
    /** @var bool|Memcache */
    // private $mcache = false;
    protected $_debug = false;

    function notempty($res)
    {
        return is_a($res, 'mysqli_result');
    }

    function affectedrow()
    {
        return @mysqli_affected_rows($this->db_link);
    }

    function insertid()
    {
        return @mysqli_insert_id($this->db_link);
    }

    /**
     * конструктор
     *
     * @param string $option параметры
     */
    function __construct($option = '')
    {
        parent::__construct($option);
        //ENGINE::debug(ENGINE::slice_option('database.'));
        $this->set_option(ENGINE::slice_option('database.'));
        // if ($this->_init) {
        $this->db_link = @mysqli_connect(
            $this->_host,
            $this->_user,
            $this->_password,
            $this->_base
        );
        if (empty($this->db_link) || mysqli_connect_error()) {
            ENGINE::error(
                'can\'t connect: (' . mysqli_connect_errno() . ') '
                . mysqli_connect_error() . "\n" .
                $this->_host . "\n" .
                $this->_user . "\n" .
                $this->_password . "\n" .
                $this->_base
            );
        }

        //$this->prefix = ENGINE::option('database.prefix', 'xsite');
        if ($option = ENGINE::option('database.options')) {
            $this->set_option($option);
        }

        if ($this->_init) {
            mysqli_set_charset($this->db_link, $this->_code);
        }
    }

    /**
     * выполнить запрос с параметрами.
     * sql парсится и дополняется параметрами.
     *
     * @param array $arg Запрос + параметры запроса
     * @param bool $cached кэшировать или нетъ
     *
     * @return resource
     */
    protected function _query($arg, $cached = false)
    {
        $start = 0;
        if ($this->_debug) {
            $start = microtime(true);
        }
        $sql = $this->_($arg);
        if ($cached) {
            $this->cachekey = ENGINE::option('cache.prefix', 'x') . md5($sql);
            if (false !== ($result = $this->cache($this->cachekey))) {
                if (false !== ($result = $this->cache($this->cachekey))) {
                    if ($this->_debug) {
                        $arg = $this->debug;
                        $arg[] = '~function|_query';
                        $arg[] = '~shift|1';
                        array_unshift(
                            $arg,
                            'QUERY(cache)' .
                            sprintf('[%f]', microtime(true) - $start) .
                            ': ' . $sql . "\n"
                        );
                        call_user_func_array(array('ENGINE', 'debug'), $arg);
                    }
                    return $result;
                }
                return $result;
            }
        }
        //ENGINE::debug( 222/* ,$this->db_link */);
        if (!$this->_test) {
            $result = mysqli_query($this->db_link, $sql);
            //ENGINE::debug($result);
            if (!$result) {
                ENGINE::error(
                    'Invalid query: ' . mysqli_error($this->db_link) . "\n" . 'Whole query: ' . $sql
                );
            } else {
                $this->q_count += 1;
            }
        } else {
            ENGINE::debug(
                "QUERY-TEST:\n" . $sql . "\n", '~function|_query', '~shift|1'
            );
            $result = false;
        }
        if ($this->_debug) {
            $arg = $this->debug;
            $arg[] = '~function|_query';
            $arg[] = '~shift|1';
            array_unshift(
                $arg,
                'QUERY' . sprintf('[%f]', microtime(true) - $start) .
                ': ' . $sql . "\n" . mysqli_info($this->db_link)
                . "\n" . mysqli_error($this->db_link)
            );
            call_user_func_array(array('ENGINE', 'debug'), $arg);
        }

        return $result;
    }

    function fetch($result)
    {
        return mysqli_fetch_assoc($result);
    }

    function escape($str)
    {
        return '' . @mysqli_real_escape_string($this->db_link, $str);
    }

    function free($result)
    {
        if ($this->notempty($result)) {
            mysqli_free_result($result);
        }

    }

    /**
     * закрыть неправеднооткрытое.
     * Не нужно, но чистота требует жертв
     */
    function __destruct()
    {
        if (!empty($this->db_link)) {
            mysqli_close($this->db_link);
            $this->db_link = null;
        }
    }

}

/**
 * вариант xDatabase для работы со sphinx
 */
class xSphinxDB extends xDatabaseLapsii
{

    function __construct($option = '')
    {
        xDatabase::__construct($option);
        $this->db_link = @mysqli_connect(
            $this->_host,
            $this->_user,
            $this->_password,
            '',
            $this->_port
        );
        if (empty($this->db_link) || mysqli_connect_error()) {
            ENGINE::error(
                'can\'t connect: (' . mysqli_connect_errno() . ') '
                . mysqli_connect_error() . "\n" .
                $this->_host . "\n" .
                $this->_user . "\n" .
                $this->_password . "\n" .
                $this->_port
            );
        }
    }
}

/**
 * вариант xDatabase для работы с mysql
 */
class xDatabaseLapsi extends xDatabase
{
    /** @var bool|Memcache */
    // private $mcache = false;
    protected $_debug = false;

    function notempty($res)
    {
        return is_resource($res);
    }

    function affectedrow()
    {
        if(!is_null($this->db_link))
            return mysql_affected_rows($this->db_link);
        else
            return mysql_affected_rows();
    }

    function insertid()
    {
        if(!is_null($this->db_link))
            return mysql_insert_id($this->db_link);
        else
            return mysql_insert_id();
    }

    /**
     * конструктор
     *
     * @param string $option параметры
     */
    function __construct($option = '')
    {
        parent::__construct($option);
        $this->set_option(ENGINE::slice_option('database.'));
        // ENGINE::debug(ENGINE::slice_option('database.'));
        if ($this->_init) {
            $this->db_link = mysql_connect(
                $this->_host,
                $this->_user,
                $this->_password
            );
            if (empty($this->db_link)) {
                ENGINE::error(
                    'can\'t connect: '
                    . mysql_error() . "\n" .
                    $this->_host . "\n" .
                    $this->_user . "\n" .
                    $this->_password
                );
            }
            mysql_select_db($this->_base);
        }

        //$this->prefix = ENGINE::option('database.prefix', 'xsite');
        if ($option = ENGINE::option('database.options')) {
            $this->set_option($option);
        }

        if ($this->_init) {
            $this->query('set names ?x', $this->_code);
        }
    }

    /**
     * выполнить запрос с параметрами.
     * sql парсится и дополняется параметрами.
     *
     * @param array $arg Запрос + параметры запроса
     * @param bool $cached кэшировать или нетъ
     *
     * @return resource
     */
    protected function _query($arg, $cached = false)
    {
        $start = 0;
        if ($this->_debug) {
            $start = microtime(true);
        }
        $sql = $this->_($arg);
        if ($cached) {
            $this->cachekey = ENGINE::option('cache.prefix', 'y') . md5($sql);
            if (false !== ($result = $this->cache($this->cachekey))) {
                if (false !== ($result = $this->cache($this->cachekey))) {
                    if ($this->_debug) {
                        $arg = $this->debug;
                        $arg[] = '~function|_query';
                        $arg[] = '~shift|1';
                        array_unshift(
                            $arg,
                            'QUERY(cache)' .
                            sprintf('[%f]', microtime(true) - $start) .
                            ': ' . $sql . "\n"
                        );
                        call_user_func_array(array('ENGINE', 'debug'), $arg);
                    }
                    return $result;
                }
                return $result;
            }
        }
        //ENGINE::debug( 222/* ,$this->db_link */);
        if (!$this->_test) {
            if(is_null($this->db_link))
                $result = mysql_query($sql/*, $this->db_link*/);
            else
                $result = mysql_query($sql, $this->db_link);
            //ENGINE::debug($result);
            if (!$result) {
                ENGINE::error(
                    'Invalid query: ' . (!is_null($this->db_link)?mysql_error($this->db_link):mysql_error()) . "\n" . 'Whole query: ' . $sql
                );
            } else {
                $this->q_count += 1;
            }
        } else {
            ENGINE::debug(
                "QUERY-TEST:\n" . $sql . "\n", '~function|_query', '~shift|1'
            );
            $result = false;
        }
        if ($this->_debug) {
            $arg = $this->debug;
            $arg[] = '~function|_query';
            $arg[] = '~shift|1';
            array_unshift(
                $arg,
                'QUERY' . sprintf('[%f]', microtime(true) - $start) .
                ': ' . $sql . "\n" . (!is_null($this->db_link)?mysql_info($this->db_link):mysql_info())
                . "\n" . (!is_null($this->db_link)?mysql_error($this->db_link):mysql_error())
            );
            call_user_func_array(array('ENGINE', 'debug'), $arg);
        }

        return $result;
    }

    function fetch($result)
    {
        return mysql_fetch_assoc($result);
    }

    function escape($str)
    {
        if(!is_null($this->db_link)){
            return @mysql_real_escape_string($str, $this->db_link);
        } else
            return '' . @mysql_real_escape_string($str);
    }

    function free($result)
    {
        if ($this->notempty($result)) {
            mysql_free_result($result);
        }

    }

    /**
     * закрыть неправеднооткрытое.
     * Не нужно, но чистота требует жертв
     */
    function __destruct()
    {
        if (!empty($this->db_link)) {
            mysql_close($this->db_link);
            $this->db_link = null;
        }
    }

}

