<?php
/**
 * Created by PhpStorm.
 * User: Àíÿ
 * Date: 26.05.16
 * Time: 16:43
 *
 * - ïîñëàòü âñå çàêàçû â ñòîêêå
 * exchange/exchange.php?type=orders&mode=send
 */
class Stokke_Exchange {

    var $articul_voc=array(
        82217=>175201 , //Stokke® Xplory® Dark Navy
//175202 Stokke® Xplory® Blue
82215=>175203 , //Stokke® Xplory® Red
//175204 Stokke® Xplory® Beige
82219=>175205 , //Stokke® Xplory® Purple
//175206 Stokke® Xplory® Green
//175207 Stokke® Xplory® Light Green
//175208 Stokke® Xplory® Pink 3 In 1 Kit
82220=>175209 , //Stokke® Xplory® Brown
//175210 Stokke® Xplory® Blue Melange 2 In 1 Kit
98516=>175211 , //Stokke® Xplory® Black Melange
98515=>175212 , //Stokke® Xplory® Beige Melange
//175213 Stokke® Xplory® Urban Blue
//175214 Stokke® Xplory® 3in1 Orange Melange
100525=>175215 , //Stokke® Xplory® Black
100526=>175216 , //Stokke® Xplory® Deep Blue
110305=>175217 , //Stokke® Xplory® Grey Melange

        /*

        //101828 //Äîñòàâêà ïî Ìîñêîâñêîé îáëàñòè
        // 94409 Óñëóãè ïî æ¸ñòêîé óïàêîâêå òîâàðà
        // 93721 Óñëóãè ïî æ¸ñòêîé óïàêîâêå òîâàðà

       // Ëåòíèé êîìïëåêò Stokke Summer Kit
        108663=>409606,//Multi Strip 409606
        103196=>409601,//Faded Pink 409601
        103197=>409602,//Salty Blue
        103198=>409603,//Sandy Beige
        108662=>409604,//Bluebell Blue
        108664=>409605,//Peony Pink
        //Àâòîêðåñëî 0+ Stokke IZI Go by BeSafe Modular
        188855=>449001,// Black
        108854=>449002,// Black Melange
        188858=>449003,// Red
        108868=>449004,// Brown
        108864=>449005,// Beige Melange
        108857=>449006,// Purple
        108861=>449007,// Deep Blue
        110169=>449008,// Grey Melange
        //Êîëÿñêà 2 â 1 Stokke Crusi
        85449=>291201,//  Black Melange
//291202 Beige
//291203 Dark Navy
        83466=>291204,// Purple
        83465=>291205,// Red
        83464=>291207,// Brown
        98071=>291208,// Black
        98072=>291209,// Deep Blue
        109368=>291210,// Grey Melange
        93870=>291212,// Beige Melange
//291213 Urban Blue

// Stokke Tripp Trapp Newborn Textile Set
        //<variant product-id="186701"/>Blue Dots
                //<variant product-id="186702"/>Green Dots
                //<variant product-id="186703"/>Purple Dots
                //<variant product-id="186704"/>Tartan Blue
                //<variant product-id="186705"/>Tartan Pink
                //<variant product-id="186706"/>Silhouette Pink
                //<variant product-id="186707"/>Silhouette Blue
        93884=>186708, //Beige
        107105=>186709, //Aqua
        107104=>186710, //Pink
// Stokke® Trailz™
    //100674 brown
        99896=>435401, //Red
        99902=>435402, //Purple
        100673=>435403, //Black Melange
        99903=>435404, //Beige Melange
        //<variant product-id="435405"/>Urban Blue
        99898=>435406, //Black
        99901=>435407, //Deep Blue
        109367=>435409, //Grey Melange
    // Stokke® Stroller Carry Cot
        96057=>282301, //Black Melange
        //<variant product-id="282302"/>Beige
        83480=>282303, //Dark Navy
        83478=>282304, //Purple
        83477=>282305, //Red
          //<variant product-id="282306"/>Light Green
        83476=>282307, //Brown
        98528=>282308, //Black
        102210=>282309, //Deep Blue
        96058=>282312, //Beige Melange
        //<variant product-id="282313"/>Urban Blue
        //<variant product-id="282317"/>Grey Melange

        92142 =>105707,//Êîìïëåêò áåëüÿ 100x135 Stokke BedLinen(Classic White 105707)
        105366 =>408500,//×åõîë Stokke íà ìàòðàñèê ïåëåíàëüíîé äîñêè Home Changer êîìïëåêò 2 øò. (White 408500)

//Stokke® Scoot™
        99868=>365201, //Stokke® Scoot™ Black Melange
        //365202 Stokke® Scoot™ Beige Melange
		99867=>365203, //Stokke® Scoot™ Purple
		99869=>365204, //Stokke® Scoot™ Black
		104579=>365205, //Stokke® Scoot™ Soft Pink
		104580=>365206, //Stokke® Scoot™ Aqua Blue
		99870=>365207, //Stokke® Scoot™ Slate Blue
		99866=>365208, //Stokke® Scoot™ Red
//Ñóìêà äëÿ ìàìû Stokke Changing Bag
        81669=>177003, // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Red )
		81671=>177005, // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Purple )
		81673=>177011, // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Black Melange )
		81674=>177009, // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Brown )
		93265=>177012, // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Beige Melange )
		98062=>177016, // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Deep Blue  )
		98063=>177017 , // Ñóìêà äëÿ ìàìû Stokke Changing Bag(Grey Melange)
        98064=>177015,//Ñóìêà äëÿ ìàìû Stokke Changing Bag(Black 177015)
//  Stokke® Sleepi™ Bed Extension
        92115=>221905, // Êîìïëåêò áîêîâèíîê äëÿ êðîâàòè Stokke Sleepi(White )
		92116=>221901, // Êîìïëåêò áîêîâèíîê äëÿ êðîâàòè Stokke Sleepi(Natural )
		110189=>221907, // Êîìïëåêò áîêîâèíîê äëÿ êðîâàòè Stokke Sleepi(Hazy Grey )

//Stokke® Trailz™
		101609=>435401,//Stokke® Trailz™ Red
		101891=>435402,//Stokke® Trailz™ Purple
		101893=>435403,//Stokke® Trailz™ Black Melange
		101892=>435404,//Stokke® Trailz™ Beige Melange
    //435405 Stokke® Trailz™ Urban Blue
		101889=>435406,//Stokke® Trailz™ Black
		101890=>435407,//Stokke® Trailz™ Deep Blue
    //435409 Stokke® Trailz™ Grey Melange
//Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion
		106617=>100326, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Aqua Star )
		92036=>100303, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Beige Stripe )
		92038=>100321, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Silhouette Black )
		92039=>100322, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Silhouette Green )
		95443=>100323, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Grey Loom )
		102113=>100325, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Soft Stripe )
		106618=>100328, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Sweet Buterflies )
		106619=>100329, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Candy Stripe )
		106620=>100330, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Ocean Stripe )
		106621=>100334, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Hazy Tweet )
		106622=>100335, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Pink Tweet )
		108921=>100333, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Pink Chevron )
		108922=>100332, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Black Chevron )
		108923=>100327, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Retro Cars )
		108924=>100331, // Ïîäóøêà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Cushion(Grey Star )
//Ïðîñòûíü Stokke íà ðåçèíêå äëÿ êðîâàòè Home Bed êîìïëåêò 2 øò.
		105357=>408801, // Ïðîñòûíü Stokke íà ðåçèíêå äëÿ êðîâàòè Home Bed êîìïëåêò 2 øò.(White )
		105358=>408802, // Ïðîñòûíü Stokke íà ðåçèíêå äëÿ êðîâàòè Home Bed êîìïëåêò 2 øò.(White/Beige Checks )   
//Áàìïåð Stokke äëÿ êðîâàòè Home
		105353=>408401, // Áàìïåð Stokke äëÿ êðîâàòè Home(White )
		105354=>408402, // Áàìïåð Stokke äëÿ êðîâàòè Home(White/Beige Checks )

        105355=>409400,// Ìàòðàñèê Stokke ïîëèóðåòàíîâûé äëÿ êðîâàòè Home Mattress(409400)
//Êîíâåðò Stokke Sleeping Bag
		100551=>357303, // Êîíâåðò Stokke Sleeping Bag(New Purple )
		100559=>357301, // Êîíâåðò Stokke Sleeping Bag(New Navy )
		100760=>221509, // Êîíâåðò Stokke Sleeping Bag(Cloud Grey )
		106872=>221511, // Êîíâåðò Stokke Sleeping Bag(Onyx Black )
		106873=>221510, // Êîíâåðò Stokke Sleeping Bag(Pearl White )

		100744=>380401, // Êîìïëåêò çèìíèé óíèâåðñàëüíûé Stokke Winter Kit (Cloud Grey )
		106403=>380403, // Êîìïëåêò çèìíèé óíèâåðñàëüíûé Stokke Winter Kit (Onyx Black )
		106402=>380402, // Êîìïëåêò çèìíèé óíèâåðñàëüíûé Stokke Winter Kit (Pearl White )

        93883=>133200,// Ïðîñòûíü äëÿ ëþëüêè Stokke Xplory Fitted Sheet

		92040=>152100, // Ñòîëåøíèöà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Table Top( êèòû)
		106340=>152100, // Ñòîëåøíèöà äëÿ ñòóëü÷èêà Stokke Tripp Trapp Table Top( áóêâû)

        105943=>428501, // Ïîäíîñ Stokke Tripp Trapp Tray äëÿ êðåïëåíèÿ íà äåòñêèé ñòóë()

        108913=>449300,// Áàçà äëÿ àâòîêðåñëà Stokke IZI Go by BeSafe Modular IsoFix 449300

		110055=>431702, // Ðþêçàê Stokke MyCarrier Front(Deep Blue )
		110056=>431701, // Ðþêçàê Stokke MyCarrier Front(Brown )
		110057=>431703, // Ðþêçàê Stokke MyCarrier Front(Black )
		110058=>431705, // Ðþêçàê Stokke MyCarrier Front(Red )
		110059=>431704, // Ðþêçàê Stokke MyCarrier Front(Black Mesh )

        110054=>312201,// Íàãðóäíèê Stokke MyCarrier Bib(312201)

		98544=>104601, // Êîìïëåêò áîêîâèíîê äëÿ êðîâàòè Stokke Sleepi Junior(Natural )
		110193=>104608, // Êîìïëåêò áîêîâèíîê äëÿ êðîâàòè Stokke Sleepi Junior(Hazy Grey )
		98546=>104605, // Êîìïëåêò áîêîâèíîê äëÿ êðîâàòè Stokke Sleepi Junior(White )

		92025=>100107, // Ñòóëü÷èê Stokke Tripp Trapp(White )
		101652=>100125, // Ñòóëü÷èê Stokke Tripp Trapp(Storm Grey )
		92024=>100124, // Ñòóëü÷èê Stokke Tripp Trapp(Aqua Blue )
		92026=>100105, // Ñòóëü÷èê Stokke Tripp Trapp(Whitewash )
		92027=>100103, // Ñòóëü÷èê Stokke Tripp Trapp(Black )
		92028=>100102, // Ñòóëü÷èê Stokke Tripp Trapp(Red )
		92029=>100123, // Ñòóëü÷èê Stokke Tripp Trapp(Lava Orange )
		92030=>100113, // Ñòóëü÷èê Stokke Tripp Trapp(Green )
		92031=>100106, // Ñòóëü÷èê Stokke Tripp Trapp(Walnut Brown )
		92032=>100101, // Ñòóëü÷èê Stokke Tripp Trapp(Natural )
		106613=>100126, // Ñòóëü÷èê Stokke Tripp Trapp(Hazy Grey )
		106614=>100128, // Ñòóëü÷èê Stokke Tripp Trapp(Soft Pink )
		108919=>100127, // Ñòóëü÷èê Stokke Tripp Trapp(Wheat Yellow )

		110070=>349407, // Íîæêè Stokke Legs äëÿ ñòóëà Steps Oak Wood(Black )
		110069=>349406, // Íîæêè Stokke Legs äëÿ ñòóëà Steps Oak Wood(Natural )

    //
    //102648, // Êîëÿñêà-ëþëüêà Stokke Crusi
    //105585, // Êîëÿñêà-ëþëüêà Stokke Trailz
    //100549, // Àâòîêðåñëî 0+ Stokke iZi Sleep by BeSafe
    //81850,  // Àâòîêðåñëî 0+ Stokke iZi Go by BeSafe
 // óæå âñòàâëåíî â òàáëèöó
        */
    );

    var $stage='staging';

    var $export=null;

    /**
     * @return xDatabaseLapsi
     */
    function getDB(){
        static $db=false;
        if(!$db){
            ENGINE::set_option(array(
                'database.options'=>'nocache',
                'database.host' => 'localhost',
                'database.user' => 'ulapsi',
                'database.password' => 'G87FZOqw',
                'database.base' => 'u178433',
                'database.prefix' => '',

                'engine.aliaces' => array(
                    'Database' => 'xDatabaseLapsi'
                ),
                'noreport'=>true,
            ));
            $db=ENGINE::db('nocache');
            Autoload::register(array(
                realpath($_SERVER["DOCUMENT_ROOT"] . "/../stokkeshop.ru/system/model/lapsi"),
                realpath($_SERVER["DOCUMENT_ROOT"] . "/../m.lapsi.ru/system/model/lapsi"),
                realpath($_SERVER["DOCUMENT_ROOT"] . "/../m.lapsi.ru/system")
            ));
        }
        return $db;
    }

    /**
     * Çàãðóçêà ôàéëà íà ñàéò ñòîêêå.êîì â ñïåöèàëüíîå ìåñòî
     * @param $contents
     * @param $name
     * @param bool $is_filename - $content - ýòî èìÿ ôàéëà
     */
    function upload_file($contents,$name,$is_filename=false){
        $transport=$this->get_transport();
        if($transport){
            $transport->upload($contents,$name,$is_filename);
        }
    }

    function get_transport($create=true){
        static $tr=false;
        if(empty($tr) && $create){
            $tr= new sftp_transport(array(
                'host'=>'stoftp.stokke.com',
                'name'=>'ecom-russia',
                'password'=>'4Jznrt425P5QKK9V',
                'root'=>'/ecommerce/'
            ));
        }
        return $tr;
    }

    function log($message){
        error_log(date('Y/m/d H:i:s').'> '.trim($message)."\n",3,
            'tmp/exchange.log');
    }

    function gotafile($filename){
        $this->log('gotafile '.$filename);
        if(!!$this->export) $this->export->read($filename);
    }

    function receivefile($filename,$dir='tmp'){

        $src = fopen("php://input", 'r');
        if(!is_dir($dir))
        {
            mkdir($dir, 0755, true);
        }
        $dest = fopen($dir.'/'.$filename, 'wb');
        stream_copy_to_stream($src, $dest);// . " áàéò ñêîïèðîâàíî â first1k.txt\n";
        fclose($dest);
        $result=true;
        if (substr($filename,-4)=='.zip'){
            $zip = zip_open($dir.'/'.$filename);

            $dir.='/'.basename($filename,'.zip');
            $files = 0;
            $folders = 0;

            if ($zip) {
                while ($zip_entry = zip_read($zip)) {

                    $name = zip_entry_name($zip_entry);

                    $path_parts = pathinfo($name);
                    # Ñîçäåì îòñóòñòâóþùèå äèðåêòîðèè
                    if(!is_dir($dir.'/'.$path_parts['dirname']))
                    {
                        mkdir($dir.'/'.$path_parts['dirname'], 0755, true);
                    }

                    //$log->addEntry(array('comment' => 'unzip 2 ' . $name));
                    if (zip_entry_open($zip, $zip_entry, "r")) {
                        $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

                        $file = fopen($dir.'/'.$name, "wb");
                        if ($file) {
                            fwrite($file, $buf);
                            fclose($file);
                            $this->gotafile($dir.'/'.$name);
                            $files++;
                        } else {
                            $result=false;
                            //$log->addEntry(array('comment' => 'error unzipopen file '.$name));
                        }
                        zip_entry_close($zip_entry);
                    }
                }
                zip_close($zip);
            } else {
                // error
                $result=false;
            }

        } else {
            $this->gotafile($dir.'/'.$filename);
        }
        return $result;
    }


// --------------------------------------------------------------------------------------

    function handle_price_create(){
        //getIds
        $ids=$this->getIds();
        $data='';

        foreach($ids as $i){
            $item=Model_Item::get($i['item']);
            if($item->canbuy && $item->price){
                //ENGINE::debug($item->price,$item);
                $data.=sprintf('
            <price-table product-id="%s">
				<amount quantity="1">%s.00</amount>
			</price-table>',$i['articul'],$item->price);
            }
        }
        if(!empty($data)){
            $data='<?xml version="1.0" encoding="UTF-8"?>
<pricebooks xmlns="http://www.demandware.com/xml/impex/pricebook/2006-10-31">
	<pricebook>
		<header pricebook-id="RUB-ECRU-prices">
			<currency>RUB</currency>
			<display-name xml:lang="x-default">Price list</display-name>
			<online-flag>true</online-flag>
		</header>
		<price-tables>
'.$data.'
		</price-tables>
	</pricebook>
</pricebooks>
';
            echo $data;
        }
    }
    function handle_price_write(){
        ob_start();
        $this->handle_price_create();
        $data=ob_get_contents();
        ob_end_clean();
        file_put_contents(dirname(__FILE__).'/RUB-ECRU.xml',$data);

    }

    function handle_update_ids(){
        $db=$this->getDB();
        $db->set_option('debug');
        foreach($this->articul_voc as $item=>$articul){
            $defname=$db->selectCell('select defname from lap_stokke_items where articul=?',$articul);
            if(empty($defname)){
                $db->insert('insert lap_stokke_items (item,articul) values (?1,?2) on duplicate key update  articul=?2,item=?1',$articul,$item);
            } else {
                $db->update('update lap_stokke_items set item=? where defname=?',$item,$defname);
            }
        }
    }

    function getIds(){
        static $ids;
        if(!isset($ids)){
            $ids=$this->getDB()->select('select articul,item from lap_stokke_items where item>0');
        }
        return $ids;
    }
    function handle_product_create(){
        $ids=$this->getIds();
        $data='';
        $date = time() + (7 * 24 * 2);//('P2D'));
        $date=date('Y-m-d\TH:i:s.000P',$date);
        //echo $date;

        foreach($ids as $i){
            //if($i['articul']!='100103') continue;
            $item=Model_Item::get($i['item']);
            if($item->canbuy && $item->price) {
                /*              $riad = ENGINE::db()->selectRow("SELECT * FROM `ostatki` WHERE ID_ELEMENT=? limit 1", $this->item['id']);
                              $numb=0;
                              foreach(array('S_MEBEL_SEDOVA','S_MEBEL_SEDOVAVARNA','S_AUTO_SEDOVA','S_KOLYA_SEDOVA','S_KOLYA_INFANSI','S_OPT','S_MEBEL_ENGELSA','S_AUTO_ENGELSA','S_KOLYA_ENGELSA') as $x){
                                  if(!empty($riad[$xx]))
                                      $numb+=$riad[$xx];
                          }*/
                $data.=sprintf('
            <record product-id="%s">
                <allocation>%3$s</allocation>
                <allocation-timestamp>%s</allocation-timestamp>
                <perpetual>false</perpetual>
                <preorder-backorder-handling>none</preorder-backorder-handling>
                <preorder-backorder-allocation>0</preorder-backorder-allocation>
                <ats>%3$s</ats>
                <on-order>0</on-order>
                <turnover>0</turnover>
            </record>',$i['articul'],$date,20);
            }
        }
        if(!empty($data)){
            $data='<?xml version="1.0" encoding="UTF-8"?>
<inventory xmlns="http://www.demandware.com/xml/impex/inventory/2007-05-31">
    <inventory-list>
        <header list-id="LAPSI_SPB_RU">
            <default-instock>false</default-instock>
            <description>Lapsi, RU</description>
            <use-bundle-inventory-only>false</use-bundle-inventory-only>
        </header>

        <records>'.$data.'
        </records>
    </inventory-list>
</inventory>';
            echo $data;
        }
    }

    function handle_product_write(){
        ob_start();
        $this->handle_product_create();
        $data=ob_get_contents();
        ob_end_clean();
        file_put_contents(dirname(__FILE__).'/LAPSI_SPB_RU.xml',$data);
    }

    function _dir(){
        static $times;
        if(!isset($times)){
            $transport=$this->get_transport();
            $dirs=array('');
            $times=array();
            while(count($dirs)>0){
                $root=array_shift($dirs);
                $raw=$transport->scan($root);
                //if(!empty($root)) $root.='/';
                foreach($raw as $key=>$item){
                    if($item['type']==1){
                        //printf('file `%s`, time: %s<br>',$item['filename'],date('d-m-Y H:i:y',$item['mtime']));
                        $times[$root.$item['filename']]=$item['mtime'];
                    } else if ($item['type']==2) {
                        if($key!='.' && $key!='..'){
                           // echo "\n".$root.$key.'/';
                            $dirs[]=$root.$key.'/';
                        }
                    } else {
                        echo "\n".$item['type'].' '.$root.$key.'/';
                    }
                }

            }
        }
        return $times;
    }

    function handle_priceproduct_send(){
        $this->handle_price_write();
        $this->handle_product_write();
        $this->handle_stokke_scan();
    }

    function handle_orders_send(){
        $db=$this->getDB();
        $res=$db->select('SELECT * FROM stokke_orders where DATEDIFF(NOW(),`date`)<30');

        $times=$this->_dir();
        print_r($times);
        //$date=date('Y-m-d-H-i-s');
        $ids=$this->getIds();
        if(!empty($res))
        foreach($res as $o){
            if(empty($o['order'])){
                $order=false;
                $date=date('Y-m-d-H-i-s',strtotime($o['date']));
            } else {
                $order=Model_Order::get($o['order']);//ENGINE::debug($order);
                $date=date('Y-m-d-H-i-s',strtotime($order->date_update));
            }
            $name="orderupdate_ECRU_".$o['articul'].'_'.$date.'.xml';

            $dir=$this->stage."/inbound/orderupdate/".$name;
            if(!isset($times[$dir])){
                $tpl='orderupdate_tpl.php';
                ob_start();
                include $tpl;
                $content=ob_get_contents();
                ob_end_clean();
                echo 'uploading '.$name.' as '.$dir.'<br>';
                $this->upload_file($content,$dir);
            }
        }
    }

    function handle_orders_print(){
        $db=$this->getDB();
        $ids=$this->getIds();
        $tpl='order_tpl.php';
        $order=Model_Order::get(ENGINE::_($_GET['order'],166853));//ENGINE::debug($order);
        include $tpl;
    }

    function handle_ordersupdate_print(){
        $db=$this->getDB();
        $ids=$this->getIds();
        $tpl='orderupdate_tpl.php';
        $order=Model_Order::get(ENGINE::_($_GET['order'],166853));//ENGINE::debug($order);
        include $tpl;
    }

    function handle_stokke_scan(){
        // generate item files
        // $this->upload_file('test','items.xml');
        $transport=$this->get_transport();
        $raw=$transport->scan();
        $times=array();
        $date=date('Y-m-d-H-i-s');
        foreach($raw as $item){
            if($item['type']==1){
                printf('file `%s`, time: %s<br>',$item['filename'],date('d-m-Y H:i:y',$item['mtime']));
                $times[$item['filename']]=$item['mtime'];
            }
        }

        $files=array(
            "LAPSI_SPB_RU.xml"=>$this->stage."/inbound/inventory/LAPSI_SPB_RU.xml",
           // "RUB-ECRU.xml"=>$this->stage."/inbound/pricebook/RUB-ECRU-".$date.".xml"
        );
        foreach($files as $file=>$dir){
            if(!isset($times[$file]) || $times[$file]<filemtime(dirname(__FILE__).'/'.$file)){
                echo 'uploading '.$file.' as '.$dir.'<br>';
                $this->upload_file(dirname(__FILE__).'/'.$file,$dir,true);
            }
        }

    }
}