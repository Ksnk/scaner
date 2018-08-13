<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

/**
 * Работа с сайтом konik.ru - сканирование всех товаров.
 * Class konik_scan_scenario
 * @tags nastia
 */
class konik_scan_scenario extends scenario
{

    /**
     * @var spider
     */
    var $spider,
        /**
         * @var string - Начальная страница поиска
         */
        $url,$state,$brandsurl=array(),

        $pages_scanned=false,

        $found_itemurl=array(),
        $found_items=array(),$yml,

        $brandlist=array();


    static $single=null;

    function prepBrandName($brand){
        return strtoupper(str_replace(' ','',$brand));
    }

    /**
     * сценарий открытия сайта
     */
    function open_konik($url)
    {
        $this->spider
            ->open($url);
        $this->joblist->append_scenario('scan_item_pages');
    }

    static function get($jobs,$par){
        if (!self::$single) {
            self::$single = new self($jobs);
            self::$single->spider= new spider();
            if(!empty($par))
                self::$single->handle('load',$par);
        }
        return self::$single;
    }

    function WriteYml($top){
        if($top){
            $handle=fopen($this->yml,'w');
            fwrite($handle,// 2016-05-18 12:10
                '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="'.date('Y-m-d H:i').'">
    <shop>
        <name>konik.ru</name>
        <company>konik.ru</company>
        <currencies>
            <currency id="RUB" rate="1" />
        </currencies>
        <categories/>
        <offers>
'
            );
            fclose($handle);
        } else {
            $handle=fopen($this->yml,'a');
            fwrite($handle,'
        </offers>
    </shop>
</yml_catalog>'
            );
            fclose($handle);
        }
    }

    /**
     * Сканирование карточки товара
     * @param $url
     */
    function scanitem($url)
    {
        if (!empty($url)) {
            $this->spider
                ->open($url);
        }
        $res=array();

        $this->spider
            ->scan('window.ad_product')
            ->until('</script>')
            ->doscan('~"([^"]+)"\:\s*(?:"([^"]+)"|([\d\.]+))~usm',1,'name',2,'value',3,'value2');
        $result=$this->spider->getResult();
        foreach($result['doscan'] as $x){
            $res[$x['name']]=!empty($x['value'])?$x['value']:$x['value2'];
        }
        $this->spider->position(0);
        $this->spider
            ->doscan('~<a[^>]+href="([^"]+)"\s+rel="item_galery"~usm',1,'url');
        $result=$this->spider->getResult();
        $res['img']=array();
        foreach($result['doscan'] as $x){
            $res['img'][]=$x['url'];
        }
        $res['img']=array_unique($res['img']);//[]=$x['url'];

        $this->spider->position(0);
        $this->spider
            ->scan('>Характеристики<')
            ->until('item-wrap item-wrap-price')
            ->doscan('~class="title">([^<]+)</div>\s+<div class="value">(.*?)</div>~us',1,'attr',2,'value');
        $result=$this->spider->getResult();
        foreach($result['doscan'] as $x){
            $res['var'][$x['attr']]=trim(preg_replace('~\s\s+~',' ',strip_tags($x['value'])));
        }
        $this->spider
            ->scan('~<h2>Описание:</h2>(.*?)</div>\s*<div class="category-block"~ms',1,'descr');
        $result=$this->spider->getResult();

        //print_r($result);
        if(empty($res['id'])) return ;
        $res['descr']=trim(strip_tags($result['descr']));
        foreach($res['var'] as $k=>$v){
            $res['descr'].="\n".$k.': '.$v;
        }
//print_r($res);
        $handle=fopen($this->yml,'a');
        $tab="\t";
        fwrite($handle,
            '<offer id="'.$res['id'].'" type="vendor.model" available="true">
'.$tab.'<url>'.$res['url'].'</url>
'.$tab.'<vendor>'.$res['vendor'].'</vendor>
'.$tab.'<vendorCode>'.$res['var']['Артикул'].'</vendorCode>
'.$tab.'<price>'.$res['price'].'</price>
'.$tab.'<currencyId>RUB</currencyId>
'
        );
        foreach($res['var'] as $k=>$v){
            fwrite($handle,
                $tab.'<param name="'.$k.'">'.$v.'</param>
'
            );
        }
        foreach($res['img'] as $img){
            fwrite($handle,
            $tab.'<picture>'.$this->spider->buildurl($img).'</picture>
'
            );
        }
        fwrite($handle,
            ''.$tab.'<name>'.$res['name'].'</name>
'.$tab.'<description>'.$res['descr'].'</description>
'.$tab.'<param name="stock">1</param>
</offer>
'
        );
        fclose($handle);
    }

    /**
     * Головной метод сканирования
     * сканируем страницу брендов и выдаем что нужно
     * @param $res
     * @param $yml_file
     */
    function scan_brands($res,$yml_file=''){
        if (!empty($res)) {
            $this->spider
                ->open($res);
        }
        $this->state=1;
        if(!empty($yml_file))
            $this->yml=$yml_file;
        $this->brandsurl=array();
        do {
            $this->spider->until()
                ->scan('<div class="brand_item_wrapper"');
            if($this->spider->found){
                $this->spider
                    ->until('</div>')
                    ->scan('~itemprop="name">(.+?)</span>~',1,'brand')
                    ->scan('~<a[^>]+href="([^\"]+)"~', 1,'url');
                $result=$this->spider->getresult();
                unset($result[0]);
                if(isset($result['url']))
                    $this->brandsurl[$result['brand']]=$result['url'];
            }
        } while($this->spider->found);

    }

    /**
     * сканируем страницу каталога с возможной страничной навигацией
     *
     */
    function scan_item_pages($res){
        //echo ">>> ".$res."<br>";
        $this->state=2;
        if (!empty($res)) {
            $this->spider
                ->open($res);
        }
        do {
            $this->spider->until()
                ->scan('~<div class="main-cat-item"[^>]*>[^<]*'.
				'<a class="main-cat-block" href="([^"]+)"~ms',1,'url');
            if($this->spider->found){
                $result=$this->spider->getresult();
                unset($result[0]);
                if(isset($result['url']))
                    $this->found_itemurl[$result['url']]=true;
            }
        } while($this->spider->found);

        $this->spider->position(0);
        $this->spider
            ->scan('<div class="navigation-pages">')
            ->until('</div>')
            ->scan('nav-current-page active')
            ->scan('~href="([^"]+)">\d+</a>~ms',1,'url');
        if($this->spider->found){
            $result=$this->spider->getResult();
            $this->joblist->append_scenario('scan_item_pages',array(preg_replace('/(yclid|utm_.*?)=[^&]+&|amp;/','',$result['url'])),false);
        }
    }

    function handle($event="none",$par=null){
        switch($event){
            case 'complete':
                switch($this->state){
                    case 1: // страница брендов прочитана
                        if(!empty($this->brandsurl)){
                            foreach($this->brandsurl as $b=>$url){
                                if(in_array($this->prepBrandName($b),$this->brandlist)){
                                    $this->joblist->append_scenario('scan_item_pages',array($url));
                                }
                            }
                        }
                        $this->brandsurl=array();
                        break;
                    case 2: // страница брендов прочитана
                        if(!empty($this->found_itemurl)){
                            $this->joblist->append_scenario('WriteYml',array(true));
                            printf( "<br>found %s item links<br>",count($this->found_itemurl));
                            foreach($this->found_itemurl as $url=>$k){
                                $this->joblist->append_scenario('scanitem',array($url));
                            }
                            $this->joblist->append_scenario('WriteYml',array(false));
                            //    print_r($this->found_itemurl);
                            $this->found_itemurl=array();
                        } else {
                            echo 'error. Complete without founded items';
                        }
                        break;
                }
                break;
            case 'load':
                if(!empty($par['lasturl']))
                    $this->spider->lasturl=$par['lasturl'];
                if(!empty($par['founditem']))
                    $this->found_itemurl=$par['founditem'];
                if(!empty($par['brandsurl']))
                    $this->brandsurl=$par['brandsurl'];
                if(!empty($par['brandlist']))
                    $this->brandlist=$par['brandlist'];
                if(!empty($par['yml']))
                    $this->yml=$par['yml'];
                break;
            case 'store':
                return array(
                    'lasturl'=>$this->spider->lasturl,
                    'founditem'=>$this->found_itemurl,
                    'brandsurl'=>$this->brandsurl,
                    'brandlist'=>$this->brandlist,
                    'yml'=>$this->yml,
                );
                break;
        }
        return false;
    }

    /**
     * Прочитать страничку Konik.ru и собрать информацию о товарах.
     * @param string $brands :textarea  список брендов через запятую
     * @param string $yml_file: text имя YML файла
     */
    function do_readKonik($brands='Gotz, Larsen, Trunki, Sentosphere, Vtech, GENII CREATION, Roommates',$yml_file='konik.yml'){
        foreach(explode(',',$brands) as $b){
            $this->brandlist[]=$this->prepBrandName($b);
        }
        $this->joblist->append_scenario('scan_brands',array("https://www.konik.ru/brands/",$yml_file));
       // $this->joblist->append_scenario('scan_item_pages',array('https://www.konik.ru/catalog/toys/igrushki_ot_genii_creation/'));
       // $this->joblist->append_scenario('scanitem',array("https://www.konik.ru/catalog/toys/chemodan_na_kolesikakh_edinorog_una_goluboy-0287-GB01.html"));
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
