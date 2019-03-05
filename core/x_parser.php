<?php

namespace Ksnk\scaner;

/**
 *
 * Класс для парсинга Doсtype информации и создания контролов для утилит.
 *
 * Описатели полей
 * @param [type] $var [:type][\[values\]] [description]
 * пример:
 * @param int|string $c :select[one|3:two|4:four|five] 3-й параметр
 * @param int|string $c :select[:none|~dir('*.log')] 3-й параметр
 * @param string $c :text 3-й параметр
 *
 * - указатель типа - игнорируется, если указан тип через :, используется для контроля типов Штормом
 * - В квадратных кавычках после типа - перечисление значений-меток для check, radio и select
 *   либо ~dir('../*.log'), ~dir(config.xxx)
 * Конструкция config.XXX заменяется на поле XXX
 */
class x_parser {

    static $_int;

    static $templates=array(
        'textarea'=>'<label for="{{UID}}">{{label}}</label><textarea {{attr}} id="{{UID}}">{{value}}</textarea>',
        'radiogroup'=>'<fieldset><legend>{{label}}</legend>{{radio}}</fieldset>',
        'radio_option'=>'<div class="radio">
  <label><input type="radio" name="{{name}}" {{checked}}>{{label}}</label>
</div>',
        'checkbox'=>'<div class="checkbox">
  <label><input type="checkbox" name="{{name}}[]" value="{{value}}" {{checked}}>{{label}}</label>
</div>',
        'file'=>'<div class="upload"><label class="file_upload dropzone">
    <input type="file" name="<?= $file_uploader_name ?>[]" multiple="multiple">
</label></div>',
    );

    static private $UID_CNT=1000;

    static function UID(){
        return 'x'.(self::$UID_CNT++);
    }


    private static $_templates=array(
        'select'=>array(
            '<label>{{title}}<select name="{{name}}">{{_values}}</select>',
            '_values'=>'<option name={{name}}>'
        ),
        '*'=>'<label>{{title}}<input type="{{type}}" name="{{name}}"[ value="{{value}}"]/></label>',

    );

    static function template($type,$template=''){

    }

    static function gotByName($values,$name=''){
        $result=null;
        if(isset($name) && false===strpos($name,'[') && isset($values[$name])){
            $result=$values[$name];
        } else if(isset($name) && false!==strpos($name,'[')) {
            $parts=preg_split('/\]\[|\[|\]/',$name);
            $cur=&$values;
            for($i=0;$i<count($parts)-1;$i++){
                if(isset($cur[$parts[$i]])) {
                    $cur=&$cur[$parts[$i]];
                    if(is_string($cur))
                        $result=stripslashes($cur);
                    else
                        $result=$cur;
                }
                else {
                    $result=null;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * $opt [{title: текст лабеля, type: тип контрола, tag: тег контрола}]
     *
     * @param array $opt
     * @param array $values
     * @return array|string
     */
    static function createInput($opt=array(),$values=array()){
        //print_r($opt);
        if(!isset($opt['type']))
            $opt['type']='text';
        if($opt['type']=='textarea') ;
        elseif($opt['type']=='select') $result=array('select');
        elseif($opt['type']=='button') $result=array('button');
        else {
            $result=array('input','type="'.$opt['type'].'"');
        }
        if(empty($opt['title'])){
            $opt['title']=$opt['parname'];
        }

        if(isset($opt['name']))
            $result[]='name="'.$opt['name'].'"';
        if(isset($opt['id']))
            $result[]='id="'.$opt['id'].'"';

        if(isset($opt['maxlength']))
            $result[]='maxlength="'.$opt['maxlength'].'"';

        if(isset($opt['class'])){
            $cv=is_array($opt['class'])?implode(' ',$opt['class']):$opt['class'];
            $result[]='class="'.$cv.'"';
        }
        $value=self::gotByName($values,$opt['name']);//null;
        if(is_null($value)){
            if(isset($opt['default']))
                $value=$opt['default'];
        }
        if($opt['type']=='select'){
          //  $result=implode(' ',$result).'>';
            if(!isset($opt['values'])) $opt['values']=array();
            $result='';
            foreach($opt['values'] as $v){
                if(false!==($kk=strrpos($v,':'))){
                    $k=substr($v,0,$kk);
                    $v=substr($v,$kk+1);
                } else {
                    $k=$v;
                }

                $selected=($value==$k?' selected':'');

                $result.=self::tpl(array(
                    'name'=>$opt['name'],
                    'label'=>$v,
                    'value'=>$k,
                    'selected'=>$selected,
                ),self::$templates['select_option']);
            }
            return self::tpl(array(
                'label'=>isset($opt['title'])?$opt['title']:'',
                'UID'=>self::UID(),
                'name'=>$opt['name'],
                'radio'=>$result,
            ),self::$templates['select']);
        } elseif($opt['type']=='radio' || $opt['type']=='checkbox'){

            if(!isset($opt['values'])) {
                $opt['values']=array();
                if(isset($opt['value']))
                    $opt['values'][]=array($opt['value']);
                else
                    $opt['values'][]=1;
            }
            $result='';
            foreach($opt['values'] as $v){
                if(false!==($kk=strpos($v,':'))){
                    $k=substr($v,0,$kk);
                    $v=substr($v,$kk+1);
                } else {
                    $k=$v;
                }
                if(is_array($value))
                    $checked=in_array($k,$value)?' checked':'';
                else
                    $checked=($value==$k?' checked':'');
                $result.=self::tpl(array(
                    'name'=>$opt['name'],
                    'label'=>$v,
                    'value'=>$k,
                    'checked'=>$checked,
                ),self::$templates[$opt['type']=='checkbox'?'checkbox':'radio_option']);
            }
            return self::tpl(array(
                'label'=>$opt['title'],
                'radio'=>$result,
            ),self::$templates[$opt['type']=='checkbox'?'radiogroup':'radiogroup']);
            //$result=array(substr($result,1,strlen($result)-2));
        } elseif($opt['type']=='file' || $opt['type']=='files'){
            if(empty($opt['values'])){
                $opt['values']=array('*.*');
            }
            $files=array();
            foreach($opt['values'] as $mask){
               $files=array_merge($files,glob(TEMP_DIR.$mask));
            }
            $result='';
            foreach($files as $f){
                $result.=self::tpl(array(
                    'name'=>$opt['name'],
                    'label'=>$f,
                    'value'=>$f,
                ),self::$templates['select_option']);
            }

            return self::tpl(array(
                'UID'=>self::UID(),
                'name'=>$opt['name'],
                'label'=>$opt['title'],
                'radio'=>$result,
                //'attr'=>implode(' ',$result),
            ),self::$templates[$opt['type']]);
        } else {
            $tp=array(
                'textarea'=>'textarea',
               /* 'text'=>'labeledinput',
                'integer'=>'labeledinput',
                'int'=>'labeledinput'*/
            );
            if(isset($tp[$opt['type']])){
                $tpl=self::$templates[$tp[$opt['type']]];
            } else {
                $tpl=self::$templates['labeledinput'];
            }
            return self::tpl(array(
                'UID'=>self::UID(),
                'name'=>$opt['name'],
                'label'=>$opt['title'],
                'value'=>htmlspecialchars($value),
                'attr'=>implode(' ',$result),
            ),$tpl);
        }
       // return $result;
    }

    private static function reflect($class){
        return new \ReflectionClass($class);
    }

    static function getParameters($method,$class_name,$include=''){
        if(!empty($include)) include_once($include);
        $class = self::reflect($class_name);
        $action = new $class_name();
        $result=array();
        $doccomment=$class->getDocComment();
        if(preg_match('/@tags\s+(.*?)\s+$/m',$doccomment,$m)) {
            $result['tags'] = explode(',', $m[1]);
        } else {
            $result['tags'] = array('unknown');
        }
        //$result['_doc_comment']=$class->getDocComment();
        if(empty($method)) $methods=$class->getMethods(  );
        else $methods=array($class->getMethod( $method ));

        $prefered_lang='ru';
        foreach($methods as $method){
            $comment = $method->getDocComment();

            $descr=explode('@',preg_replace('#@[.\s\r\n]+|^\s*\/\*\*?|^\s*\*\/|^\s*\*#m','',$comment).'@');
            $result[$class_name][$method->name]['title']=trim($descr[0]);
            $result[$class_name][$method->name]['description']=$descr;
            $result[$class_name][$method->name]['param']=array();

            if (!empty($comment)) {
                $pars = $method->getParameters();
                $xname = $class_name . '::' . $method->name;
                foreach (array('menu'=>'{menu}[;{title}[;{tab}]]',
                             'context'=>'{menu}[;{title}[;{tab}]]',
                             'action'=>'{menu}[;{title}[;{tab}]]',
                             'template'=>'{template}',
                             'submit'=>'{button}[;{title}]') as $x=>$mask) {
                    if(!($res=preg_match('/@' . $x .'(?::'.preg_quote($prefered_lang). ')\s+([^\n]+)?/', $comment, $m)))

                        $res=preg_match('/@' . $x .'\s+([^\n]+)?/', $comment, $m);

                    if ($res) {
                        preg_match_all('/\{(\w+)\}/',$mask,$mm);
                        $mask=str_replace(
                            array('[',']'),
                            array('(?:',')?'),
                            $mask
                        );
                        if(count($mm[1])>0){
                            //ENGINE::debug($mask);
                            $mask=preg_replace(
                                array('#{\w+}\(\?\:;#','#{\w+}#'),array('([^;]*)(?:;','(.*)'),$mask
                            );
                        }
                        //ENGINE::debug($mask,$m[1],$mm[1]);

                        preg_match('/'.$mask.'$/',$m[1],$mmm);
                        foreach($mm[1] as $k=>$xx){
                            if(isset($mmm[1+$k])){
                                $result[$class_name][$method->name][$x][$xx]=$mmm[1+$k];
                            }
                        }
                    }
                }

                $xpar=array();
                foreach ($pars as $par) {
                    $xpar[$par->name] = array(
                        'type' => 'text',
                        'function' => $xname,
                        'name' => $xname.'['.$par->name.']',
                        //'title'=>$par->name,
                    );
                    if ($par->isOptional()) {
                        $xpar[$par->name]['default'] = $par->getDefaultValue();
                    }

                    $xx=preg_match(
                        '/@param:(?:'.preg_quote($prefered_lang).')\s+([\|\w]*).*?\$' . preg_quote($par->name)
                        . '[ ]*-?([^\n]+)?/',
                        $comment, $m
                    );

                    if(!$xx){
                        $xx=preg_match(
                            '/@param\s+([\|\w]*)\s*\$' . preg_quote($par->name)
                            . '[ ]*\-?([^\n]+)?/',
                            $comment, $m
                        );
                    }
                    //ENGINE::debug($comment,$xx,$m);
                    if ($xx) {
                        $xpar[$par->name]['type'] = empty($m[1]) ? 'text' : $m[1];
                        $xpar[$par->name]['title'] = empty($m[2]) ? '' : trim($m[2]);

                        if (preg_match(
                            '/(\s*:\s*[\.\w]+)?(?:\[([^\]]+)\])?[ ]*(.*)$/',
                            $xpar[$par->name]['title'], $m
                        )
                        ) {
                            if (!empty($m[1])) {
                                $xpar[$par->name]['type'] = trim($m[1], ': ');
                            }
                            if ('' != trim($m[2])){
                                $xxx = array();
                                foreach(explode('|', $m[2]) as $_x){
                                    if(preg_match('/~dir\(([^\(]+)\)/',$_x,$mm)){
                                        while(preg_match('/config\.(\w+)/',$mm[1],$mmm)){
                                            $mm[1]=str_replace('config.'.$mmm[1],$action->config[$mmm[1]],$mm[1]);
                                        }
                                        // echo $mm[1];
                                        $cwd=getcwd();
                                        chdir(dirname($class->getFileName()));
                                        foreach(glob($mm[1], GLOB_BRACE) as $g){
                                            $xxx[]=iconv('cp1251','utf-8',realpath($g)).':'.iconv('cp1251','utf-8',basename($g));
                                        }
                                        chdir($cwd);

                                    } else
                                        $xxx[]=$_x;
                                }
                                $xpar[$par->name]['values']=$xxx;
                            }
                            if ('' == trim($m[3]))
                                $xpar[$par->name]['title']=$par->name;
                            else
                                $xpar[$par->name]['title'] = $m[3];
                        }
                    }
                }

                $result[$class_name][$method->name]['param']=$xpar;
            }
        }
        return $result;
    }

    static function _ifelse($m){
        if(''!=trim(self::$_int[$m[1]])) return $m[2];
        if(empty($m[3])) return '';
        return $m[3];
    }

    static function tpl($par,$template){
        self::$_int=$par;
        $template=preg_replace_callback(
            '~{%\s*if\s*(.+?)\s*%}(.*?)(?:{%\s*else\s*%}\s*(.*?))?\s*{%\s*endif\s*%}~si',
            array(__CLASS__,'_ifelse'),
            $template
        );

        foreach($par as $k=>$v){
            $template=preg_replace('/{{\s*'.preg_quote($k).'\s*}}/i',$v,$template);
        }
        return $template;
    }

}