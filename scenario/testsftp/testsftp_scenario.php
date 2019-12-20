<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 06.03.2019
 * Time: 12:03
 */

namespace Ksnk\scaner;
/**
 * @tags Тест sftp
 * @property mailer mailer
 */
class testsftp_scenario extends \Ksnk\scaner\scenario {

  /**
   * скачать csv
   * @param $uri
   * @param $filename
   */
  function do_csv($uri){
    $str=file_get_contents($uri);
    $csv=csv::csvStr($str);
    $top=10;$bot=10;$cnt=0;$bots=[];
    while(!empty($r=$csv->nextRow())) {
      $cnt++;
      if ($cnt<=$top) {
        printf("%s\n",str_replace('\\/','/',str_replace('\\"','"',json_encode($r, JSON_ERROR_NONE + JSON_UNESCAPED_UNICODE))));
      } else
        $bots[] = $r;
      if (count($bots) > $bot) array_shift($bots);
    }
    if($cnt>$top+$bot)
      echo "...\n";
    foreach($bots as $r)
      printf("%s\n",str_replace('\\/','/',str_replace('\\"','"',json_encode($r, JSON_ERROR_NONE + JSON_UNESCAPED_UNICODE))));
    printf("total %s lines\n",$cnt);
  }
  /**
   * dir и скачать, если есть
   * @param $uri
   * @param $filename
   */
  function do_test($uri, $filename=''){
    $tr=new ftp_transport($uri);
    print_r($dir=$tr->scan('.'));

    if(!empty($filename)){
      $reg=\UTILS::masktoreg($filename);
      foreach($dir as $d){
        if(preg_match($reg,$d['filename'])){
          $tr->get($d['filename'],'',$d['mtime']);
        }
      }
    };
    echo 'Ok';
  }

    /**
     * Проверить почту
     */
    function do_testmail(){
        $this->mailer->imap_open('{imap.gmail.com:993/imap/ssl/novalidate-cert/norsh}Inbox'
            ,'sergekoriakin@gmail.com'
            , 'ksnk17740481');
        $this->mailer->imap_search('');
    }
}