<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

/**
 * Работа с сайтом http://www.rfbr.ru/rffi/ru/ - сканирование части разделов, книг и конкурсов
 * Class rffi_scan_scenario
 * @tags rfbr
 */
class rffi_scan_scenario extends scenario
{

    /**
     * @var spider
     */
    var $spider,
        /**
         * @var string - Начальная страница поиска
         */
        $url,

        $pages_scanned=false,

        $found_itemurl=array(),
        $found_items=array(),

        $maxitem=5;


    static $single=null;

    /**
     * сценарий открытия сайта
     */
    function open_braindesign($url)
    {
        $this->spider
            ->open($url);
        $this->joblist->append_scenario('scan_item_pages');
    }

    static function get($par){
        if (!self::$single) {
            self::$single = new self($par);
            self::$single->spider= new spider();
        }
        return self::$single;
    }

    /**
     * Сканирование карточки товара
     * @param $res
     * @param $articul
     */
    function scanitem($url)
    {
        if (!empty($url)) {
            $this->spider
                ->open($url);
        }
        $res=array('img'=>[],'info'=>[],'authors'=>[]);
        $this->spider
            ->scan('~<h1.*?>(.*?)</h1~us',1,'title')
            ->until('<dl class="views')
            ->doscan('~src="([^"]+)"~us',1,'img');
        $result=$this->spider->getResult();
        $res['title']=trim($result['title']);
        foreach($result['doscan'] as $x){
            $res['img'][]=$x['img'];
        }
        $this->spider
            ->until()
            ->until('<h2')
            ->doscan('~<dt.*?>(.*?):?</dt.*?<dd>(.*?)</dd>~us',1,'key',2,'val');
        $result=$this->spider->getResult();
        foreach($result['doscan'] as $x){
            $res['info'][$x['key']]=$x['val'];
        }
        $this->spider
            ->until()
            ->until('<p class="l-3"')
            ->doscan('~<a.*?>(.*?)</a.>~us',1,'author');
        $result=$this->spider->getResult();
        foreach($result['doscan'] as $x){
            $res['authors'][]=$x['author'];
        }

        $this->spider
            ->until()
            ->scan('~гранта:(.*?)</~us',1,'grant')
            ->scan('~<p class="pb-20.*?>(.*?)</p>~us',1,'anounce');
        $result=$this->spider->getResult();
        $res['anounce']=$result['anounce'];
        $res['grant']=$result['grant'];
        print_r($res);

    }

    /**
     * сканируем главную страницу с товарами
     *
     *
     */
    function scan_book_pages($res)
    {
        if (!empty($res)) {
            $this->spider
                ->open($res);
        }
        $this->spider->until()
            ->scan('<table class="table">')
            ->until('</table')
            ->doscan('~<tr.*?href="([^"]+)"~ms'
                        , 1, 'url');

        $result = $this->spider->getresult();
        if(!empty($result['doscan']))
            foreach($result['doscan'] as $v)if($this->maxitem-- > 0){
                $this->joblist->append_scenario('scanitem',array($v['url']));
            }
/*
        $this->spider->until()
            ->scan('<div class="pager')
            ->doscan('<a class="pager-in-link" href="([^"]+)"',1,'url');
        $result = $this->spider->getresult();
        if(!empty($result['doscan']))
            foreach($result['doscan'] as $v){
                $this->joblist->append_scenario('scan_book_pages',array($v['url']));
            }
*/
    }

    /**
     * Прочитать страничку http://www.rfbr.ru
     */
    function do_scanbooks(){
        $this->joblist->append_scenario('scan_item',array('http://www.rfbr.ru/rffi/ru/books/o_2073568'));
        //$this->joblist->append_scenario('scan_book_pages',array("http://www.rfbr.ru/rffi/ru/books/"));
    }

    /**
     * Прочитать 1 страницу с товарами.
     */
    /*
    function do_test1page(){
        $this->joblist->append_scenario('scanitem',array("https://bragindesign.ru/product/tobonn-орех/"));

    }
    */

}
