<?php
/**
 *
 * Класс для парсинга Doсtype информации и создания контролов для утилит.
 *
 * Описатели полей
 * @param type $var :type[values] description
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

    /**
     * $opt [{title: текст лабеля, type: тип контрола, tag: тег контрола}]
     *
     * @param array $opt
     * @param array $values
     * @return array|string
*/

    static function createInput($opt=array(),$values=array()){
        if(!isset($opt['type']))
            $opt['type']='text';
        if($opt['type']=='textarea') $result=array('textarea');
        elseif($opt['type']=='select') $result=array('select');
        elseif($opt['type']=='button') $result=array('button');
        else {
            $result=array('input','type="'.$opt['type'].'"');
        }

        if(isset($opt['name']))
            $result[]='name="'.$opt['name'].'"';
        if(isset($opt['id']))
            $result[]='id="'.$opt['id'].'"';

        if(isset($opt['maxlength']))
            $result[]='maxlength="'.$opt['maxlength'].'"';
        $value=null;
        if(isset($opt['name']) && false===strpos($opt['name'],'[') && isset($values[$opt['name']])){
            $value=$values[$opt['name']];
        } else if(isset($opt['name']) && false!==strpos($opt['name'],'[')) {
            $parts=preg_split('/\]\[|\[|\]/',$opt['name']);
            $cur=&$values;
            for($i=0;$i<count($parts)-1;$i++){
                if(isset($cur[$parts[$i]])) {
                    $cur=&$cur[$parts[$i]];
                    $value=$cur;
                }
                else {
                    $value=null;
                    break;
                }
            }
        }
        if(is_null($value)){
            if(isset($opt['default']))
                $value=$opt['default'];
        }
        if($opt['type']=='select'){
            $result=implode(' ',$result).'>';
            if(!isset($opt['values'])) $opt['values']=array();
            foreach($opt['values'] as $v){
                if(false!==($kk=strpos($v,':'))){
                    $k=substr($v,0,$kk);
                    $v=substr($v,$kk+1);
                } else {
                    $k=$v;
                }
                $selected=($value==$k?' selected':'');
                if($k!=$v){
                    $result.='<option'.$selected.' value="'.$k.'">'.$v.'</option>';
                } else {
                    $result.='<option'.$selected.'>'.$v.'</option>';
                }
            }
            $result.='</select';
            $result=array($result);
        } elseif($opt['type']=='radio' || $opt['type']=='checkbox'){

            if(!isset($opt['values'])) {
                $opt['values']=array();
                if(isset($opt['value']))
                    $opt['values'][]=array($opt['value']);
                else
                    $opt['values'][]=1;
            }
            $xresult=$result; $result='';
            foreach($opt['values'] as $v){
                if(false!==($kk=strpos($v,':'))){
                    $k=substr($v,0,$kk);
                    $v=substr($v,$kk+1);
                } else {
                    $k=$v;
                }
                $checked=($value==$k?' checked':'');
                if($k!=$v){
                    $result.='<label><'.implode(' ',$xresult).' value="'.$k.'"'.$checked.'><span>'.$v.'</span></label>';
                } else {
                    $result.='<'.implode(' ',$xresult).' value="'.$k.'"'.$checked.'>';
                }
            }
            $result=array(substr($result,1,strlen($result)-2));
        } elseif($opt['type']=='textarea'){
            $result=array(implode(' ',$result).'>'.htmlspecialchars($value).'</textarea');
        } else {
            $result[]='value="'.htmlspecialchars($value).'"';
        }

        $result='<'.implode(' ',$result).'>';
        if(isset($opt['title'])){
            $result= '<label>'.$opt['title'].$result.'</label>';
        }
        return $result;
    }


    static function reflect($class){
        return new ReflectionClass($class);
    }

    static function getParameters($method,$class_name){
        $class = self::reflect($class_name);
        $action = new $class_name();
        $result=array();

        if(empty($method)) $methods=$class->getMethods(  );
        else $methods=array($class->getMethod( $method ));

        $prefered_lang='ru';
        foreach($methods as $method){
            $comment = $method->getDocComment();

            $descr=explode('@',preg_replace('#@[.\s\r\n]+|^\s*\/\*\*?|^\s*\*\/|^\s*\*#m','',$comment).'@');
            $result[$class_name][$method->name]['title']=trim($descr[0]);
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
                        if(count($mm[1])>1){
                            $mask=preg_replace(
                                array('#\{\w+\}\(\?\:;/#','#\{\w+\}/#'),array('([^;]+)(?:;','(.*)'),$mask
                            );
                        }
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
                        'name' => $method->name.'['.$par->name.']',
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
                                        foreach(glob($mm[1], GLOB_BRACE) as $g){
                                            $xxx[]=iconv('cp1251','utf-8',$g).':'.iconv('cp1251','utf-8',basename($g));
                                        }

                                    } else
                                        $xxx[]=$_x;
                                }
                                $xpar[$par->name]['values']=$xxx;
                            }
                            if ('' != trim($m[3]))
                                $xpar[$par->name]['title'] = $m[3];
                        }
                    }
                }

                $result[$class_name][$method->name]['param']=$xpar;
            }
        }
        return $result;
    }

}