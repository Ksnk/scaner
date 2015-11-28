<?php
/**
 * Created by PhpStorm.
 * User: Ksnk
 * Date: 24.11.15
 * Time: 19:04
 */

include_once '../../autoload.php';

$config = (object)array(
    'login' => 'pitersvet',
    'password' => 'z99rvYEfpa',

    'archive' => 'archive.zip',
    'excelfile' => 'evrosvet.xlsx',
    'articul_txt' => 'articul.txt',
//    'debug'=>1,

    'phpexcel_path'=>'../../../youlamp/PHPExcel.1.8/',
    'pclzip_path'=>'../../../youlamp/admin/libs/',
);

/**
 * Работа с сайтом евросвет - авторизация и поиск товаров по артикулу.
 * Class scenario
 */
class scenario extends base
{

    /**
     * @var spider
     */
    var $spider;

    /**
     * @var string
     */
    var
        $login,
        $password;

    var $archive = 'archive.zip';

    /**
     * @var joblist
     */
    var $joblist;

    /**
     * Просто результат операции, чтобы было что выковыривать.
     * @var
     */
    var $result;

    /**
     * сценарий открытия сайта и авторизации
     */
    function open_evrosvet()
    {
        $this->spider
            ->open('http://eurosvet.ru/')
            ->post("/handlers/make.auth.handler.php", array(
                'inputLogin' => $this->login,
                'inputPassword' => $this->password,
            ));
    }

    /**
     * Поиск по артикулу. Поиск дает 2 варианта - либо переход на страницу с результатами,
     *   либо переход на карточку товаров с единственной карточкой
     * @param $articul
     */
    function searchfor($articul)
    {
        $this->spider
            ->open('/poisk/?' . http_build_query(array('q' => $articul)));
        if (empty($this->spider->lasturl['query'])) {
            $this->scanitem('', $articul);
        } else {
            $this->spider
                ->scan("<h1>Результаты поиска</h1>")
                ->doscan('~<a href=\'([^\']+)\' class=\'cLink\'~', 1);

            foreach ($this->spider->result as $res) {
                $this->joblist->append_scenario('scanitem', $res, $articul);
            }
        }
    }

    /**
     * Сканирование карточки товара
     * @param $res
     * @param $articul
     */
    function scanitem($res, $articul)
    {
        if (!empty($res)) {
            $this->spider
                ->open($res);
        }
        $this->spider
            ->scan('~<h1>(.*?)</h1>~', 1, 'name')
            ->scan('~"main_img"\s+src="(.*?)"~', 1, 'big_img')
            ->scan('<h3>Все изображения:</h3>')
            ->until("<div class='clearfix'></div>");
        $res = $this->spider->result;
        $this->joblist->append_scenario('upload_img', $res['big_img'], $articul);
        $res['articul'] = $articul;
        $this->spider->result = array();
        $this->spider
            ->doscan('~<a href=\'(.*?)\'\s+class="itemFoto~is', 1);
        $res['morefoto'] = implode("\r\n", $this->spider->result);
        foreach ($this->spider->result as $i)
            $this->joblist->append_scenario('upload_img', $i, $articul);
        $this->spider->result = array();
        $this->spider
            ->scan("~<h3>Другие модели серии '(.*?)':</h3>~", 1, 'collection')
            ->scan('~<p class="prod-price">(.*?) руб.</p>~', 1, 'price')
            ->scan('Характеристики:')
            ->until('</table>');
        $res = array_merge($res, $this->spider->result);
        $this->spider->result = array();
        $sub = '';
        do {
            $start = $this->spider->start;
            $this->spider
                ->scan('~<td>(.*?)</td><td>(.*?)</td>|<th colspan=2>(.*?)</th>~', 1, 'name', 2, 'value', 3, 'sub');
            if ($start != $this->spider->start) {
                if (!empty($this->spider->result['sub'])) {
                    $sub = $this->spider->result['sub'];
                } else {
                    $name = trim($this->spider->result['name'], ':');
                    if (!empty($sub)) $name = rtrim($sub, ':') . ':' . $name;
                    $res[$name] = $this->spider->result['value'];
                    $this->result['names'][$name] = 1;
                }
            }
            $this->spider->result = array();
        } while ($start != $this->spider->start);

        $this->result['result'][] = $res;
    }

    function upload_img($img, $articul)
    {
        $articul = iconv('utf-8', 'cp866//ignore', $articul);
        if (!isset($this->imagecache)) $this->imagecache = array();
        if (!isset($this->imagecache[$articul])) $this->imagecache[$articul] = array();
        if (isset($this->imagecache[$articul][$img])) return;
        $this->imagecache[$articul][$img] = true;
        $suff = count($this->imagecache[$articul]) - 1;
        $ext = '';
        if (preg_match('/\.([^\.]+)$/', $img, $m)) {
            $ext = '.' . $m[1];
        }

        $this->spider->uploadfile($img,
            preg_replace(array('/\./', '~[/\\\\]~'), array('---', '----'), $articul) . ($suff > 0 ? '_' . $suff : '') . $ext,
            $this->archive
        );
    }

}

ini_set('display_error', 1);
error_reporting(E_ALL);

echo '<pre>';
$config->spider = new spider();
$config->joblist = new joblist();
$config->scenario = new scenario();


$config->spider->init($config);
$config->joblist->init($config);
$config->scenario->init($config);

/** @var joblist $jobs */
$jobs = $config->joblist;
$jobs->append_scenario('open_evrosvet');

if (is_readable($config->archive))
    unlink($config->archive);
$file = file($config->articul_txt);
$search = array();
foreach ($file as $a) {
    //$x=explode(" ",trim($a)); $x=trim($x[0]);
    $x = trim($a);
    if (empty($x) || isset($search[$x])) continue;
    $search[$x] = true;
    $jobs->append_scenario('searchfor', $x);
}

$config->scenario->result['names'] = array(
    'articul' => 1,
    'name' => 1,
    'big_img' => 1,
    'morefoto' => 1,
    'collection' => 1,
    'price' => 1
);
$config->scenario->result['result'] = array();
//print_r($jobs);
while ($jobs->donext()) {
    ;
}

$data_arr = array(array_keys($config->scenario->result['names']));

foreach ($config->scenario->result['result'] as $kk => $v) {
    $x = array();
    foreach ($config->scenario->result['names'] as $k => $vv) {

        if (isset($v[$k])) {
            if ($k == 'price') {
                $v[$k] = preg_replace(array('/\s+/', '~\.00~', '~\.~'),
                    array('', '', ','), $v[$k]);
            }

            $x[] = strip_tags($v[$k]);
        } else
            $x[] = '';
    }

    $data_arr[] = $x;
}

require_once $config->phpexcel_path.'PHPExcel.php';

$objPHPExcel = new PHPExcel();

$objPHPExcel->getProperties()->setCreator('test')
    ->setLastModifiedBy('test')
    ->setTitle('evroset items at ' . date("d.m.y G.i:s"))
    ->setSubject('test')
    ->setDescription('some evroset items at ' . date("d.m.y G.i:s"))
    ->setKeywords('test')
    ->setCategory('test');

$objPHPExcel->getActiveSheet()->fromArray($data_arr, NULL, 'A1');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save($config->excelfile);