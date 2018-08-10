<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 06.08.2018
 * Time: 14:09
 */
//include_once '../../autoload.php';

class rmsp_csv_scenario extends scenario {

    /**
     * Optimization Enabled / Disabled
     *
     * @var bool
     */
    protected $bOptimize = true;

    protected $code='utf-8';


    function write($s){
        if($this->code!='utf-8')
            return  mb_convert_encoding($s,'utf-8','cp866');
        else
            return $s;
    }

    /**
     * Method to optimize assoc tree.
     * ( Deleting 0 index when element
     *  have one attribute / value )
     *
     * @param array $mData
     */
    public function optXml(&$mData) {
        if (is_array($mData)) {
            if (isset($mData[0]) && count($mData) == 1 ) {
                $mData = $mData[0];
                if (is_array($mData)) {
                    foreach ($mData as &$aSub) {
                        $this->optXml($aSub);
                    }
                }
            } else {
                foreach ($mData as &$aSub) {
                    $this->optXml($aSub);
                }
            }
        }
    }

    protected function val($val){
       /* return preg_replace_callback('/&\#x([0-9a-f]{4});/i', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'cp866', 'UCS-16BE');
        }, $val);
       /* &#x413; */
        return $val; // preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            //return mb_convert_encoding(pack('H*', $match[1]), 'utf-8', 'UCS-2BE');
        //}, $val);
    }

    protected function parseXml( XMLReader $oXml ) {

        $aAssocXML = null;
        $iDc = -1;

        while($oXml->read()){
            switch ($oXml->nodeType) {

                case XMLReader::END_ELEMENT:

                    if ($this->bOptimize) {
                        $this->optXml($aAssocXML);
                    }
                    return $aAssocXML;

                case XMLReader::ELEMENT:
                    $name=$oXml->name;
                    if(!isset($aAssocXML[$name])) {
                        if($oXml->hasAttributes) {
                            $aAssocXML[$name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$name] = '';
                            } else {
                                $aAssocXML[$name] = $this->parseXML($oXml);
                            }
                        }
                    } elseif (is_array($aAssocXML[$name])) {
                        if (!isset($aAssocXML[$name][0]))
                        {
                            $temp = $aAssocXML[$name];
                            foreach ($temp as $sKey=>$sValue)
                                unset($aAssocXML[$name][$sKey]);
                            $aAssocXML[$name][] = $temp;
                        }

                        if($oXml->hasAttributes) {
                            $aAssocXML[$name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$name][] = '';
                            } else {
                                $aAssocXML[$name][] = $this->val($this->parseXML($oXml));
                            }
                        }
                    } else {
                        $mOldVar = $aAssocXML[$name];
                        $aAssocXML[$name] = array($mOldVar);
                        if($oXml->hasAttributes) {
                            $aAssocXML[$name][] = $oXml->isEmptyElement ? '' : $this->parseXML($oXml);
                        } else {
                            if($oXml->isEmptyElement) {
                                $aAssocXML[$name][] = '';
                            } else {
                                $aAssocXML[$name][] = $this->parseXML($oXml);
                            }
                        }
                    }

                    if($oXml->hasAttributes) {
                        //if(!isset($aAssocXML[$name]))$aAssocXML[$name]=[];
                        while($oXml->moveToNextAttribute()) {
                            if(!isset($aAssocXML[$oXml->name])) $aAssocXML[$oXml->name]=[''];
                            $mElement =& $aAssocXML[$oXml->name][count($aAssocXML[$oXml->name]) - 1];
                            @$mElement = $oXml->value;
                        }
                    }
                    break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:

                    $aAssocXML[++$iDc] = $this->val($oXml->value);

            }
        }

        return $aAssocXML;
    }

    function scansinglexml($_xml){
        $xml=new XmlReader(); $xml->open($_xml);
        $reader = $this->parseXml ($xml);
        //$x=simplexml_load_file($xml);
        print_r($reader);
    }

    /**
     * Делаем это
     */
    function do_it(){
        $this->scansinglexml('d:/projects/nalog.reestr\xxx.xml'
            //'d:/projects/nalog.reestr/VO_RRMSPSV_0000_9965_20160809_fd52e76d-9d2c-4c30-9826-ce18d7ec438e.xml'
        );
        echo $this->write('А вот!');
        echo "Hello world!";
    }

}
/*
$x= new rmsp_csv_scenario();
$x->do_it();*/