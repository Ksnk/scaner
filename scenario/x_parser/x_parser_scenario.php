<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 06.04.16
 * Time: 13:46
 */
namespace Ksnk\scaner;

use \phpMorphy, \phpMorphy_Exception;

/**
 * Проверка возможностей x_parcer
 * Class sqlfiddle_scenario
 * @tags ~debug
 */
class x_parser_scenario extends scenario {

    /**
     * 2+2=
     * @param $a
     * @param $b
     */
    function do_22($a=2,$b=2){
        echo $a+$b;
    }

    /**
     * Тестировать
     * @param string $a :radio[1:one|3:two|4:four|5:five] 1-й параметр
     * @param $b
     * @param int|string $c :select[one|3:two|4:four|five] 3-й параметр
     * @param array $d :checkbox[1:да|2:заодно и удалить] Полностью?
     */
    function do_test0($a,$b,$c=4,$d=array()){
        /*
        $parsed=parse_url($url0);
        if($parsed['scheme']=='https'){
            $values[':has_https']=1;
        } else {
            // собираем url обратно
            $parsed['scheme']='https';
            $urls = '';
            foreach (['scheme' => '%s:', 'host' => '//%s', 'path' => '%s', 'query' => '?%s'] as $k => $v) {
                if (!empty($parsed[$k])) $urls .= sprintf($v, $parsed[$k]);
            }
            $result = _check_url_get_info($http, $urls, array('method' => HTTP_Request2::METHOD_GET, 'http2_config' => array('store_body' => TRUE)));
            if (!$result['result']) {
                $values[':has_https']=0;
            }
        }
        $url404='';$parsed['path']='/i/d.like.to.check.404.page/';
        foreach (['scheme' => '%s:', 'host' => '//%s', 'path' => '%s', 'query' => '?%s'] as $k => $v) {
            if (!empty($parsed[$k])) $url404 .= sprintf($v, $parsed[$k]);
        }
        */
        $port = ($_SERVER["SERVER_PORT"] != '80' ? ':' . $_SERVER["SERVER_PORT"] : '');
        printf("%s\n\$a=`%s`,\$b=`%s`,\$c=`%s`, \$d=`%s`"
            ,'http'.(isset($_SERVER["HTTPS"])?'s':'').'://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]
            ,$a,$b,$c,empty($d)?'empty':implode(',',$d));
    }

    /**
     * проверка загрузки файла
     * @param string $file :file выберите файл
     * @param string $files :files выберите файлы
     */
    function do_fileupload($file,$files){
        print_r($file);print_r($files);
    }
    /**
     * phpinfo
     */
    function do_phpinfo(){
        $this->outstream(self::OUTSTREAM_HTML_FRAME);
        phpinfo();
    }

  /**
   * Morphy - разобрать предложение по частям речи
   * @param $words
   */
    function do_testMorphy($words){

      require_once (\Autoload::find("~/libs/phpmorphy-master/src/common.php"));

      if(!class_exists('phpMorphy')) {
        echo 'не установен phpMorphy';
        return;
      }
// set some options
      $opts = array(
        // storage type, follow types supported
        // phpMorphy::STORAGE_FILE - use file operations(fread, fseek) for dictionary access, this is very slow...
        // phpMorphy::STORAGE_SHM - load dictionary in shared memory(using shmop php extension), this is preferred mode
        // phpMorphy::STORAGE_MEM - load dict to memory each time when phpMorphy intialized,
        // this is useful when shmop ext is not activated.
        // Speed same as for phpMorphy::STORAGE_SHM type
        'storage' => phpMorphy::STORAGE_FILE,
        // Extend graminfo for getAllFormsWithGramInfo method call
        'with_gramtab' => true,
        // Enable prediction by suffix
        'predict_by_suffix' => true,
        // Enable prediction by prefix
        'predict_by_db' => true
      );

// Create phpMorphy instance
      try {
        $morphy = new phpMorphy(\Autoload::find("~/libs/phpmorphy-master/dicts"), 'ru_RU', $opts);
        //$morphy = new phpMorphy(new phpMorphy_FilesBundle(phpMorphy::getDefaultDictsDir(), 'ru_RU'), $opts);
      } catch(phpMorphy_Exception $e) {
        die('Error occured while creating phpMorphy instance: ' . $e->getMessage());
      }

// All words in dictionary in UPPER CASE, so don`t forget set proper locale
// Supported dicts and locales:
//  *------------------------------*
//  | Dict. language | Locale name |
//  |------------------------------|
//  | Russian        | cp1251      |
//  |------------------------------|
//  | English        | cp1250      |
//  |------------------------------|
//  | German         | cp1252      |
//  *------------------------------*
// $codepage = $morphy->getCodepage();
// setlocale(LC_CTYPE, array('ru_RU.CP1251', 'Russian_Russia.1251'));

// Hint: in this example words $word_one, $word_two are in russian language(cp1251 encoding)

      $list=preg_split('/\s+/ui',mb_strtoupper($words,'utf-8'));
      $bulk=[];
      foreach($list as $word_one) {
        $word=preg_replace('/[^ёЁа-я0-9]+/iu','',$word_one);
        if(''==trim($word_one)) continue;
        if(!preg_match('/^[ёЁа-я0-9]+$/iu',$word_one)){
          printf("getPartOfSpeech = %s; %s\n",$word_one,'?');
          continue;
        }
        if(''==trim($word)) continue;
        $bulk[]=$word;
        try {
          // word by word processing
          // each function return array with result or FALSE when no form(s) for given word found(or predicted)
          $base_form = $morphy->getBaseForm($word);
          $all_forms = $morphy->getAllForms($word);
          $pseudo_root = $morphy->getPseudoRoot($word);
          //printf("root = %s\n", var_export($pseudo_root, true));
          if (false === $base_form || false === $all_forms || false === $pseudo_root) {
            die("Can`t find or predict $word word");
          }

        //  printf("base form = %s\n", var_export($base_form, true));
        //  printf("all forms = %s\n", var_export($all_forms, true));
          printf("getPartOfSpeech = %s; %s\n",$word_one,
            implode(',',$morphy->getPartOfSpeech($word)));
          /*printf("findWord = %s\n",
            var_export($morphy->findWord($word_one), true));*/
          //printf("lemmatize = %s\n",
          //  implode(',',$morphy->lemmatize($word_one)));
          //printf("getAllFormsWithGramInfo = %s\n",
         //   var_export($morphy->getAllFormsWithGramInfo($word_one), true));

          // You can also retrieve all word forms with graminfo via getAllFormsWithGramInfo method call
          // $all_forms_with_gram = $morphy->getAllFormsWithGramInfo($word_one);
        } catch (phpMorphy_Exception $e) {
          die('Error occured while text processing: ' . $e->getMessage());
        }
      }
      printf("getPartOfSpeech = %s; %s\n",implode(' ',$bulk),
        var_export($morphy->getPartOfSpeech($bulk),true));

    }
    
}
