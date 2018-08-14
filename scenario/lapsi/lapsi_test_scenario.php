<?php
/**
 * набор сценариев отладки из утилит лапси
 * Class convert_picture_scenario
 * @tags lapsi
 */
class lapsi_test_scenario extends scenario
{
    /**
     * Очистить паразитные доставки по МО из корзины товаров
     * @param int $limit ограничение на удаляемую область
     * @param int $repeat сколько раз повторять
     */
    function do_ClearBasketMODelivery($limit=1000,$repeat=10){

        $sql='select b.id FROM `b_sale_basket` as b left join b_sale_fuser as u on u.id=b.FUSER_ID where b.PRODUCT_ID=89760 and b.date_insert=0 and b.order_id is null and  u.ID is null';
        if($limit){
            $sql.=' limit ?d';
        }

        while($repeat-->0){
            $ids=ENGINE::db('nocache')->selectCol($sql,$limit);
            if(!empty($ids))
                ENGINE::db()->delete('delete from `b_sale_basket` where id in (?[?2])',$ids);
        }
    }

    /**
     * Информация о заказе
     * @param int|string $order номер заказа
     */
    function do_infoOrder($order){
        // 159158
        $items=ENGINE::db('debug once')->select('select * from b_sale_basket where order_id=?d',$order);
        $o=ENGINE::db('debug once')->selectRow('select * from b_sale_order where id=?d',$order);
        foreach($items as $i){
            printf('добавлен %s(%s) товар:`%s`
',$i['DATE_INSERT'],$i['DATE_UPDATE'],$i['NAME']);
        }
        printf('заказ:`%s`(%s) re: %s
%s
',$o['DATE_INSERT'],$o['STATUS_ID'],$o['re'],$o['COMMENTS']);
        var_dump($items);
        var_dump($o);
    }

    /**
     * Сравниваем стокке с нашими товарами
     */
    function do_sinchStokke(){
        $o=ENGINE::db('debug once')->select('select e.id, s.articul, s.name as stokename from lap_stokke_items as s join lapsi_elements as e on e.id=s.item');
        echo '<table>';
        foreach($o as $oo){
            $item=Model_Item::get($oo['id']);
            echo '<tr>';
            echo '<td>'.$oo['id'].'</td>';
            echo '<td>'.$oo['articul'].'</td>';
            echo '<td>'.$item->itemname.'</td>';
            echo '<td>'.$oo['stokename'].'</td>';
            echo '</tr>';
        }
        echo '</table>';
    }

    /**
     * Информация о наличии товара
     * @param int|string $code код товара
     * @param bool|string $info :checkbox[1:Выводить информацию о товарах?]
     */
    function do_infoItem($code,$info=false){
        foreach(explode(',',$code) as $c ){
            echo '<br>'.$c.' ';
            $o=ENGINE::db()->selectCol('select ID from '.Model_Item::$TABLE.' where name_id=? ',trim($c));
            if(!empty($o)){
                echo ' id\'s found by name_id: '.implode(',',$o),'; ';
            }
            $o=ENGINE::db()->selectCol('select item from lap_stokke_items where articul=? ',trim($c));
            if(!empty($o)){
                echo ' id\'s found by stokkeid: '.implode(',',$o),'; ';
            }
            $o=ENGINE::db()->selectCol('select ID_ELEMENT from ostatki where ID_1C=? ',trim($c));
            if(!empty($o)){
                echo ' id\'s found by 1C: '.implode(',',$o);
            }
            /*
            $o=ENGINE::db('debug once')->select('select item,comment from lap_edm_convert where artikul=?',trim($c));
            if(!empty($o)){
                $res=array();
                foreach($o as $oo){
                    if(!empty($oo['item'])) $res[]=$oo['item'];
                    elseif (!empty($oo['comment'])) $res[]=$oo['comment'];
                }
                echo ' id\'s found by EDMID: '.implode(',',$res);
            }*/

            $o=ENGINE::db()->select('select IBLOCK_ELEMENT_ID from b_iblock_element_property where value=? and IBLOCK_PROPERTY_ID=163',trim($c));
            if(!empty($o)){
                $res=array();
                foreach($o as $oo){
                    $res[]=$oo['IBLOCK_ELEMENT_ID'];
                }
                echo ' id\'s found by EDMID: '.implode(',',$res);
            }

            $o=ENGINE::db()->selectRow('select ID from '.Model_Item::$TABLE.' where id=? ',$c);
            if(!empty($o)){
                //print_r($o);
                // 159158
                $item=ENGINE::db()->select('select * from ostatki where ID_ELEMENT=? limit 3',$c);
                print_r($item);
                $edmids=ENGINE::db()->selectCol('select value from b_iblock_element_property where IBLOCK_ELEMENT_ID=? and IBLOCK_PROPERTY_ID=163',$c);
                if(!empty($edmids)){
                    print_r($edmids);
                    $o=ENGINE::db()->select('select * from lap_edm_items  where EDM_ID in (?[?2]) ',$edmids);
                    print_r($o);
                }

                if(!empty($info)){
                    $item=Model_Item::get($c);
                    print_r($item);
                }
            }
        }
    }


    /**
     * Показать забаненные IP с временем
     */
    function do_showbanned(){
        $banned = @unserialize(LAPSI::cache('banned_forum.php'));
        if(empty($banned)) {
            echo 'Нету забаненых IP';
        } else {
            foreach($banned as $k=>$v){
                echo $v.' ---- '.date('Y-m-d H:i:s',$k);
            }

        }
    }

    /**
     * Вывести все товары, под заказ, но без следов в остатках
     */
    function do_absentitemlist(){
        $list=ENGINE::db()->selectCol('select e.id from '.LAPSI::$TABLE.' as e left join ostatki as o on e.id=o.ID_ELEMENT where o.id is null order by e.IBLOCK_SECTION_ID');
        echo 'count: '.count($list).'.<br>';
        foreach($list as $i){
            $item= Model_Item::get($i);
            echo $item->name. ($item->color_name?' ('.strip_tags($item->color_name).')':'').'<br>';
        }
//        print_r($list);
    }

    /**
     * Почистить каталог с временными файлами
     */
    function do_clear(){
        $x=glob($_SERVER["DOCUMENT_ROOT"].'/upload/tmp/*.*');
        usort($x, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));
        while(count($x)>0 && (time()-filemtime($x[0])>86400)){
            unlink($x[0]);
            array_shift($x);
        }
    }
    /*
     * таблица сама чистится на записи старше года.
     * К сожалению, сопутствующие таблицы остаются грязными.
     * Можно добавить чистку еще и по времени создания записи b_search_content->date
     */
    /**
     * Почистить таблицу поиска
     * @param string $before срок
     */
    function do_clearSearchTable($before='-1 year'){

        $before=strtotime($before);
        ENGINE::db()->delete('delete from b_search_content where `DATE_CHANGE`<?',date('Y-m-d H:i:s',$before));
        ENGINE::db()->delete('delete from b_search_content_group WHERE not exists (select * from b_search_content where id=`SEARCH_CONTENT_ID`)');
        ENGINE::db()->delete('delete from b_search_content_site WHERE not exists (select * from b_search_content where id=`SEARCH_CONTENT_ID`)');
        ENGINE::db()->delete('delete from b_search_content_stem WHERE not exists (select * from b_search_content where id=`SEARCH_CONTENT_ID`)');
    }
    /**
     * Некорректно вставляется дата + неправильный пользователь.
     */
    /**
     * Почистить корзину от грязных записей.
     */
    function do_clearBasket(){
        ENGINE::db()->delete('delete FROM `b_sale_basket` WHERE `DATE_INSERT`=0 and `ORDER_ID` is null  and not exists (select * from b_sale_fuser where id=`FUSER_ID`)');
    }

    /**
     * Тестировать
     * @param string $a :radio[1:one|3:two|4:four|5:five] 1-й параметр
     * @param $b
     * @param int|string $c :select[one|3:two|4:four|five] 3-й параметр
     * @param int $d :checkbox[1:да] Полностью?
     */
    function do_test($a,$b,$c=4,$d=0)
    {
        $port = ($_SERVER["SERVER_PORT"] != '80' ? ':' . $_SERVER["SERVER_PORT"] : '');
        echo 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        printf('  $a=`%s`,$b=`%s`,$c=`%s`, $d=`%s` port=`%s`',$a,$b,$c,implode(',',$d),$port);
    }

    /**
     * Выдать PHPINFO
     */
    function do_phpinfo()
    {
        phpinfo();
    }


    /**
     * Вывести бестселлеры за
     * @param int $period :select[1:месяц|2:2 месяца|6:полгода|12:год] за период
     */
    function do_BestSeller2($period){
        foreach(ENGINE::db('debug once')->selectlong(
                    "SELECT count(i.PRODUCT_ID) as cnt,i.PRODUCT_ID,sum(i.QUANTITY) as qt, max(t.name_id) as name_id, max(i.name) as name
                    FROM `b_sale_basket` as i join b_sale_order as o on o.id=i.ORDER_ID left join b_iblock_element as t on i.PRODUCT_ID=t.id
                    WHERE not i.PRODUCT_ID in (93721,89760,94409,101828,0,1)
                      and (o.STATUS_ID='O' OR o.STATUS_ID='R' OR o.STATUS_ID='C')
                      and  DATEDIFF(now(),o.DATE_INSERT)<?d
                    group by t.NAME having cnt>4
ORDER BY `cnt` DESC",$period*30) as $row)
        {
            if($row['name_id']>0)
                $item=Model_Item::get(-$row['name_id']);
            else
                $item=Model_Item::get($row['PRODUCT_ID']);
            if(!!$item->name)
                printf('%s(%s) <a href="%s" target="_blank">%s</a><br>',
                    $row['cnt'],$row['qt'],$item->lapsi_elementurl,$item->name);
            else
                printf('%s(%s) %s (%s)<br>',
                    $row['cnt'],$row['qt'],$row['name'],$row['PRODUCT_ID']);
        };
    }

    /**
     * Заполнить таблицу lap_bestsellers
     */
    function do_BestSeller3(){
        foreach(array(1,2,6,12) as $period) {
            $field='sales'.(0+$period);
            $xtpl=array('ID'=>'','update'=>date('Y-m-d H:i:s'));
            $xtpl[$field]='';
            $insert=ENGINE::db()->insertValues(
                'insert into lap_bestsellers (?1[?1k]) values ()
                on duplicate key update ?1[?1k=VALUES(?1k)];',$xtpl);

            foreach (ENGINE::db('debug ')->selectlong(
                         "SELECT t.name_id,sum(i.QUANTITY) as qt
                         FROM `b_sale_basket` as i join b_sale_order as o on o.id=i.ORDER_ID
                         join " . LAPSI::$TABLE . " as t on t.id=i.PRODUCT_ID
                         WHERE not i.PRODUCT_ID in (93721,89760,94409,101828,0,1)
                           and (o.STATUS_ID='O' OR o.STATUS_ID='R' OR o.STATUS_ID='C')
                           and  DATEDIFF(now(),o.DATE_INSERT)<?d
                         group by t.name_id having qt>2
                         ORDER BY `qt` DESC",$period*30) as $row)
            {
                $xtpl['ID']=$row['name_id'];
                $xtpl[$field]=$row['qt'];
                $insert->insert($xtpl);
            }
            $insert->flush();
            ENGINE::db()->update('update lap_bestsellers set ?k=0 where update<?',$field,$xtpl['update']);
        }
        ENGINE::db()->delete('delete from lap_bestsellers where sales1=0 and sales2=0 and sales6=0 and sales12=0');
    }

    /**
     * список бестселлеров по категориям с использованием таблицы
     * @param int $category категория
     * @param int $period :select[1:месяц|2:2 месяца|6:полгода|12:год] за период
     */
    function do_BestSeller4($category,$period){
        // список всех категорий
        $field='sales'.$period;
        if(ctype_digit(''.$category)) $category.='*';
        $cat=Model_Category::getSubtree($category);
        $names=ENGINE::db('debug once')->selectCol('select distinct(i.name_id) from b_iblock_section_element as s
join ' . LAPSI::$TABLE . ' as i on i.id=s.IBLOCK_ELEMENT_ID
          where s.IBLOCK_SECTION_ID in (?[?2])
        ',$cat);

        foreach(ENGINE::db('debug once')->select(
                    'select id,?1k as sale from lap_bestsellers
                    where id in (?2[?2]) order by ?1k DESC LIMIT 10
                ',$field,$names) as $row){
            $item=Model_Item::get(-$row['id']);
            printf('%s <a href="%s" target="_blank">%s</a> <br>',
                $row['sale'],$item->lapsi_url,$item->name);
        };
    }

    /**
     * Список картинок Кэша
     * @param int $text_start :text начальный номер. Выводим по 1000 фото
     * @param int|string $after :text время, после которого нужно проверять.
     * @param int|string $before :text время, после которого уже не нужно.
     * @param string $mask :text маска файлов
     * @param bool|int $del :checkbox[1:удалить]
     */
    function do_showmecache($text_start=0,$after='-3 month',$before='-2 month',$mask='*',$del=false)
    {
        $dir=realpath(dirname(dirname(__FILE__)).'/../../../lapsi.msk.ru/cashe-images');

        $d = dir($dir);
        echo '</pre>
<style>
        .cache img{
           max-width:150px; max-height:150px;
        }
        .cache .box {
            display:inline-block; width:150px;height:150px; float:left;
        }
</style>
';
        echo "Путь: " . $d->path . '<br><div class="cache">';
        $cnt=1000;
        $total=0; $scipped=0;
        $after=empty($after)?time():strtotime($after);
        $before=empty($after)?0:strtotime($before);
        $mask=UTILS::masktoreg($mask);
        echo $mask." ".$after." ".$before." ";
        while ( false !== ($entry = $d->read())) {
           // if(!preg_match('/^\d.*\.(jpe?g|gif|png)$/i',$entry)) continue;
            if($mask && !preg_match($mask,$entry)) continue;
            $t=filemtime($d->path.'/'.$entry);
            if($t>$after) continue;
            if($t<$before) continue;
            $total++;
            if($text_start-- >0) continue;
            $scipped++;
            if($cnt--<0) break;
            echo '<div class="box"><img title="'.$entry.' '.date('d-m-Y H:i',$t).'" src="http://lapsi.ru/cashe-images/'.$entry.'"></div> ';
        }
        echo "</div>";
        echo $total. "(".$scipped.") skipped images";
        $d->close();
//        phpinfo();
    }

    /**
     * послать письмо
     *
     * @param string $to : text адрес
     * @param string $subject:text  Заголовок
     * @param string $msg:textarea  текст сообщения
     * @param string $template:select[mail|phpmail|html_mime_mail] как послать
     */
    function do_mail($to, $subject='тестовое сообщение', $msg='',$template='xxx')
    {
        if (empty($to) || empty($msg)) {
            echo 'не все поля заполнены';
            return;
        }
        if (preg_match('/<html/', $msg)) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=windows-1251\r\n";

        } else {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/plain; charset=windows-1251\r\n";

        }
        switch($template){
            case 'html_mime_mail':
                require_once $_SERVER['DOCUMENT_ROOT'].'/az/helpers/_mail.php';

                html_mime_mail::send(array(
                        'to'=>array(
                            $to,
                        ),
                       // 'from'=>$arFields['SALE_EMAIL'],
                        'subj'=>$subject,
                        'html'=>$msg,//.'<!--xx--:'.htmlspecialchars(var_export($arResult).var_export($arOrder)).'-->',
                       // 'pictures'=>'logo|'.$_SERVER['DOCUMENT_ROOT'].'/images/mail.gif'
                    )
                );
                break;
            case 'phpmail':
                require '../helpers/phpmailer/PHPMailerAutoload.php';

//Authenticate via POP3.
//After this you should be allowed to submit messages over SMTP for a while.
//Only applies if your host supports POP-before-SMTP.
                $pop = POP3::popBeforeSmtp('mail.lapsi.ru', 110, 30, 'bg@lapsi.ru', '201220', 1);

//Create a new PHPMailer instance
//Passing true to the constructor enables the use of exceptions for error handling
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    //Enable SMTP debugging
                    // 0 = off (for production use)
                    // 1 = client messages
                    // 2 = client and server messages
                    $mail->SMTPDebug = 2;
                    //Ask for HTML-friendly debug output
                    $mail->Debugoutput = 'html';
                    //Set the hostname of the mail server
                    $mail->Host = "mail.lapsi.ru";
                    $mail->CharSet='cp1251';
                    //Set the SMTP port number - likely to be 25, 465 or 587
                    $mail->Port = 25;
                    //Whether to use SMTP authentication
                    $mail->SMTPAuth = false;
                    //Set who the message is to be sent from
                    $mail->setFrom('SKoriakin@lapsi.ru', 'POP3 почта');
                    //Set an alternative reply-to address
                    //$mail->addReplyTo('replyto@example.com', 'First Last');
                    //Set who the message is to be sent to
                    $mail->addAddress($to);
                    //Set the subject line
                    $mail->Subject = $subject;
                    //Read an HTML message body from an external file, convert referenced images to embedded,
                    //and convert the HTML into a basic plain-text alternative body
                    $mail->msgHTML($msg);
                    //Replace the plain text body with one created manually
                    //$mail->AltBody = 'Just to say hello!';
                    //Attach an image file
                    // $mail->addAttachment('images/phpmailer_mini.png');
                    //send the message
                    //Note that we don't need check the response from this because it will throw an exception if it has trouble
                    $mail->send();
                    echo "Message sent!";
                } catch (phpmailerException $e) {
                    echo $e->errorMessage(); //Pretty error messages from PHPMailer
                } catch (Exception $e) {
                    echo $e->getMessage(); //Boring error messages from anything else!
                }
                break;
            default: //case 'mail':
                mail($to, $subject, $msg, $headers);
        }
        mail($to, $subject, $msg, $headers);
        echo 'сообщение послано!';
    }

    /**
     * Установить флаг 5x2
     *
     */
    function do_update5x2()
    {
        $items=array(//-17515,
            //FD-Design
            -18486,-18487,-2366,-19183,-19186,19187,-19185,-18226,-18227,-18228,-19187,
            -18231,-17940,-16813,-16818,-17945,-16816,-17929,-17944,-2713,-19109,
            //Funnababy
            -18296,-18297,-18298,
            //Teutonia
            /* -17515,-17517,-17573,-17531,-17562,-17570,-19206,-18041,-17902,-19207,*/-95915,-95912,-17578,
            //KHW
            -6106,/*-6107,*/-6118,-6117,-6380,-297,-4376,-1704,-6380,-6151,
            //CHOUPETTE
            -18490,-18676,-18680,
            //BUGABOO
            -2330,-17319,-19167,-6315,-6314,-6317,-6558,-9346,-4546,-19174,-2174,
            -2023,-19169,-19168,-2175,-8146,-2180,-6318,-18995,-18992,-18996,-18994,-17331,
            //Maclaren
            -17863,-2276,-2991,-2990,-2992,-2983,-7543,-17278,
            //Aprica
            -8180,-17949,-17950,-17951,-17952,-9344,-2985,-7778,
            //Graco
            -19208

        ,-7498,-17588,-4071,-4062,-8271,-4066,-4067,-7497,-4054,-4057
        ,-19162,-19161,-2210,-2209,-2208,-2213
        ,-5022,-3099,-3098,-3100
        ,-7953,-7938,-7940,-7941
        ,-18850,-18851,-9040,-9055,-17590
        );

        foreach($items as $item){
            if($item<0)
                $ids=ENGINE::db()->selectCol('select id from b_iblock_element where name_id=?',-$item);
            else
                $ids=array($item);
            if(count($ids)>0)
                foreach($ids as $id){
                    $par=array(
                        'IBLOCK_PROPERTY_ID'=>75,
                        'IBLOCK_ELEMENT_ID'=>$id,
                        'VALUE'=>12,
                        'VALUE_TYPE'=>'text',
                        'VALUE_ENUM'=>12,
                        // 'VALUE_NUM'=>$topic['value'],
                    'UPDATED'=>date("Y-m-d H:i:s"),
                    );
                    ENGINE::db()->insert('insert into b_iblock_element_property  set ?[?k=?]',$par);
                }
        }

    }

    /**
     * обновить все "Все товары"
     */
    function do_menuUpdate()
    {
        $path = $_SERVER["DOCUMENT_ROOT"] . "/az/partials/all_brands/";
        foreach (array(
                     172 => 'all_carriage.php',
                     164 => 'all_furniture.php',
                     178 => 'all_mattress.php',
                     174 => 'all_seat.php',
                     173 => 'all_clothing.php',
                     163 => "all_high_chairs.php",
                     179 => "all_hygiene.php",
                     176 => "all_safety.php",
                     205 => "all_biketoys.php"
                 ) as $k => $v) {
            //ENGINE::db('debug');
            $sections = LAPSI::section_tree(array($k));
            //LAPSI::debug($sections);
            $brands = ENGINE::db()->selectCol(
                'select distinct BRAND from ' . LAPSI::$TABLE . ' where IBLOCK_SECTION_ID in (?[?2d])'
                , $sections
            );
            //LAPSI::debug($brands);
            if (empty($brands)) {
                LAPSI::error('section {sec} have no items', array('{sec}' => $k));
                continue;
            }
            $brands_ids = ENGINE::db()->select(
                'select id,name from b_iblock_element where '
                .'id in (?[?2d]) order by name'
                , $brands
            );
            // LAPSI::debug($brands_ids);
            $out = '<ul class="brand_list">
    ';
            foreach ($brands_ids as $brand) {
                $out .= sprintf('    <li class="brand_item"><a class="filter" href="/list.php?filterSECTION=%s&amp;filter71=%s">%s</a></li>
    ', $k, $brand['id'], $brand['name']);
            }
            $out .= '
</ul>';
            file_put_contents($path . $v, $out);
        }
    }


    /**
     * Поиск строки сфинксом в нужном индексе
     * @param string $search :textarea строка для поиска
     * @param string $index :select[titles|faces|standard] ключи поиска
     */
    function do_Search($search,$index='titles'){
        require_once "sphinxapi.php";

        $cl= new SphinxClient();
        $host='localhost';
        $port='9312';
        $cl->SetServer($host,$port);
        $q=trim($search);
        echo'<pre>';
        $cl->SetLimits(0, 1000);
        $cl->SetMatchMode(SPH_MATCH_ALL);
        var_export($cl->Query(html_entity_decode(iconv('cp1251', 'utf-8', $q), ENT_NOQUOTES, 'UTF-8'), $index));

        var_export($cl->Query('*' . preg_replace('/\s+/', '* *', html_entity_decode(iconv('cp1251', 'utf-8', strtr($q, '-(), .', '     ')) . '*', ENT_NOQUOTES, 'UTF-8')), $index));
        $cl->SetLimits(0, 15);
        $cl->SetMatchMode(SPH_MATCH_ANY);
        $q_matches = $cl->Query(html_entity_decode(iconv('cp1251', 'utf-8', $q), ENT_NOQUOTES, 'UTF-8'), $index);
        var_export($q_matches);

        echo'</pre>';
        /*
                foreach(explode("\n",$x) as $line){
                    $key=explode("\t",trim($line)."\t\t\t\t\t",5);
                    for($xxx=1;$xxx<2;$xxx++){
                        if(''==trim($key[$xxx])) continue;
                        $words=explode(',',$key[$xxx]);
                        if(count($words)==1)
                            ;// echo '# '.$key[$xx].'<br>';
                        else {
                            for($xx=0;$xx<count($words);$xx++)
                                echo $words[$xx].' => '.trim($key[0]).'<br>';
                        }
                    }
                };
        */    }

    /**
     * Выполнить курлом запрос с сервера
     * Выполняется запрос.
     * @param string $url :textarea строка для поиска
     */
    function do_Curl($url){

        $rsp=file_get_contents($url);
        if(!empty($rsp)){
            echo htmlspecialchars($rsp);
        } else {
            echo 'no any bank records found!';
        }



    }


}