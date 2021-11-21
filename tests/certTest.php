<?php
// кадис-аудит.рф
// https://xn----7sbbocvdj0eoi.xn--p1ai/

namespace Ksnk\Tests;

use PHPUnit\Framework\TestCase;
use Ksnk;

//include "../vendor/autoload.php";

class certTest extends TestCase
{

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

    function test_cert()
    {
        foreach([
            'new.snabworld.ru','eco-bakaleya.ru',
            'kadis.org','consultant.kadis.org','info.kadis.org',
            'outportal.kadis.org',
            'audit.kadis.org',
            'кадис-аудит.рф','express.kadis.org',
            'buhgalteru.kadis.org',
        ] as $host) {
            $info = $this->getCert($host);
            echo '----------------------------------' . "\r\n";
            echo 'Домен: ' . $info['subject']['CN'] . "\r\n";
            echo 'Выдан: ' . $info['issuer']['CN'] . "\r\n";
            if(time()>$info['validTo_time_t']){
                echo 'Просрочен: ' . date('d.m.Y H:i', $info['validTo_time_t']);
            } else {
                $t = new \DateTime();
                $t->setTimestamp($info['validTo_time_t']);
                $interval = date_create('now')->diff( $t );
                echo $interval->format('Истекает через %R%a дней  %H').' - ' . date('d.m.Y H:i', $info['validTo_time_t']);
            }
            echo "\r\n";
        }

    }

}
