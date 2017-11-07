<?php
/** @var Model_Order $order */
if(empty($order)){
    echo '<?xml version="1.0" encoding="UTF-8" ?>
<orderupdate xmlns="">
    <order-date>'.gmdate('Y-m-d\TH:i:s.000\Z',strtotime($o['date'])).'</order-date>
    <internalOrderNumber>'.$o['articul'].'</internalOrderNumber>
    <currency>RUB</currency>
    <order-no>'.$o['articul'].'</order-no>
    <status>
        <order-status>CANCELLED</order-status>
    </status>
</orderupdate>';

} else {
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
      <orderupdate xmlns="">
          <order-date><?=gmdate('Y-m-d\TH:i:s.000\Z',strtotime($order->date_insert))?></order-date>
          <internalOrderNumber><?=$o['articul']?></internalOrderNumber>
          <currency>RUB</currency>
          <order-no><?=$o['articul']?></order-no>
          <customer>
              <customer-no><?=$o['user']?></customer-no>
              <customer-name><?=$order->prop_7?></customer-name>
              <customer-email><?=$order->prop_6?></customer-email>
              <customer-phone><?=$order->prop_4?></customer-phone>
          </customer>
          <status>
              <order-status><?=$status[0]?></order-status>
          </status>
          <product-lineitems>
<?php
$cnt=0;
      foreach($order->get_items as $item){
          if(isset($ids[$item->id]))
              $articul=$ids[$item->id]['articul'];
          else {
              $this->log('error: неизвестный артикул '.$item->id);
              continue;
          }
          $cnt++;
              //  ENGINE::debug($item->add);
?>
              <product-lineitem>
                  <position><?=$cnt?></position>
                  <product-id><?=$articul?></product-id>
                  <product-name><?=$item->itemname?></product-name>
                  <quantity><?=$item->quantity?></quantity>
              </product-lineitem>
<?php } ?>
          </product-lineitems>
      </orderupdate>
<?php }