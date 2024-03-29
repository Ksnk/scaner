<?php
/**
 * this file is created automatically at "11 Jun 2013 11:33". Never change anything,
 * for your changes can be lost at any time.
 */
class tpl_compiler extends tpl_base
{
    function __construct()
    {
        parent::__construct();
    }

    function _class($par)
    {
        $result = '<?php
/**
 * this file is created automatically at "'
            . (date('d M Y G:i'))
            . '". Never change anything,
 * for your changes can be lost at any time. 
 */ 
class tpl_'
            . (isset($par['name']) ? $par['name'] : "")
            . ' extends tpl_'
            . ($this->filter_default((isset($par['extends']) ? $par['extends'] : ""), 'base'))
            . ' {
function __construct(){
parent::__construct();';
        $loop1_array = self::ps($par['macro']);
        if (is_array($loop1_array) && !empty($loop1_array)) {
            foreach ($loop1_array as $m) {

                $result .= '
$this->macro[\''
                    . ($m)
                    . '\']=array($this,\'_'
                    . ($m)
                    . '\');';
            }
        }
        ;
        $loop1_array = self::ps($par['import']);
        if (is_array($loop1_array) && !empty($loop1_array)) {
            foreach ($loop1_array as $imp) {

                $result .= '$'
                    . ($imp)
                    . '=new tpl_'
                    . ($imp)
                    . '();
$this->macro=array_merge($this->macro,$'
                    . ($imp)
                    . '->macro);';
            }
        }
        ;
        $result .= '
}';
        $loop1_array = self::ps($par['data']);
        if (is_array($loop1_array) && !empty($loop1_array)) {
            foreach ($loop1_array as $func) {

                $result .= '
'
                    . ($func);
            }
        }
        ;
        $result .= '
}';
        return $result;
    }

    function _callmacro($par)
    {
        $result = 'if(!empty($this->macro[\''
            . (isset($par['name']) ? $par['name'] : "")
            . '\']))
$result.=call_user_func($this->macro[\''
            . (isset($par['name']) ? $par['name'] : "")
            . '\'],array(';
        $loop1_array = self::ps($par['parkeys']);
        if (is_array($loop1_array) && !empty($loop1_array)) {
            foreach ($loop1_array as $p) {

                $result .= '\''
                    . $this->func_bk($p, 'key')
                    . '\'=>'
                    . $this->func_bk($p, 'value')
                    . ',';
            }
        }
        ;
        $result .= ')';
        if ((isset($par['param']) && !empty($par['param']))) {

            $result .= ','
                . ($this->filter_join((isset($par['param']) ? $par['param'] : ""), ', '));
        }
        ;
        $result .= ')';
        return $result;
    }

    function _callmacroex($par)
    {
        $result = (isset($par['par1']) ? $par['par1'] : "")
            . '->'
            . (isset($par['mm']) ? $par['mm'] : "")
            . '( '
            . ($this->filter_join((isset($par['param']) ? $par['param'] : ""), ', '))
            . ' )';
        return $result;
    }

    function _set($par)
    {
        $result = (isset($par['id']) ? $par['id'] : "")
            . '='
            . (isset($par['res']) ? $par['res'] : "");
        return $result;
    }

    function _for(&$par)
    {
        $result = '@$loop'
            . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
            . '_array=self::ps('
            . (isset($par['in']) ? $par['in'] : "")
            . ');';
        if ((isset($par['loop_index']) && !empty($par['loop_index']))) {

            $result .= '$loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_index=0;';
        }
        ;
        if ((isset($par['loop_last']) && !empty($par['loop_last']))) {

            $result .= '$loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_last=count($loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_array);';
        }
        ;
        if ((isset($par['loop_revindex']) && !empty($par['loop_revindex']))) {

            $result .= '$loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_revindex=$loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_last+1;';
        }
        ;
        if ((isset($par['loop_cycle']) && !empty($par['loop_cycle']))) {

            $result .= '$loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_cycle='
                . (isset($par['loop_cycle']) ? $par['loop_cycle'] : "")
                . ';';
        }
        ;
        $result .= '
if ((is_array($loop'
            . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
            . '_array) && !empty($loop'
            . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
            . '_array))||($loop'
            . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
            . '_array instanceof Traversable)){
foreach($loop'
            . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
            . '_array as '
            . (isset($par['index']) ? $par['index'] : "");
        if ((isset($par['index2']) && !empty($par['index2']))) {

            $result .= ' =>'
                . (isset($par['index2']) ? $par['index2'] : "");
        }
        ;
        $result .= '){';
        if ((isset($par['loop_index']) && !empty($par['loop_index']))) {

            $result .= '    $loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_index++;';
        }
        ;
        if ((isset($par['loop_revindex']) && !empty($par['loop_revindex']))) {

            $result .= '    $loop'
                . (isset($par['loopdepth']) ? $par['loopdepth'] : "")
                . '_revindex--;';
        }
        ;
        $result .= '
'
            . (isset($par['body']) ? $par['body'] : "")
            . '
}}';
        if ((isset($par['else']) && !empty($par['else']))) {

            $result .= '
else {
'
                . (isset($par['else']) ? $par['else'] : "")
                . '
}';
        }
        ;
        return $result;
    }

    function _callblock($par)
    {
        $result = '';
        $x = (isset($par['name']) ? $par['name'] : "");
        if ($x) {

            $result .= '$this->_'
                . ($x)
                . '($par)';
        }
        ;
        return $result;
    }

    function _block($par)
    {
        $result = '';
        if ((isset($par['name']) && !empty($par['name']))) {

            if ((((isset($par['tag']) ? $par['tag'] : "")) == ('macros'))) {

                $result .= '
function _'
                    . (isset($par['name']) ? $par['name'] : "")
                    . '($namedpar';
                $loop1_array = self::ps($par['param']);
                if (is_array($loop1_array) && !empty($loop1_array)) {
                    foreach ($loop1_array as $p) {

                        $result .= ',$'
                            . $this->func_bk($p, 'name');
                        if ($this->func_bk($p, 'value')) {

                            $result .= '='
                                . $this->func_bk($p, 'value');
                        } else {

                            $result .= '=0';
                        }
                        ;
                    }
                }
                ;
                $result .= '){
extract($namedpar);';
            } else {

                $result .= '
function _'
                    . (isset($par['name']) ? $par['name'] : "")
                    . '(&$par){';
            }
            ;
        }
        ;
        $loop1_array = self::ps($par['data']);
        $loop1_index = 0;
        if (is_array($loop1_array) && !empty($loop1_array)) {
            foreach ($loop1_array as $blk) {
                $loop1_index++;

                if ($this->func_bk($blk, 'string')) {

                    $xxx = $this->filter_join($this->func_bk($blk, 'string'), '
    .');
                    if (($loop1_index == 1) && ((isset($par['name']) && !empty($par['name'])))) {

                        $result .= '
$result='
                            . ($xxx)
                            . ';';
                    } elseif ((($xxx) != ('\'\''))) {

                        $result .= '
$result.='
                            . ($xxx)
                            . ';';
                    }
                    ;
                } else {

                    $result .= '
'
                        . $this->func_bk($blk, 'data')
                        . ';';
                }
                ;
            }
        }
        ;
        if ((isset($par['name']) && !empty($par['name']))) {

            $result .= '
    return $result;
}';
        }
        ;
        return $result;
    }

    function _if($par)
    {
        $result = '';
        $if_index = 1;
        $if_last = count((isset($par['data']) ? $par['data'] : ""));
        $loop1_array = self::ps($par['data']);
        if (is_array($loop1_array) && !empty($loop1_array)) {
            foreach ($loop1_array as $d) {

                if ((($if_index) == (1))) {

                    $result .= 'if( '
                        . $this->func_bk($d, 'if')
                        . ' ) {
'
                        . $this->func_bk($d, 'then')
                        . '
}';
                } elseif (($this->func_bk($d, 'if')) || ((($if_index) != ($if_last)))) {

                    $result .= '
elseif( '
                        . $this->func_bk($d, 'if')
                        . ' ) {
'
                        . $this->func_bk($d, 'then')
                        . '
}';
                } else {

                    $result .= '
else {
'
                        . $this->func_bk($d, 'then')
                        . '
}';
                }
                ;
                $if_index = (($if_index) + (1));
            }
        }
        ;
        return $result;
    }

    function _($par)
    {
        $result = ($this->_class($par))
            . ($this->_callmacro($par))
            . ($this->_callmacroex($par))
            . ($this->_set($par))
            . ($this->_for($par))
            . ($this->_callblock($par))
            . ($this->_block($par))
            . ($this->_if($par));
        return $result;
    }
}