<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 18:52
 */

namespace Ksnk\scaner;

use mysql_xdevapi\Exception;

/**
 * Паучёк
 * file_get_contents ===
 *   spider::getcontents($url)
 * Class spider
 */
class spider extends scaner
{

    var $curl,
//        $debug = 0,
        $siteroot,
        $cookie_file = 'tmp_cookie.txt',
        $pclzip_path,
        $info=[];

    var $lasturl = array(
        'scheme' => 'http',
        'host' => '',
        'user' => '',
        'pass' => '',
        'path' => '',
        'query' => '',
        'fragment' => '',
    );

    var $stat = [
        'redirects' => [],
    ];

    private function curl($url, $opt = array())
    {
        try {

            $ch = curl_init();
            $opt = $opt + [
                    'level' => 1,
                    'data'=>[],
                    'method'=>'GET',
                    'file'=>false
                ] ;
            $this->siteroot = $this->buildurl($url, true);
            $redirect = '';
            if ($this->debug()) {
                $verbose = fopen('php://temp', 'w+');
                curl_setopt($ch, CURLOPT_VERBOSE, true);
                curl_setopt($ch, CURLOPT_STDERR, $verbose);
            }
            if (empty($opt['level'])) {
                // Минимальная маскировка, не меняем useragent
                $user_agent = 'Kadis Bot/1.0';
            } else if ($opt['level'] > 0) {
                $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36';
            }
            $header = array(
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8",
                "Accept-Encoding: gzip, deflate",
                "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7",
                "Upgrade-Insecure-Requests: 1",
                //           "Keep-Alive: 300"
            );
            if(!empty($opt['headers'])) {
                $header = array_merge($header, $opt['headers']);
            }

            curl_setopt($ch, CURLOPT_URL, $this->siteroot); // set url to post to
            $method=$opt['method']?:'';
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($opt['data']?:[]));
            } else if ($method == 'HEAD') {
                curl_setopt($ch, CURLOPT_NOBODY, true);
            }
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
            //curl_setopt($ch, CURLOPT_HTTP_VERSION,'1.0');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); // times out after 4s
           // curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            // curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $fp=false;
            if($opt['file']) {
                $fp = fopen ($opt['file'], 'w+');
                curl_setopt($ch, CURLOPT_FILE, $fp);
            }

            $x = curl_exec($ch);
            if ($this->debug()) {
                rewind($verbose);
                $this->debug(stream_get_contents($verbose));
            }
            $info = curl_getinfo($ch);
            $this->info=$info;
            $this->debug("%s\n", $info);
            if (false === $x) {
                $this->error("Ошибка curl: %s\n", curl_error($ch));
                $this->newbuf(''); // run the whole process
            } else {
                $content_type = $info['content_type'];
                if (preg_match('/charset=\s*(.+)\s*/', $content_type, $found)) {
                    $p = strtoupper(trim($found[1]));
                    if ($p != 'UTF-8') $x = mb_convert_encoding($x, 'UTF-8', $p);
                };
                $this->newbuf($x); // run the whole process
                $x = $this->buildurl($info['url'], true);
                /*  if ($x != $this->siteroot) {
                    $this->out("`%s` => `%s`\n",$this->siteroot,$x);
                  } else {
                    $this->out("%s\n", $this->siteroot);
                  } */
                if ($info['http_code'] == 302) {
                    $this->stat['redirects'][$url] = $info['url'];
                    $redirect = $info['redirect_url'];
                }
            }

            if ($this->debug()) {
                $version = curl_version();
                $this->debug('
    date: %s
    %s
    URL....: %s
    Code...: %s (%s redirect(s) in %s secs)
    Content: %s Size: %s (Own: %s) Filetime: %s
    Time...: %s Start @ %s (DNS: %s Connect: %s Request: %s)
    Speed..: Down: %s (avg.) Up: %s (avg.)
    Curl...: v{%s}
    ',
                    date('Y-m-d H:i:s T'), $x,
                    $info['url'], $info['http_code'], $info['redirect_count'],
                    $info['redirect_time'],
                    $info['content_type'], $info['download_content_length'],
                    $info['size_download'], $info['filetime'],
                    $info['total_time'], $info['starttransfer_time'],
                    $info['namelookup_time'], $info['connect_time'],
                    $info['pretransfer_time'], $info['speed_download'],
                    $info['speed_upload'], $version['version']
                );
            }
            curl_close($ch);
            if($opt['file']) {
                fclose($fp);
            }
            if (isset($opt['callback']) && is_callable($opt['callback'])) {
                $callback = $opt['callback'];
                $callback($info, $x);
            }
            if (!empty($redirect)) {
                $this->curl($redirect);
            }
        } catch (\Exception $e) {
            $this->error("curl: ".$url."\n".'Exception: '.$e->getMessage());
            $this->newbuf('');
            if(empty($this->info['status']))
                $this->info['status']='404';
        }
        return $this;
    }

    /**
     * Build url from last opened url's
     * @param $url
     * @param bool $update
     * @return string
     */
    function buildurl($url, $update = false)
    {
        $parsed = parse_url(trim($url));
        // собираем url обратно
        $urls = '';
        if (!isset($parsed['scheme'])) {
            $parsed['scheme'] = $this->lasturl['scheme'];
            $parsed['host'] = $this->lasturl['host'];
        }
        // idna

        if (!isset($parsed['host'])) {
            return $url;
        }
        $host_utf = idn_to_utf8($parsed['host'], 0 ,INTL_IDNA_VARIANT_UTS46);
        $host_loc = idn_to_ascii($parsed['host'], 0 ,INTL_IDNA_VARIANT_UTS46);
        if (!empty($host_utf) && $host_utf != $parsed['host']) {
            $parsed['host'] = $host_utf;
        } elseif (!empty($host_loc) && $host_loc != $parsed['host']) {
            $parsed['host'] = $host_loc;
        }
        if (!empty($parsed['path']) && $parsed['path']{0} != '/') {
            $parsed['path'] = preg_replace('~[^/]*$~', '', $this->lasturl['path']) . $parsed['path'];
        }
        foreach (['scheme' => '%s:', 'host' => '//%s', 'path' => '%s', 'query' => '?%s'] as $k => $v) {
            if (!empty($parsed[$k])) $urls .= sprintf($v, $parsed[$k]);
        }

        if ($update) {
            $this->lasturl = $parsed;
        }
        return $urls;
    }

    /**
     * Имя сайта
     */
    function open($url, $opt = array())
    {
        $this->curl($url, $opt);
        return $this;
    }

    function uploadfile($url, $img, $_archive)
    {
        $this->curl($url);
        require_once $this->pclzip_path . 'pclzip.lib.php';
        $archive = new PclZip($_archive);
        if (is_readable($_archive)) {
            $list = $archive->add(array(
                    array(PCLZIP_ATT_FILE_NAME => $img,
                        PCLZIP_ATT_FILE_CONTENT => $this->getbuf()
                    )
                )
            );
        } else {
            $list = $archive->create(array(
                    array(PCLZIP_ATT_FILE_NAME => $img,
                        PCLZIP_ATT_FILE_CONTENT => $this->getbuf()
                    )
                )
            );
        }
        if ($list == 0) {
            die("ERROR : '" . $archive->errorInfo(true) . "'");
        }

        $this->newbuf('');
        return $this;
    }

    /**
     * Имя сайта
     */
    function post($url, $data)
    {
        return $this->curl($url, [
            'method' => 'POST',
            'data' => $data
        ]);

    }

    function getcontents($url)
    {
        $this->debug = 1;
        $this->curl($url);
        return $this->getbuf();
    }
}
