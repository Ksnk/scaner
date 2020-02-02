<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 02.02.2020
 * Time: 15:34

предназначен для решения вот такой проблемы, на определенном сайте

if(!function_exists('idn_to_utf8')) {
    function idn_to_utf8($url){
        return idn\IDN::decodeIDN($url);
    }
}

 * отсюда: https://www.php.net/manual/ru/function.idn-to-utf8.php#112097
 */
namespace idn;
use \Ksnk\scaner\traitHandledStaticClass;
// for those who has PHP older than version 5.3
class IDN {
    use traitHandledStaticClass;
    // adapt bias for punycode algorithm
    private static function punyAdapt(
        $delta,
        $numpoints,
        $firsttime
    ) {
        $delta = $firsttime ? $delta / 700 : $delta / 2;
        $delta += $delta / $numpoints;
        for ($k = 0; $delta > 455; $k += 36)
            $delta = intval($delta / 35);
        return $k + (36 * $delta) / ($delta + 38);
    }

    /**
     * translate character to punycode number
     * @param $cp
     * @return int
     * @throws \Exception
     */
    private static function decodeDigit($cp) {
        $cp = strtolower($cp);
        if ($cp >= 'a' && $cp <= 'z')
            return ord($cp) - ord('a');
        elseif ($cp >= '0' && $cp <= '9')
            return ord($cp) - ord('0')+26;
        self::error('Что-то пошло не так');
    }

    /**
     * make utf8 string from unicode codepoint number
     * @param $cp
     * @return string
     * @throws \Exception
     */
    private static function utf8($cp) {
        if ($cp < 128) return chr($cp);
        if ($cp < 2048)
            return chr(192+($cp >> 6)).chr(128+($cp & 63));
        if ($cp < 65536) return
            chr(224+($cp >> 12)).
            chr(128+(($cp >> 6) & 63)).
            chr(128+($cp & 63));
        if ($cp < 2097152) return
            chr(240+($cp >> 18)).
            chr(128+(($cp >> 12) & 63)).
            chr(128+(($cp >> 6) & 63)).
            chr(128+($cp & 63));
        // it should never get here
        self::error('Что-то пошло не так');
    }

    // main decoding function
    private static function decodePart($input) {
        if (substr($input,0,4) != "xn--") // prefix check...
            return $input;
        $input = substr($input,4); // discard prefix
        $a = explode("-",$input);
        if (count($a) > 1) {
            $input = str_split(array_pop($a));
            $output = str_split(implode("-",$a));
        } else {
            $output = array();
            $input = str_split($input);
        }
        $n = 128; $i = 0; $bias = 72; // init punycode vars
        while (!empty($input)) {
            $oldi = $i;
            $w = 1;
            for ($k = 36;;$k += 36) {
                $digit = self::decodeDigit(array_shift($input));
                $i += $digit * $w;
                if ($k <= $bias) $t = 1;
                elseif ($k >= $bias + 26) $t = 26;
                else $t = $k - $bias;
                if ($digit < $t) break;
                $w *= intval(36 - $t);
            }
            $bias = self::punyAdapt(
                $i-$oldi,
                count($output)+1,
                $oldi == 0
            );
            $n += intval($i / (count($output) + 1));
            $i %= count($output) + 1;
            array_splice($output,$i,0,array(self::utf8($n)));
            $i++;
        }
        return implode("",$output);
    }

    public static function decodeIDN($name) {
        // split it, parse it and put it back together
        return
            implode(
                ".",
                array_map("self::decodePart",explode(".",$name))
            );
    }

}
