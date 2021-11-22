<?php
/**
 * смастерить новую версию проекта
 *
 * получить текущую мажорную и минорную версии (x.y) из файла version.txt

вывести список всех git-тегов данного релиза x.y.*

обнаружить патч версию (z) последнего тега релиза

добавить новый тег вида x.y.(z+1)

при необходимости добавить постфикс окружения (x.y.z-dev)
 */

use Ksnk\scaner\console;

include '../vendor/autoload.php';

//$version='1.1';
$console=new console();

// вывод всех веток удаленнных репозиториев
//  git for-each-ref --format='%(refname:short) %(objecttype)'
// ваще все!
// git for-each-ref --format='%(refname:short) %(objecttype)' refs/remotes/origin
// отправить поставленные теги  refs/tags
// git push origin --tags

$console
    ->run("git for-each-ref --format='%(refname:short)' refs")->end()
    ->syntax([
        'name'=>'\S+',
        'type'=>'\S+',
        'line'=>[':current:\s+:branch:','.+?']
    ],
        //'/^\s*:current:\s+:branch:\s*$/m'
    '/^:line:$/m'
        , function ($res)
        {
            print_r($res);
        });
    echo $console->getbuf();
    $console
    ->run('git tag')->end()
    ->syntax([
        'prefix'=>'[\D\S]*',
        'major'=>'\d+',
        'minor'=>'\d+',
        'lo'=>'\d+',
        'suffix'=>'\S*?',
    ],
        '/^:prefix::major:\.:minor:\.:lo::suffix:\s*$/m', function ($res)
    {
       print_r($res);
    });


