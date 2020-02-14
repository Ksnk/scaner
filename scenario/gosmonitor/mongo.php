<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 12.02.2020
 * Time: 11:24
 */

/**
 * аналог класса для друпала
 * Class mongo
 */
class emongo {

  /** @var MongoClient  */
  var $mongo=null,
    $uri = 'mongodb://gosmonitor:shooTah6@localhost:27017/',
    $options=array(
      'connectTimeoutMS' => 10000,
      'db' => 'techrategosmonitorru',
    );

  function __construct(){
    $this->_init();
  }

  function _init(){
    if(empty($this->mongo)) {
      $this->mongo= new \MongoClient($this->uri, $this->options);
    }
    return $this->mongo;
  }

  /**
   * @param $collection
   * @return MongoCollection
   * @throws Exception
   */
  function selectCollection($collection){
    return $this->mongo->selectCollection($this->options['db'],$collection);
  }

  function update()
  {
    //$collectionHost = $db->Host;
    /*
        $mustUpdateId = false;
        $host = $collectionHost->findOne(['gosmonitor_id' => intval($siteId)]);
        if (!isset($host['_id'])) {
          watchdog('widget', 'site not found by id, trying to find by URL: ' . $siteUrl);
          $host = $collectionHost->findOne(['site' => $siteUrl]);
          $mustUpdateId = true;
        }
        if (isset($host['_id'])) {
          $hostId = new MongoId($host['_id']->{'$id'});
          if ($mustUpdateId) {
            $collectionHost->update(['_id' => $hostId], ['$set' => [
              'gosmonitor_id' => intval($siteId),
              'changed' => time(),
              'latest_record_update' => new MongoDate(time())
            ]], ['fsync' => true]);
          }
        }
      }
    */
  }
}