<?php
/** @var Model_Order $order */
$address = $order->prop_5;
$city='';
if(preg_match('~^(г\.[^,]+),\s+(.*)$~iu',$address,$m)){
    $city=$m[1]; $address=$m[2];
};
$status=array('NEW','NOT_SHIPPED','CONFIRMED','NOT_PAID');
//ENGINE::debug($order->status);
switch($order->status_id ){
    case 'N': // новый,только что создан
        $status=array('CREATED','NOT_SHIPPED','CONFIRMED','NOT_PAID');
        break;
    case 'W': // ожидает завершения расчета
        $status=array('NEW','NOT_SHIPPED','CONFIRMED','NOT_PAID');
        break;
    case 'C': // оплачен, отправлен на доставку
        $status=array('OPEN','NOT_SHIPPED','CONFIRMED','PAID');
        break;
    case 'R': // передан на доставку
        $status=array('OPEN','NOT_SHIPPED','CONFIRMED','PAID');
        break;
    case 'O': // не выполнен
        $status=array('COMPLETED','SHIPPED','CONFIRMED','PAID');
        break;
    default: // тестовый
        $status=array('CANCELLED','NOT_SHIPPED','CONFIRMED','NOT_PAID');
}

echo '<?xml version="1.0" encoding="UTF-8" ?>';
?>
 <orders xmlns="http://www.demandware.com/xml/impex/order/2006-10-31">
 <order order-no="<?=$order->id?>">
    <order-date><?=gmdate('Y-m-d\TH:i:s.000\Z',strtotime($order->date_insert))?></order-date>
    <created-by>lapsi</created-by>
    <original-order-no><?=$order->id?></original-order-no>
    <currency>RUB</currency>
    <customer-locale>ru_RU</customer-locale>
    <taxation>net</taxation>
    <!--<invoice-no>00042506</invoice-no>-->
     <customer>
        <customer-no><?=$order->user_id?></customer-no>
        <customer-name><?=$order->prop_7?></customer-name>
        <customer-email><?=$order->prop_6?></customer-email>
         <billing-address>
            <first-name><?=$order->prop_7?></first-name>
            <last-name></last-name>
            <address1><?=$address?></address1>
            <city><?=$city?></city>
           <!-- <postal-code>80-759</postal-code>-->
            <country-code>RU</country-code>
            <phone><?=$order->prop_4?></phone>
        </billing-address>
    </customer>
     <status>
        <order-status><?=$status[0]?></order-status>
        <shipping-status><?=$status[1]?></shipping-status>
        <confirmation-status><?=$status[2]?></confirmation-status>
        <payment-status><?=$status[3]?></payment-status>
    </status>
    <current-order-no><?=$order->id?></current-order-no>
    <product-lineitems>
<?php
     foreach($order->get_items as $item){
         if(isset($ids[$item->id]))
            $articul=$ids[$item->id]['articul'];
         else {
             $this->log('error: неизвестный арттикул '.$item->id);
             continue;
         }
       //  ENGINE::debug($item->add);
?>
         <product-lineitem>
            <net-price><?=$item->add['price']?></net-price>
            <tax>0</tax>
            <gross-price><?=$item->add['price']?></gross-price>
            <base-price><?=$item->add['price']?></base-price>
            <lineitem-text><?=$item->add['name']?></lineitem-text>
            <tax-basis><?=$item->add['price']?></tax-basis>
            <position>1</position>
            <product-id><?=$articul?></product-id>
            <product-name><?=$item->itemname?></product-name>
            <quantity unit=""><?=$item->quantity?> </quantity>
            <tax-rate>0</tax-rate>
           <!-- <shipment-id>00043513</shipment-id>-->
            <gift>false</gift>
        </product-lineitem>
<?php } ?>
    </product-lineitems>
     <totals>
         <merchandize-total>
            <net-price><?=$order->price?></net-price>
            <tax>0</tax>
            <gross-price><?=$order->price?></gross-price>
        </merchandize-total>
         <adjusted-merchandize-total>
            <net-price><?=$order->price?></net-price>
            <tax>0</tax>
            <gross-price><?=$order->price?></gross-price>
        </adjusted-merchandize-total>
          <shipping-total>
            <net-price>0</net-price>
            <tax>0</tax>
            <gross-price>0</gross-price>
        </shipping-total>
          <adjusted-shipping-total>
            <net-price>0.00</net-price>
            <tax>0.00</tax>
            <gross-price>0.00</gross-price>
        </adjusted-shipping-total>
          <order-total>
            <net-price><?=$order->price?></net-price>
            <tax>0</tax>
            <gross-price><?=$order->price?></gross-price>
        </order-total>
    </totals>
</order>
</orders>