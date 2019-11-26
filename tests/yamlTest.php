<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 19.02.16
 * Time: 18:38
 */
use Symfony\Component\Yaml ;

Autoload::map(array(
    'Symfony\\Component\\Yaml\\' => 'libs\\yaml\\',
));


class yamlTest extends PHPUnit_Framework_TestCase {

    function testClassExists(){
        $this->assertNotEmpty(new Yaml\Yaml());
    }

    function test_02(){
        $yaml=new Yaml\Yaml();

        $data=<<<'DATA'
language: php

php:
  - 5.3
  - 5.4
  - 5.5
  - 5.6

install:
  - composer self-update
  - composer install --prefer-source

script:
  - mkdir -p build/logs
  - bin/phpunit --coverage-clover build/logs/coverage.xml

after_script:
  - bin/coveralls -v
---------------------------------------------------------
{"language":"php","php":[5.3,5.4,5.5,5.6],"install":["composer self-update","composer install --prefer-source"],"script":["mkdir -p build\/logs","bin\/phpunit --coverage-clover build\/logs\/coverage.xml"],"after_script":["bin\/coveralls -v"]}
---------------------------------------------------------
Date1: 2008-01-01
Date2: 01.02.2008
Time: 12:45
StringDate: "2008-01-01"
---------------------------------------------------------
{"Date1":1199134800,"Date2":"01.02.2008","Time":"12:45","StringDate":"2008-01-01"}
---------------------------------------------------------
Email list:
  - arc
  - &h hunter
  - engineer
SMS list:
  - *h # заменится на hunter
---------------------------------------------------------
{"Email list":["arc","hunter","engineer"],"SMS list":["hunter"]}
---------------------------------------------------------
node:
  ip: 10.0.0.51
  user: arc
  password: xidighei
---------------------------------------------------------
{"node":{"ip":"10.0.0.51","user":"arc","password":"xidighei"}}
---------------------------------------------------------
person:
    first_name: Sam
    encrypted_pass_phrase: "\r<\xD1\x8B\xC3\xF7\xE3\xE8\xF4\xB3\b\"D\xDB\xA9\x80"
---------------------------------------------------------
{"person":{"first_name":"Sam","encrypted_pass_phrase":"\r<\u00d1\u008b\u00c3\u00f7\u00e3\u00e8\u00f4\u00b3\b\"D\u00db\u00a9\u0080"}}
---------------------------------------------------------
x: >
  This is a very long sentence
  that spans several lines in the YAML
  but which will be rendered as a string
  without carriage returns.

---------------------------------------------------------
{"x":"This is a very long sentence that spans several lines in the YAML but which will be rendered as a string without carriage returns.\n"}
---------------------------------------------------------
simple: |
  one
  two
  three
continued: >
  one
  two
  three
---------------------------------------------------------
{"simple":"one\ntwo\nthree\n","continued":"one two three\n"}
DATA;

/* неработоспособные ямлы
generic: !!binary |
  R0lGODlhDAAMAIQAAP//9/X17unp5WZmZgAAAOfn515eXvPz7Y6OjuDg4J+fn5
  OTk6enp56enmlpaWNjY6Ojo4SEhP/++f/++f/++f/++f/++f/++f/++f/++f/+
  +f/++f/++f/++f/++f/++SH+Dk1hZGUgd2l0aCBHSU1QACwAAAAADAAMAAAFLC
  AgjoEwnuNAFOhpEMTRiggcz4BNJHrv/zCFcLiwMWYNG84BwwEeECcgggoBADs=
description:
  The binary value above is a tiny arrow encoded as a gif image.
---------------------------------------------------------

---------------------------------------------------------
png: !!binary     iVBORw0KGgoAAAANSUhEUgAAABUAAAAVCAIAAAAmdTLBAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAAte/PNwvDAKJ/owerAAAAAElFTkSuQmCC
---------------------------------------------------------

--- # Server
host: localhost
port: 80
--- # Client
user: root
homedir: /var/client/home
---------------------------------------------------------
png: !!binary |
    iVBORw0KGgoAAAANSUhEUgAAABUAAAAVCAIAAAAmdTLBAAAAAXNSR0IArs4c6QAAAARnQU1B
    AACxjwv8YQUAAAAgY0hSTQAAeiYAAICEAAD6AAAAgOgAAHUwAADqYAAAOpgAABdwnLpRPAAA
    te/PNwvDAKJ/owerAAAAAElFTkSuQmCC
---------------------------------------------------------
---------------------------------------------------------

---------------------------------------------------------
# Массив (как элемент хэша)
Moderators: [Site Admin, Dr.Moder, Polizei]
   # Хэш (как элемент хэша)
   Location: {host: localhost, port: 5432}
---------------------------------------------------------
    ? - 1
      - 2
      - 3: three
    :
      some digits
---------------------------------------------------------
*/

        $data=explode('---------------------------------------------------------',$data);
        while(count($data)>1){
            $src=array_shift($data);
            $act=trim(array_shift($data));
            $this->assertEquals($act,json_encode($yaml->parse($src)));
        }
    }
}
