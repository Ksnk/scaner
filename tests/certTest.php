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
        try {
            $fp = stream_socket_client($url, $err_no, $err_str, 2, STREAM_CLIENT_CONNECT, $context);
            if (!!$fp) {
                $cert = stream_context_get_params($fp);

                if (empty($err_no)) {
                    $info = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
                    //print_r($info);
                    return $info;
                }
            }
            return false;
        } catch(\Exception $e){
            return false;
        }
    }

    function test_cert()
    {
        $results=[];
        $fault=0;
        foreach([
            'new.snabworld.ru',
            'eco-bakaleya.ru',
            'kadis.org','consultant.kadis.org','info.kadis.org',
            'outportal.kadis.org',
            'audit.kadis.org',
            'arbspor.ru',
            'кадис-аудит.рф','express.kadis.org','meeting.kadis.org','events.kadis.org',
            'land.kadis.org',
        ] as $host) {
            $str = '----------------------------------' . "\r\n" ;
            $info = $this->getCert($host);
            if(!$info){
                $fault++;
                $str .= 'Домен: ' . $host . "\r\n" ;
                $str .= 'не доступен ' . "\r\n";
                $results[$str]=0;
            } else {
                if($host!=$info['subject']['CN']) {
                    $str .= 'Домен: ' .$host.'('. $info['subject']['CN'] . ")\r\n";
                } else {
                    $str .= 'Домен: ' . $info['subject']['CN'] . "\r\n";
                }
                $str .=  'Выдан: ' . $info['issuer']['CN'] . "\r\n";
                if (time() > $info['validTo_time_t']) {
                    $fault++;
                    $str .= 'Просрочен: ' . date('d.m.Y H:i', $info['validTo_time_t']);
                } else {
                    $t = new \DateTime();
                    $t->setTimestamp($info['validTo_time_t']);
                    $interval = date_create('now')->diff($t);
                    $str .= $interval->format('Истекает через %R%a дней  %H') . ' - ' . date('d.m.Y H:i', $info['validTo_time_t']);
                }
                $str.="\r\n";
                $results[$str]=$info['validTo_time_t'];
            }
        }
        asort($results);
        echo implode('',array_keys($results));
        $this->assertEquals(0, $fault);
    }

}
