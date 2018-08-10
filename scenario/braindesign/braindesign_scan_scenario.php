<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

/**
 * Работа с сайтом bragindesign.ru - сканирование всех товаров.
 * Class braindesign_scan_scenario
 * @tags nastia
 */
class braindesign_scan_scenario extends scenario
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
        $found_items=array();

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
        $res=array();
        $this->spider
            ->doscan('~<figure itemprop="image".*?<a href="(.*?)"~us',1,'img');
        $result=$this->spider->getResult();
        $res['img']=array();
        foreach($result['doscan'] as $x){
            $res['img'][]=$x['img'];
        }
        //print_r($result);
        $this->spider->position(0);
        $this->spider
            ->scan('~<h1[^>]+class="product_title.*?">(.*?)</h1>~',1,'name')
            ->scan('~<meta itemprop="price" content="(\w+)"~',1,'price')
            ->scan('~<meta itemprop="priceCurrency" content="(\w+)"~',1,'currencyId')
            ->scan('~data-product-id="(\w+)"~',1,'id')
            ;
        $this->spider->position(0);
        $this->spider
            ->scan('~data-product_variations="([^"]+?)"~',1,'var')
            ->scan('~<li class="description_tab">\s*<div.*?/div>\s*<div[^>]*>\s*(.*?)\s*(?:</div>|<table class="shop_attributes")~ism',1,'descr')
            ->until('</table>')
            ->doscan('~<th>(.*?)</th>\s*<td.*?>\s*(.*?)\s*</td>~us',1,'attr',2,'value');
        $result=$this->spider->getResult();
        //print_r($result);
        if(empty($result['id'])) return ;
        if(isset($result['var']))
        $res['var']=json_decode(urldecode(html_entity_decode($result['var'])),true);
        $attr=array();
        foreach($result['doscan'] as $x){
            $attr[$x['attr']]=strip_tags(trim($x['value']));
        }
        $res['descr']=trim(strip_tags($result['descr']));
        foreach($attr as $k=>$v){
            $res['descr'].="\n".$k.': '.$v;
        }

        // so create an item card
        if(!isset($res['var'])){
            $res['var']=array(
                array(
                    'variation_id'=>$result['id'],
                    'display_price'=>$result['price'],
                    'attributes'=>array()
                )
            ) ;
        }
        foreach($res['var'] as $xx){
            $a=$attr;
            if(!empty($xx['attributes']))
            foreach($xx['attributes'] as $k=>$v){
                $a[preg_replace('~^attribute_~','',$k)]=$v;
            }
            $x=array(
                'id'=>$xx['variation_id'],
                'description'=>$res['descr'],
                'url'=>$url,
                'name'=>$result['name'],
                'price'=>$xx['display_price'],
                'currencyId'=>$result['currencyId'],
                'attr'=>$a,
            );
            $handle=fopen('tmp.yml','a');
            $tab="\t";
            fwrite($handle,
                '<offer id="'.$x['id'].'" type="vendor.model" available="true">
'.$tab.'<url>'.$x['url'].'</url>
'.$tab.'<vendor>Инриум</vendor>
'.$tab.'<vendorCode>'.$x['id'].'</vendorCode>
'.$tab.'<price>'.$x['price'].'</price>
'.$tab.'<currencyId>'.$x['currencyId'].'</currencyId>
'
            );
            foreach($x['attr'] as $k=>$v){
                fwrite($handle,
                    $tab.'<param name="'.$k.'">'.$v.'</param>
'
                );
            }
            foreach($res['img'] as $img){
                fwrite($handle,
                $tab.'<picture>'.$img.'</picture>
'
                );
            }
            fwrite($handle,
                ''.$tab.'<name>'.$x['name'].'</name>
'.$tab.'<description>'.$x['description'].'</description>
'.$tab.'<param name="stock">1</param>
</offer>
'
            );
            fclose($handle);
        }
    }

    function writeYML(){
        $handle=fopen('tmp.yml','a');
        fwrite($handle,
            '</offers>
</shop>
</yml_catalog>'
        );
        fclose($handle);
    }


    /**
     * сканируем главную страницу с товарами
     *
     *
     */
    function scan_item_pages($res){
        if (!empty($res)) {
            $this->spider
                ->open($res);
        }
        do{
            $this->spider->until()
                ->scan('<div itemscope');
        if($this->spider->found){
        $this->spider
           // ->until('<div itemscope')
            ->scan('~<h3>\s*<a\s+href="([^"]+)"\s*title="([^"]+)">~ms',1,'url',2,'name')
            ->scan('~<a[^>]+href="([^\"]+)" data-quantity="(\w+)"\s+data-product_id="(\w+)"\s+data-product_sku="(\w+)"~', 2,'q',3,'id',4,'sku');
            $result=$this->spider->getresult();
            unset($result[0]);
            if(isset($result['url']))
                $this->found_itemurl[$result['url']]=$result;
        }
        } while($this->spider->found);


            $this->spider->position(0);
            $this->pages_scanned=true;
            $this->spider
                ->scan('~<a class=[\'"]next page-numbers[\'"] href=[\'"]([^"\']+)[\'"]>~',1,'url');
            if($this->spider->found){
                $result=$this->spider->getresult();
                $this->joblist->append_scenario('scan_item_pages',array($result['url']));
            } else {
                echo 'found '.count($this->found_itemurl),' items'."<br>\n";
                foreach($this->found_itemurl as $url=>$item){
                    $this->joblist->append_scenario('scanitem',array($url));
                }
                $handle=fopen('tmp.yml','w');
                fwrite($handle,
                    '<?xml version="1.0" encoding="utf-8"?><!DOCTYPE yml_catalog SYSTEM "shops.dtd">
        <yml_catalog date="2016-05-18 12:10">
            <shop>
                <name>braginDesign.ru</name>
                <company>braginDesign.ru</company>
                <currencies>
                    <currency id="RUB" rate="1" />
                </currencies>
                <categories/>
<offers>
'
                );
                fclose($handle);
                $this->joblist->append_scenario('writeYML');
             }

    }

    /**
     * Прочитать страничку bragindesign.ru и собрать информацию о товарах.
     */
    function do_readBraindesign(){
        $this->joblist->append_scenario('scan_item_pages',array("https://bragindesign.ru/shop/"));
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
