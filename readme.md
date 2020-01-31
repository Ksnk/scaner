# версия php.5.2 потенциально может работать на php 5.2, мастер затачивается на более свежую версию 

# Служебные утилиты

##Autoload

Статический класс для поддержки PSR-0. Основное преимущество перед конкурентами - компактность, универсальность.

Методы

- register(...) - параметры - массив каталогов, в которых будет инициироваться поиск. Заодно инициируется автолоад загрузчик.
- map - параметр - ассоциативный массив `класс`=>`имя файла без расширения`

Примеры

`Autoload::register(array('~/core', '~/libs', '~'));` - добавить каталоги, константа ~ заменяется на каталог, определенный в константе INDEX_DIR. Если константы нет - используется каталог, в котором размещается autoload.php.

`Autoload::map(array('xSphinxDB'=>'xDatabaseLapsi'));` - класс xSphinxDB размещается в файле xDatabaseLapsi.php

##Scaner

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

## JobList

JobList - это класс, обеспечивающий хранение списка заданий и последовательное его исполнение.

Интерфейс JL достаточно прост. В него можно поместить задание по имени-классу-файлу для инклюда, с указанием параметров,
можно запустить на выполнение следующее задание, можно сохранить JL и восстановить его из сохранения.
Сохранение и восстановление списка заданий идет обычным json-сериалайзом.
Каждое задание атомарно и не может быть прервано. Это значит, что все задания должны выполняться быстро.
В частности, это значит, что если заданию таки надо выполняться долго,
то ему, заданию, должна быть предоставлена возможность добавиться еще раз в тот же JobList.

Методы JL

- store/load, параметр - имя рессурса для хранения
- appendJob(array(class,method), paramlist) - добавить в список новое задание. Каждый параметр обязан быть сериализуемым
- doNextTask(self) - выполнить первое в списке задание. Параметр - ссылка на себя, родимого

##Task

В качестве заданий в JobList вставляются специальные функции.


#Web морда

Web клиент представляет собой страничку со списком всех возможных Task'ов и с возможностью вручную
установить параметры этих Task'ов. Все возможные Task'и - это объекты с родителем scenario и специальной разметкой в заголовках функций.
Для автоматизации сего процесса, используется специальный диалект
DocType. Для каждого параметра Task есть возможность уточнить его тип и содержимое.

Отмеченное задание может быть добавлено в список задач.

#история переделок

- если после первого поста в очереди обнаруживаем задачи - задача прерывается до достижения таймаута и клиенту предлагается быстро послать iframe запрос на довыполнение оставшегося списка задач