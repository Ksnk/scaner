# Классы приложения

Основное назначение проекта - обеспечить комфортную среду для отладки и обслуживания приложений на сайте. Для этого вместе с ядром системы ставится целевой сценарий, в котором уже и описываются все необходимые действия.

Ниже идет описание некоторых классов ядра системы.

## Autoload

Статический класс для поддержки PSR-0. Основное преимущество перед конкурентами - компактность, универсальность. Поддерживается запуск в phar и сканирование классов как в файловой системе (приоритет), так и внутри phar. При наличии - автоматически подключает autoload из composer'а. 

    \Autoload::map(['\\Ksnk\\scaner' => '']);
    
При поиске файлов все классы с таким префиксом будут искаться как классы без этого префикса.     

    \Autoload::register(['~/libs/template', '~/template']);
    
Добавляет каталоги для поиска файлов    


### Методы

- register(...) - параметры - массив каталогов, в которых будет инициироваться поиск. Заодно инициируется автолоад загрузчик.

        \Autoload::register(['~/libs/template', '~/template']);
    
- map - параметр - ассоциативный массив `класс`=>`имя файла без расширения`

        \Autoload::map(['\\Ksnk\\scaner' => '']);
        
- find - поиск по имени файла. В файловой системе, потом в phar, если он есть. Символ `~` в начале заменяется на индекс-каталог системы. (значение константы INDEX_DIR или каталог файла autoload.php )       
 
### Примеры

`Autoload::register(array('~/core', '~/libs', '~'));` - добавить каталоги, константа ~ заменяется на каталог, определенный в константе INDEX_DIR. Если константы нет - используется каталог, в котором размещается autoload.php.

`Autoload::map(array('xSphinxDB'=>'xDatabaseLapsi'));` - для инициализации класса xSphinxDB будет включен файл xDatabaseLapsi.php. 

## Scaner

Сканер предназначен для несложного парсинга текстов.
В качестве предмета анализа может быть строка, или имя файла.

Методы сканера

- found - логическое свойство. Устанавливается после каждого поиска. Было найдено что-то в результате поиска или не было.
- finish - длина анализируемого текста. Для текста в виде handle - не установлена.

- position(int) - установка курсора на указанную позицию в тексте.

- newbuf(string) - новый текст в виде строки

- newhandle(string|handle) - новый текст в виде имени файла или открытого handle.

- getResult - получить результат последнего сканирования.

- scan (reg/pattern,[N,Name,...])
Метод применяет регулярку или строку для поиска к тексту, начиная с текущего положения курсора. В случае успешного поиска - положение курсора сдвигается на последний символ найденой строки. Если используется регулярка - в результат поиска будут вписаны значения из захваченных масок регулярки с нужными именами. Если используется строка - в результате будет вся строка, содержащая подстроку.

- syntax - рудиментарный синтаксический разбор. Пример разбора атрибутов тега. 

         $scaner->scan('~<link~si')->until('~>~si')->syntax([
            'tag' => '\w+',
            'value' => '(?:"[^"]*"|\'[^\']*\'|[^\'">]*)'
         ], '~:tag:=:value:(?<fin>)~sm',
            function ($line) use (&$link) {
              if($line['tag']=='rel') $link['rel']=trim($line['value'],'"\'');
              if($line['tag']=='href') $link['href']=trim($line['value'],'"\'');
              return true;
            });
          $scaner->until();

## JobList

JobList - это класс, обеспечивающий хранение списка заданий и последовательное его исполнение.

Задания, обычно, оформляются в виде классов-сценариев.

На каждый файл сценария заводится область cохранения данных. Она восстанавливается-сохраняется прозрачно для сценария. Доступна как атрибут Joblist (обсуждается). При выполнении сценария, при первом создании списка задач, область данных обнуляется. весь вывод сценария стандартными функциями вывода, сохраняется в этой области, если нет возможности сразу отдать клиенту - вывести в консоль.

JL сам (явное выполнение команды из консоли или web приложения) может стартовать какой-то метод сценария. После этого сценарий может добавить новое задание в него, с указанием параметров.

**система событий**

Сценарий может быть остановлен на время, остановлен для ввода параметров пользователем (prompt). Сценарий реагирует на события пользователя - остановку/сброс сценария.

можно запустить на выполнение следующее задание, можно сохранить JL и восстановить его из сохранения.
Сохранение и восстановление списка заданий идет обычным json-сериалайзом.
Каждое задание атомарно и не может быть прервано. Это значит, что все задания должны выполняться быстро.
В частности, это значит, что если заданию таки надо выполняться долго,
то ему, заданию, должна быть предоставлена возможность добавиться еще раз в тот же JobList.

### Методы JL

- **store/load**, параметр - имя рессурса для хранения
- **appendJob**(array(class,method), paramlist) - добавить в список новое задание. Каждый параметр обязан быть сериализуемым
- **append_scenario** (callable, paramlist) - добавить новый метод из существующего сценария.
- **donext**(timeInSeconds) - продолжить выполнение задач, до исчерпания указаного количства секунд.

## Task

В качестве заданий в JobList вставляются специальные функции.

# Файлы сценариев

## Разметка файлов scenario

Сценарный файл представляет собой один или несколько файлов со специальной разметкой, по которой автоматически строятся элементы формы для ввода параметров и вызова функций класса.

В описании сценарного класса необходим тег **tags**

     /**
      * Class xparcer_scenario
      * @tags ~debug
      */

Теги можно перечислять через запятую, формы, определенные в классе, появятся во всех разделах с этими тегами. 
Перед названием тега возможно указание `~` - при этом этот тег не будет выводится в меню сценария, но добраться до сценария можно явно указав его в командной строке

Каждая функция сценария, имеющая префикс `do_` должна иметь полное описание в стиле phpDocs, иначе она будет игнорироваться при автоматическом построении форм.

    /**
     * Тестировать
     * @param string $a :radio[1:one|3:two|4:four|5:five] 1-й параметр
     * @param $b
     * @param int|string $c :select[one|3:two|4:four|five] 3-й параметр
     * @param array $d :checkbox[1:да|2:заодно и удалить] Полностью?
     */
    function do_test0($a,$b,$c=4,$d=array()){
      ...

Перед блоком описания параметров обязано быть описание функции, которое будет выводится в описании формы. Каждый параметр, может быть доопределен описателем типа, он начинается с символа `:`, после чего идет имя типа и, возможно, в квадратных скобках - дополнительные параметры. После формального описателя может следовать подпись к элементу. Если подписи нет - вместо него используется имя параметра. При описании дополнительных значений - `1:one` значение, которое принимает параметр `1`, а подпись, выводящаяся в форме `one`, если значения нет - значение будет совпадать с подписью.
 
 - **radio** `:radio[1:one|3:two|4:four|5:five] 1-й параметр` - будет сформирован html элемент radio, с элементами. перечисленными в квадратных скобках. Значением параметра будут одно из 1,3,4,5 в зависимости от выбора пользователя, подписью к каждому элементу radio будут `one two...`.
 
 - **select** `:select[one|3:two|4:four|five] 3-й параметр` - будет сформирован html элемент select, с элементами. перечисленными в квадратных скобках. Значением параметра будет одно из `one`,3,4,`five` в зависимости от выбора пользователя, подписью к каждому элементу radio будут `one two...`.
 
 - **checkbox** `:checkbox[1:да|2:заодно и удалить] Полностью?` - будет сформирован элемент checkbox, с элементами. перечисленными в квадратных скобках. Значением параметра будет массив с выбранными значениями.
 
 - **file** `:file[*.xml|*.yml] выберите файл` будет сформирован элемент select,
  со списком всех `xml` и `yml` из каталога `/tmp` в корне проекта, и  с возможностью загрузки файлов.  Файл будет подгружен в каталог `/tmp`
  проекта, если его  нет - можно его создать и обеспечить возможность перезаписи. Поле поддерживает Drag&drop из
  эксплорера Windows. В функцию прилетает полное имя файла в системе
                                                            
 - **textarea** - будет сформирован элемент textarea для ввода длинной строки с переводами строк.
  
 - **files** - `:files выберите файлы` поле для загрузки некольких файлов. По функционалу схож с file, но параметры в функцию передаются массивом 
 
## консольное приложение

Запуск любой сценарной функции выполняется из командной строки
        
        php index.php x3::master 1234,101 
          
будет вызвана функция do_master из сценария с тегом x3 (первая попавшаяся, если их несколько с таким тегом). Параметрами ее будут 1234 , 101.

        php index.php x3_scenario.master 1234,101 
        
будет вызвана функция do_master из сценария x3_scenario

За именем функции следует блок параметров, его формат описан ниже.        

Если параметры функции не указаны в адресной строке или в итерактивном режиме,будет требоваться ввести в консоль. Ввод параметров возможен через запятую, с указанием имени параметра

    1234,101
    
    result:101,responce:1234
    
    "1234", result:101
    
Параметры отделяются запятыми, каждый параметр может иметь наименование, в этом случае он будет поставлен в нужное место массива параметров, каждое значение параметра может быть взято в одинарные или двойные кавычки. Ведущие и замыкающие пробелы игнорируются, если значение не взято в кавычки. Внутри значения в кавычках можно использовать слеш, для экранирования символа кавычек.

## Web приложение (сценарий default)

Web приложение представляет собой сценарий с именем `default`. Если не переопределена константа DEFAULT_SCENARIO, будет активным именно он. 

Класс реализует заодно интерфейс Main, c конструктором и методами route, do_Default. Он определяет шаблон приложения и его логику.  

Шаблон сценария содержит меню - список всех тегов комплекта сценариев, и страничку с текущим отображаемым тегом. На странице выводится список названий всех форм этого тега и выбранная форма.

Шаблон используется в web варианте вызова приложения. Он позволяет запустить любую функцию любого сценария системы.

Поддерживается консольный вариант работы, который поддерживает запуск задач любого сценария из командной строки.  
 
Каждый web клиент запускает свою собственную версию сценарных скриптов, не пересекающуюся с одновременно выполняющимися версиями других клиентов, за исключением совместного использования общих рессурсов - файлов и базы данных. Это достигается сохранением клиентских данных в специальной области, уникальной для каждого клиента (`сейчас - json файл с именем сессии`) 

### Однократный вызов сценарной функции.

Тестируемый код вставляется как метод класса - сценария, с именем `do_***` и описанием с корректной разметкой в doc_type стиле. После этого функцию с таким именем можно вызвать из стандартного окна web приложения или из консоли. При этом можно указать нужные параметры.

Весь вывод функции, который осуществляется стандартными для php операторами вывода, вроде `echo`, будет помещен в область консольного вывода.

### Продолжительное выполнение.

Длительное выполнение осуществляется с помощью последовательного выполнения сценарныз фунций. В процессе выполнения можно добавить другую задачу на выполнение методами `Joblist`. Как правило - функции того же сценария (`append_scenario`) или другого (`append`). Добавленные задачи будут выполнены `JobList` в обычном порядке выполнения.

Весь вывод последовательно выполняющихся сценарных функций будет отправлен в область консольного вывода. Если выполнение происходит в web режиме, будет сделана остановка выполнения и продолжение через броузер c помощью ajax. Для консольного выполнения, тоже осуществляется подобный трюк, но только в случае, если установлено максимальное время выполнения php скрипта в консоли.

### Итерактивное приложение (мастер/ текстовый квест)

Приложение с итерактивными функциями (игра/вопрос-ответ). В этом режиме, сценарий имеет возможность остановиться и запросить реакцию пользователя, при этом режим вывода сценарной страницы сохраняется в задаче. Режим может определять, какие формы сценария нужно выводить в окне web-приложения при обработке этого сценария

# история переделок

- если после первого поста в очереди обнаруживаем задачи - задача прерывается до достижения таймаута и клиенту предлагается быстро послать iframe запрос на довыполнение оставшегося списка задач. (Не доделано... Пока в осмысленности этого действия сомневаюсь)

 