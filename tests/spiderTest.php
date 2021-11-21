<?php
// кадис-аудит.рф
// https://xn----7sbbocvdj0eoi.xn--p1ai/

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk;

//include "../vendor/autoload.php";

class spiderTest extends TestCase
{

    function getSpider()
    {
        $scaner = new Ksnk\scaner\spider();
       /* $scaner->_debug(function ($mess) {
            echo '>>>' . $mess . PHP_EOL;
        });/**/
        /*$scaner->_all(function ($mess) {
            echo '>>>' . $mess . PHP_EOL;
        });*/
        return $scaner;
    }

    // открывает кадис-аудит, проверяет 200, проверяем строку АУДИТОРСКИЕ УСЛУГИ
    public function testKadisAudit(){

        $spider=$this->getSpider();
        $result=$spider->open('https://кадис-аудит.рф')
            ->scan('~АУДИТОРСКИЕ УСЛУГИ\s*</h1~s')
            ->found;

        $this->assertEquals(true, $result);
/*
        $result=$spider->open('https://eco-bakaleya.ru')
            ->scan('~АУДИТОРСКИЕ УСЛУГИ\s*</h1~s')
            ->found;

        $this->assertEquals(true, $result);

        $result=$spider->open('https://new.snabworld.ru')
            ->scan('~АУДИТОРСКИЕ УСЛУГИ\s*</h1~s')
            ->found;

        $this->assertEquals(true, $result);
*/
    }

    function getCert($host){
        $host_utf = idn_to_utf8($host, 0 ,INTL_IDNA_VARIANT_UTS46);
        if(!empty($host_utf)) $host=$host_utf;

        $host_loc = idn_to_ascii($host, 0 ,INTL_IDNA_VARIANT_UTS46);
        if(!empty($host_loc)) $host=$host_loc;

        $url = 'ssl://'.$host.':443';

        $context = stream_context_create(
            array(
                'ssl' => array(
                    'capture_peer_cert' => true,
                    'verify_peer'       => false, // Т.к. промежуточный сертификат может отсутствовать,
                    'verify_peer_name'  => false  // отключение его проверки.
                )
            )
        );

        $fp = stream_socket_client($url, $err_no, $err_str, 30, STREAM_CLIENT_CONNECT, $context);
        $cert = stream_context_get_params($fp);

        if (empty($err_no)) {
            $info = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
            //print_r($info);
        }
        return $info;
    }

}
