#"Джентльменский" набор классов.

Набор классов для решения специфических задач, возникающих при администрировании.

##x-parser

Класс для быстрой интеграции файлов в систему.

### getParameters($method,$class_name,$include='')

Если указан первый параметр - информация выводится только для него, если не указан ('') - для всех методов класса. Если укзан параметр `include` и !class_exists() для этого класса, то выполнится include_once. Не все в этом мире PSR-0, не все...

    /**
     * @param integer $a : select[1:a|2:b|3:c]  А вот!
     */
    function justforTest($a=3){

В виде результата будет выглядеть так:

     array (
       'x_parserTest' =>
       array (
         'justforTest' =>
         array (
           'title' => '',
           'param' =>
           array (
             'a' =>
             array (
               'type' => 'select',
               'function' => 'x_parserTest::justforTest',
               'name' => 'x_parserTest::justforTest[a]',
               'default' => 3,
               'title' => 'А вот!',
               'values' =>
               array (
                 0 => '1:a',
                 1 => '2:b',
                 2 => '3:c',
               ),
             ),
           ),
         ),
       ),
     )

Общий вид описателя параметров

@param [type0] {varname} [: {text|textarea|select|button|radio|checkbox} \[1:a|2:b|3:c\]] [{description}]

- type0 - стандартный для DocType тип переменной, используется IDE(Штормом) естественноым образом.
- после символа `:` идет слово - тип поля
- после него - в квадратных скобках, разделенные | записаны возможные значения, для полей типа select, radio и checkbox.
- description - текст для метки

###createInput($opt=array(),$values=array())

Ввести html-control для ввода значения в соответствии с указанным типом.
Для примера из предыдущего параграфа

     <label>А вот!
       <select name="x_parserTest::justforTest[a]">
         <option value="1">a</option>
         <option value="2">b</option>
         <option selected value="3">c</option>
       </select>
     </label>

Первым параметром функции будет раздел `param` c именем переменной `$par[__CLASS__]['justforTest']['param']['a']`

Второй параметр - $_POST или $_GET или их сохраненная копия. Если в массиве нет значения - берется значение по умолчанию из описания функции.