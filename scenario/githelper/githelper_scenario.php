<?php
/**
 * Created by PhpStorm.
 * User: ksnk
 * Date: 08.11.17
 * Time: 10:03
 * @tags git
 */
class githelper_scenario {

    /**
     * Установить публичный ключ в систему
     * @param string $key :file[*.pub] выбрать файл, или
     * @param string $rsa:textarea добавить содержимое публичного ключа
     */
    function do_installkeys($key='',$rsa=''){
        $console= new console();
        $console->begin('ssh root@libercode.ru')->run('cat /home/git/.ssh/authorized_keys')->end();
        if(!$console->success){
            echo $console->cmd."\n"."\n".$console->getbuf();
            return;
        }
        $console->doscan('~(\S+)\s+(\S+)\s+(\S+)\n~ums',1,'type',2,'body',3,'sign') ;
        $result=$console->getResult();
        //print_r($result);

        $contents='';
        if(''!=trim($rsa)){
            $contents=trim($rsa);
        } else if(is_readable($key)){
            $contents=file_get_contents($key);
        }

        foreach($result['doscan'] as $l){
            if($contents==($l['type'].' '.$l['body'].' '.$l['sign'])){
                echo "Ключ уже установлен в системе";
                return;
            }
        }
        $console->begin('ssh root@libercode.ru')->run('echo '.$contents.' >> /home/git/.ssh/authorized_keys"')->end();
        if($console->success){
            echo "Ключ установлен в системе";
            if(is_readable($key))unlink($key);
        } else {
            echo $console->cmd."\n"."\n".$console->getbuf();
        }
    }

    /**
     * поставить git на новый проект
     * @param $project:text Имя проекта
     */
    function do_git_new_project($project){
       // echo $project."\n".$_SERVER['DOCUMENT_ROOT']."/../".$project.'.libercode.ru';
        $console= new console();
        $console->current_dir='/var/www/vhosts/'.$project.'.libercode.ru';
        $console->begin('ssh root@libercode.ru')->run('git','status')->end()->scan('Not a git repository');
        if($console->found){
            // ставим git
            $console->begin('ssh root@libercode.ru')->run('git','init')
                ->run('cp /opt/git/.gitignore .')
                ->run('find . -name *.tar.gz >> .gitignore')
                ->run('git config  user.email "git@libercode.ru" ; git config  user.name "GIT"; git config --global core.safecrlf false;')
                ->run('git add httpdocs/.')
                ->end();
            echo $console->cmd."\n\n";
            echo $console->getbuf();
            $console->begin('ssh root@libercode.ru')->run('git','init')
                ->run('git commit -m "First commit"')
                ->end();
            echo $console->cmd."\n\n";
            echo $console->getbuf();
            $console->begin('ssh root@libercode.ru')->run('git','init')
                ->run('git clone --bare . /opt/git/'.$project.'.git')
                ->run('git remote add shared /opt/git/'.$project.'.git')
                ->run('git push shared master')
                ->run('chown -R git .git')
                ->run('chown -R git /opt/git/'.$project.'.git')
                ->end();
            echo $console->cmd."\n\n";
            echo $console->getbuf();
        } else {
            echo $console->cmd."\n\n";
            echo $console->getbuf();
        }
    }




    /**
     * команда git
     * @param $project :text Имя проекта
     * @param $cmd команда
     */
    function do_rungit($project,$cmd){
        $console= new console();
        $console->current_dir='/var/www/vhosts/'.$project.'.libercode.ru';
        $console->begin('ssh root@libercode.ru')->run($cmd)->end();
        echo str_replace('<','&le;',$console->getbuf());
    }

    /**
     * phpinfo
     */
    function do_phpinfo(){
        phpinfo();
    }
}