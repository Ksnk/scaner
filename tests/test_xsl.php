<?php
/**
 * Created by PhpStorm.
 * User: Аня
 * Date: 30.11.15
 * Time: 22:50
 */
error_reporting(E_ALL);
function parse_exel($exc) {
    $ws = $exc->worksheet['data'][0];		//$exc->worksheet['data'][0] - только первый лист
    if( is_array($ws) && isset($ws['max_row']) ) {
        for($i=0;$i<=$ws['max_row'];$i++) {
            if(isset($ws['cell'][$i]) && is_array($ws['cell'][$i]) ) {
                // Данные ячейки:
                for($j=0;$j<=$ws['max_col'];$j++) {
                    if(is_array($ws['cell'][$i]) && isset($ws['cell'][$i][$j])) {
                        if($i!==0) {		// Со второго ряда и далее
                            $colname=$col[$j];//uc2html($col[$j]);
                            if($ws['cell'][$i][$j]['type']===0) {
                                $excel[0][$i][$colname] = ($exc->sst['unicode'][$ws['cell'][$i][$j]['data']]) ? trim(uc2html($exc->sst['data'][$ws['cell'][$i][$j]['data']])) : trim($exc->sst['data'][$ws['cell'][$i][$j]['data']]);
                            }
                            elseif($ws['cell'][$i][$j]['type']===1) {
                                $excel[0][$i][$colname]= (int)($ws['cell'][$i][$j]['data']);
                            }
                            elseif($ws['cell'][$i][$j]['type']===2) {
                                $excel[0][$i][$colname]= (float)($ws['cell'][$i][$j]['data']);
                            }
                            elseif($ws['cell'][$i][$j]['type']===3) {
                                $ret = $exc->getDateArray($ws['cell'][$i][$j]['data']);
                                $excel[0][$i][$colname]= $ret['day'].'-'.$ret['month'].'-'.$ret['year'];
                            }
                            else {
                                $excel[0][$i][$colname]= '';
                            }
                        } else {		// Первый ряд - имена полей
                            $col[$j]=($exc->sst['unicode'][$ws['cell'][$i][$j]['data']]) ? trim(uc2html($exc->sst['data'][$ws['cell'][$i][$j]['data']])) : trim($exc->sst['data'][$ws['cell'][$i][$j]['data']]);
                        }
                    }
                }
            }
        }
        return (isset($excel)) ? $excel[0] : '';
    }
}
function fatal($msg = '') {
    $cont='[Fatal error]';
    $cont.=": $msg";
    $cont.="<br>\nВыполнение Скрипта прервано <br>\n";
    echo $cont;
    exit();
}
// Функция, создающая нагрузку
function uc2html($str) {
    return iconv('ucs-2LE','utf-8',$str);
/*    $ret = '';
    for( $i=0; $i<strlen($str)/2; $i++ ) {
        $charcode = ord($str[$i*2])+256*ord($str[$i*2+1]);
        $ret .= '&#'.$charcode.';';
    }
    return html_entity_decode($ret,ENT_NOQUOTES,'UTF-8');/**/
}

    include (dirname(__FILE__).'/excel-reader.php');
    $err_corr = "Неподдерживаемый формат или битый файл";

    $excel_file_size;
    $excel_file = '2evrosvet.xls';

    if( $excel_file == '' ) fatal("Файл не загружен");

    $exc = new ExcelFileParser("debug.log", ABC_NO_LOG);

    $style = 'old';
    if($style == 'old') {
        $fh = @fopen ($excel_file,'rb');
        if( !$fh ) fatal("Файл не загружен");
        if( filesize($excel_file)==0 ) fatal("Файл не загружен");
        $fc = fread( $fh, filesize($excel_file) );
        @fclose($fh);
        if( strlen($fc) < filesize($excel_file) )
            fatal("Немогу прочитать файл");

        $res = $exc->ParseFromString($fc);
    }
    elseif($style == 'segment') {
        $res = $exc->ParseFromFile($excel_file);
    }

    switch ($res) {
        case 0: break;
        case 1: fatal("Невозможно открыть файл");
        case 2: fatal("Файл, слишком маленький чтобы быть файлом Excel");
        case 3: fatal("Ошибка чтения заголовка файла");
        case 4: fatal("Ошибка чтения файла");
        case 5: fatal("Это - не файл Excel или файл, сохраненный в Excel < 5.0");
        case 6: fatal("Битый файл");
        case 7: fatal("В файле не найдены данные  Excel");
        case 8: fatal("Неподдерживаемая версия файла");
        default:fatal("Неизвестная ошибка");
    }
    //echo '<pre>';print_r($exc);echo '</pre>';exit;
    //$col=columns_num($exc);
    $excel=parse_exel($exc);

    unset($exc);
    print_r($excel);

