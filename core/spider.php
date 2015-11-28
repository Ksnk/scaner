<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 18:52
 */


/**
 * Паучёк
 * Class spider
 */
class spider extends scaner
{

    var $curl,
        $debug = 0,
        $siteroot,
        $cookie_file = 'tmp_cookie.txt',
        $pclzip_path;

    var $lasturl = array(
        'scheme' => 'http',
        'host' => '',
        'user' => '',
        'pass' => '',
        'path' => '',
        'query' => '',
        'fragment' => '',
    );

    private function curl($url, $opt = array())
    {
        $ch = curl_init();
        $this->siteroot = $this->buildurl($url, true);
        echo $this->siteroot;
        $redirect = '';
        if ($this->debug) {
            $verbose = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $verbose);
        }

        $user_agent = 'Mozilla/5.0 (Windows; U;
Windows NT 5.1; ru; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9';
        $header = array(
            "Accept: text/xml,application/xml,application/xhtml+xml,
    text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
            "Accept-Language: ru-ru,ru;q=0.7,en-us;q=0.5,en;q=0.3",
            "Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7",
 //           "Keep-Alive: 300"
        );

        curl_setopt($ch, CURLOPT_URL, $this->siteroot); // set url to post to
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
        //curl_setopt($ch, CURLOPT_HTTP_VERSION,'1.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // times out after 4s

        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);


        $x = curl_exec($ch);
        if ($this->debug) {
            rewind($verbose);
            echo stream_get_contents($verbose);
        }
        if (false === $x) {
            echo 'Ошибка curl: ' . curl_error($ch);
            $this->newbuf(''); // run the whole process
        } else {
            $this->newbuf($x); // run the whole process
            $x = $this->buildurl(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), true);
            if ($x != $this->siteroot) {
                echo '=>' . $x;
            } else {
                $info = curl_getinfo($ch);
                if ($info['http_code'] == 302) {
                    $redirect = $info['redirect_url'];
                }
            }
        }
        echo "\n";

        if ($this->debug) {
            $version = curl_version();
            $info = curl_getinfo($ch);
            printf('
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
        if (!empty($redirect)) {
            $this->curl($redirect);
        }
    }

    /**
     * Build url from last opened url's
     * @param $url
     * @param bool $update
     * @return string
     */
    function buildurl($url, $update = false)
    {
        $xurl = parse_url($url);

        if (!isset($xurl['scheme'])) { // короткий адрес
            $xurl['scheme'] = $this->lasturl['scheme'];
            $xurl['host'] = $this->lasturl['host'];
            if ($xurl['path']{0} != '/') {
                $xurl['path'] = preg_replace('~[^/]*$~', '', $this->lasturl['path']) . $xurl['path'];
            }
        }
        if ($update) {
            $this->lasturl = $xurl;
        }
        return $xurl['scheme'] . '://' . $xurl['host']
        . $xurl['path']
        . (!empty($xurl['query']) ? '?' . $xurl['query'] : '');
    }

    /**
     * Имя сайта
     */
    function open($url)
    {
        $this->curl($url);
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
                        PCLZIP_ATT_FILE_CONTENT => $this->buf
                    )
                )
            );
        } else {
            $list = $archive->create(array(
                    array(PCLZIP_ATT_FILE_NAME => $img,
                        PCLZIP_ATT_FILE_CONTENT => $this->buf
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
        //$curl
        $ch = curl_init();
        $this->siteroot = $this->buildurl($url, true);
        //echo 'POST '.http_build_query($data); var_dump($this->siteroot);

        curl_setopt($ch, CURLOPT_URL, $this->siteroot); // set url to post to
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // allow redirects
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        $this->newbuf(curl_exec($ch));
        $this->buildurl(curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), true);
        curl_close($ch);
        return $this;
    }

}
