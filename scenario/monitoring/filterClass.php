<?php
/**
 * Рудиментарная фильтрация в командной строке
 * User: s.koryakin
 * Date: 25.06.2018
 * Time: 13:42
 * Фильтры выглядят как строковые значения, разные для каждого столбца таблицы. Язык описания фильтров :
 * - условия разделяются либо символом операции, либо символами `,;`? либо и тем и другим. Если условия разделены
 * символами операции, считается, что они разделены `,`
 * - условие состоит из символа операции и операнда. Если символ операции отсутствует - считается, что он `=`
 * - символы операции - `> >= < <= =` (5 операций)
 * - за символом операции следует операнд, до следующего символа-разделителя, либо до конца строки
 * - если в операнде присутствует `*` операнд воспринимается как маска. Звездочка в этой маске изображает любое значение.
 * - если необходимо указать пустое значение - необходимо использовать операцию =
 * - Если разделитель `,` - условия склеиваются оператором AND, ';' - OR
 *
 * Существует helper - выпадающий список для столбцов, у которых количество разных значений не очень велико. При клике на
 * значение в списке - это значение помещается в поле фильтра, заменяя все имеющиеся там значения. Если при этом
 * держать Shift - значение добавится в поле, через символ `;`
 *
 * примеры условий
 * >=1<=3 - значения в диапазоне от 1 до 3, аналог >=1,<=3
 * Мин*петер* - маска для значений, которые начинаются с буквосочетания `мин`. в середине имеют буквосочетание `петер`.
 * При этом регистр символов не учитывается.
 * 1;=;2 - выводятся все поля со значением 1,2 и пустым значением
 *
 * класс в процессе фильтрации собирает информацию для блока данных смарти.
 *
 * Без зависимостей
 *
 * Версия для использования в формах
 * Приходящие данные - массив полей (filter[]), каждое значение может быт как & (,) так и  || (;)
 *
 */


class filterClass
{
    /** @var int сколько уникальных значений будем считать приемлемыми для выдачи в окне фильтра */
    var $maxCount = 45;

    private
        /** @var array - массив условий, разбит на or-and блоки */
        $conditions,
        /** @val array - данные для смарти */
        $distinct = array();

    /**
     * filterClass constructor.
     * @param int $max - иногда нужно прибить выпадающие окошки, или разумно ограничить размер
     */
    function __construct($max = 45)
    {
        $this->maxCount = $max;
    }

    /**
     * состряпать операцию
     * @param string $op
     * @param string $val
     * @return array
     */
    private function newop($op = '=', $val = '')
    {
        if ($op == '>') {
            return array('min' => (int)$val);
        } else if ($op == '>=') {
            return array('min' => (int)$val - 1);
        } else if ($op == '=' && '' === trim($val)) {
            return array('reg' => '/^\s*$/');
        } else if ($op == '<') {
            return array('max' => (int)$val);
        } else if ($op == '<=') {
            return array('max' => (int)$val + 1);
        } else if ($op == '|=' or $op == '!=') {
            return array('notmask' => $val);//
            //'/^\s*' . str_replace('\*', '.*', preg_quote(mb_strtolower($val,'utf-8'))) . '/iu');
        } else {
            return array('mask' => $val);//
            // '/^\s*' . str_replace('\*', '.*', preg_quote(mb_strtolower($val,'utf-8'))) . '/iu');
            //return array('like' =>  '%'. str_replace('*','%',addcslashes(mb_strtolower($val,'utf-8'),'%_')) . '%');
        }
    }

    protected function parseCond(){

    }

    /**
     * парсинг строки кондиций. Обычно, они прилетают из GET в виде
     * filter[SHOW_NAME]=*якутия&filter[STATUS_NAME]=Архивный&filter[x]=>5<10;=15
     * @param $get
     */
    public function createConditions($get)
    {
        if (isset($get) && is_array($get)) {
            foreach ($get as $key => $val) {
                // fill distint value
                if (!isset($this->distinct[$key])) $this->distinct[$key]['val'] = $val;
                // split to OR blocks
                $orvalues = explode(';', $val);
                $or = array();
                foreach ($orvalues as $orval) {
                    $andvalues = explode(',', $orval);
                    $and = array();
                    foreach ($andvalues as $andval) if ('' != ($andval = trim($andval))) {
                        if (preg_match_all('/\|=|!=|=|<=|<|>=|>/', $andval, $m, PREG_OFFSET_CAPTURE)) {
                            //echo '<!--xxx--'.var_export($m,true).'-->';
                            $m[0][] = array('', strlen($andval));
                            foreach ($m[0] as $k => $x) {
                                if ('' === $x[0]) break;
                                $and[] = $this->newop(substr($andval, $x[1], strlen($x[0])),
                                    substr($andval, $x[1] + strlen($x[0]), $m[0][$k + 1][1]));
                            }
                        } else {
                            $and[] = $this->newop('=', $andval);
                        }

                    }
                    $or[] = $and;
                }
                $this->conditions[$key] = $or;
            }
        }
       echo '<!--xxx--'.$this->createsql().'-->';
        return $this->conditions;
    }

    /**
     * фильтрация данных+ сборка информации для данных смарти
     * @param $users
     * @return array
     */
    public function filter($users,$alias=[])
    {
        $conditions = $this->conditions;
        $that = $this;
        $user = array_filter($users, function ($val) use ($that, $conditions,$alias) {
            foreach ($val as $k => $v) {
                if(isset($alias[$k])) $k=$alias[$k];
                if (!isset($that->distinct[$k])) $that->distinct[$k]['dis'] = array();
                if (false === $that->distinct[$k]['dis']) continue;
                if ('' !== trim($v))
                    if (count($that->distinct[$k]['dis']) < $that->maxCount) {
                        $that->distinct[$k]['dis'][trim($v)] = 1;
                    } else {
                        $that->distinct[$k]['dis'] = false;
                    }
            }
            if (!empty($conditions)) {
                foreach ($conditions as $key => $cond) {
                    if(isset($alias[$key])) $key=$alias[$key];
                    $value=is_object($val)? $val->getProperty($key):$val[$key];
                    $or = false;
                    foreach ($cond as $_or) {
                        $and = true;
                        foreach ($_or as &$_and) {
                            if (isset($_and['mask']) && !isset($_and['reg'])) {
                                $_and['reg'] = '/^\s*' . str_replace('\*', '.*', preg_quote(mb_strtolower($value, 'utf-8'))) . '/iu';
                            }
                            if (isset($_and['notmask']) && !isset($_and['notreg'])) {
                                $_and['notreg'] = '/^\s*' . str_replace('\*', '.*', preg_quote(mb_strtolower($value, 'utf-8'))) . '/iu';
                            }
                            if (isset($_and['reg']) && !preg_match($_and['reg'], $value)) {
                                $and = false;
                                break;
                            }
                            if (isset($_and['notreg']) && preg_match($_and['notreg'], $value)) {
                                //echo '<!--' . var_export($and, true) . var_export($val[$key],true).'!'.preg_match($and['reg'], $val[$key]).'-->';
                                $and = false;
                                break;
                            }
                            if (isset($_and['min']) && $_and['min'] >= $value) {
                                $and = false;
                                break;
                            }
                            if (isset($_and['max']) && $_and['max'] <= $value) {
                                $and = false;
                                break;
                            }
                        }
                        $or = $or || $and;
                    }
                    if (!$or) return false;
                }
            }
            return true;
        });
        foreach ($this->distinct as $k => &$v) {
            if (!empty($v['dis']))
                ksort($v['dis']);
        }
        return $user;
    }

    public function createsql($alias=[]){
        $sql='';
        if (!empty($this->conditions)) {
            $and = [];
            foreach ($this->conditions as $key => $cond) {
                if (isset($alias[$key])) $key = '`'.$alias[$key].'`'; else $key='`'.$key.'`';
                $or = [];

                foreach ($cond as $_or) {
                    $x_and = [];
                    foreach ($_or as $_and) {
                        if (isset($_and['mask'])) {
                            $x_and[] = $key . ' like \'%'. str_replace('*','%',addcslashes(mb_strtolower($_and['mask'],'utf-8'),'%_')) . '%\'';
                            continue;
                        }
                        if (isset($_and['notmask'])) {
                            $x_and[] = $key . ' not like \'%'. str_replace('*','%',addcslashes(mb_strtolower($_and['notmask'],'utf-8'),'%_')) . '%\'';
                            continue;
                        }
                        if (isset($_and['min'])) {
                            $x_and[] = $key . ' <= ' . $_and['min'];
                            continue;
                        }
                        if (isset($_and['max'])) {
                            $x_and[] = $key . ' >= ' . $_and['max'];
                            continue;
                        }
                    }
                    $or[]=implode(' and ', $x_and);
                }
                if(count($or)>1){
                    $and[] = '('.implode(') or (', $or).')';
                } else
                    $and[] =  $or[0];
                //$and[] = implode(' or ', $or);
            }
            if(count($and)>1){
                $sql = '('.implode(') and (', $and).')';
            } else
                $sql= $and[0];
        }
        return $sql;
    }

    /**
     * отдай и не греши...
     * @return array
     */
    public function getFilterInfo()
    {
        //array('ORGANISATION'=>['val'=>x, 'dis'=>[]];
        //
        return $this->distinct;
    }
}