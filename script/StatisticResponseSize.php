<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StatisticResponseSize.php 90503 2014-02-18 07:02:58Z TiantianZhang $
 * 
 **************************************************************************/
require_once ('/home/pirate/rpcfw/lib/TableGenerator.class.php');
require_once ('/home/pirate/rpcfw/lib/Util.class.php');
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/StatisticResponseSize.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-02-18 07:02:58 +0000 (Tue, 18 Feb 2014) $
 * @version $Revision: 90503 $
 * @brief 
 *  
 **/
function statisticRspFromLog($logFile)
{
    $file = fopen ( $logFile, 'r' );
    $pattern = '#.+Connection.cpp:727]response:([0-9a-zA-Z]+)#';
    if (empty ( $file ))
    {
        echo sprintf ( "open file:%s failed\n", $logFile );
        exit ( 0 );
    }
    $arrResponse = array();
    while ( ! feof ( $file ) )
    {
        $line = fgets ( $file );
        $line = trim ( $line );
        if (empty ( $line ))
        {
            continue;
        }
        $arrMatch = array ();
        if (preg_match ( $pattern, $line, $arrMatch ))
        {
            if(!isset($arrMatch[1]))
            {
                echo "not matched.".$line."\n";
                continue;
            }
            $response = $arrMatch[1];
            $decodeData = decodeRsp($response);
            var_dump($decodeData);
            if(empty($decodeData))
            {
                echo "empty decodeData\n";
                continue;
            }
            
            $ret = $decodeData;
            $length = $ret['length'];
            $callBack = $ret['callback'];
            if(!isset($arrResponse[$callBack]))
            {
                $arrResponse[$callBack] = array();
            }
            $arrResponse[$callBack][] = $length;
        }
    }
    fclose($file);
    return $arrResponse;
}

function statisticRspSize($arrResponse)
{
     $arrRspSize = array();
     foreach($arrResponse as $callback => $arrSize)
     {
         $sizeNum = count($arrSize);
         $sizeSum = array_sum($arrSize);
         $arrRspSize[$callback] = array('callback'=>$callback,'size'=>$sizeSum/$sizeNum,'num'=>$sizeNum);
     }   
     generateCsvTbl($arrRspSize);
}

function generateCsvTbl($arrRspSize)
{
    $arrConfig = array (
            'callback' => array ('colName' => '请求名' ),
            'num' => array ('colName' => '请求总数目' ),
            'size'=>array('colName'=>'响应数据平均大小'),
    );
    
    $tableGen = new TableGenerator ( $arrConfig );
    $content = $tableGen->generateCsv ( $arrRspSize );
    global $argc, $argv;
    $day = $argv[1];
    $handle = fopen ( "/home/pirate/rspsize." . $day . ".csv", "w" );
    fwrite ( $handle, $content );
    fclose ( $handle );
}

function decodeRsp($data)
{
    var_dump($data);
    $length = strlen(hex2bin($data));
    if($length >= 1024000)
    {
        echo "gzuncompress data\n";
        $data = gzuncompress($data);
        if (false === $data)
        {
            echo "gzuncompress failed.\n";
            return array();
        }
    }
    $data = pack("H".strlen($data), $data);
    $data = chr(0x11) . $data;
    $arrData = amf_decode($data, 7);
    if((!isset($arrData['ret'])) || !is_array($arrData['ret']))
    {
        echo "ret is not array.\n";
        $arrDataArray = objectToArray($arrData);
        if(isset($arrDataArray['callback']) && (is_string($arrDataArray['callback'])))
        {
            $callBack = $arrDataArray['callback'];
        }
        elseif(isset($arrDataArray['callback']['callbackName']))
        {
            $callBack = $arrDataArray['callback']['callbackName'];
        }
    }
    else
    {
        foreach( $arrData['ret'] as $key => $ret)
        {
            $arrData['ret'][$key] = amf_decode(chr(0x11).$ret);
        }
        $arrDataArray = objectToArray($arrData);
        if(isset($arrDataArray['ret'][0]['callback']) && is_string($arrDataArray['ret'][0]['callback']))
        {
            $callBack = ($arrDataArray['ret'][0]['callback']);
        }
        elseif(isset($arrDataArray['ret'][0]['callback']['callbackName']))
        {
            $callBack = $arrDataArray['ret'][0]['callback']['callbackName'];
        }
    }
    var_dump($arrDataArray);
    if(!isset($callBack))
    {
        return array();
    }
    var_dump(array('length'=>$length,'callback'=>$callBack));
    return array('length'=>$length,'callback'=>$callBack);
}

function objectToArray($e)
{
    $e=(array)$e;
    foreach($e as $k=>$v)
    {
        if( gettype($v)=='resource' )
            return;
        if( gettype($v)=='object' || gettype($v)=='array' )
            $e[$k]=(array)objectToArray($v);
    }
    return $e;
}

function hex2bin( $str ) {
    $sbin = "";
    $len = strlen( $str );
    for ( $i = 0; $i < $len; $i += 2 ) {
        $sbin .= pack( "H*", substr( $str, $i, 2 ) );
    }

    return $sbin;
}

function isSameDay($checkTime)
{

    // 获取当前时间
    $referenceTime = time();
    // 如果检查时间小于判定时刻
    if (date ( "Y-m-d ", $checkTime ) === date ( "Y-m-d ", $referenceTime ))
    {
        // 尚未通过这个时刻，返回 TRUE
        return true;
    }
    // 已经通过这个时刻，返回 FALSE
    return false;
}

function main()
{
    global $argc, $argv;
    if ($argc < 1)
    {
        echo "usage: php $argv[0] day\n";
        exit ( 0 );
    }
    $cmdStr = "> /tmp/response";
    exec($cmdStr);
    $day = $argv[1];
    $cmdStr = "grep 'Connection.cpp:727]response' /home/pirate/lcserver/log/lcserver.log.$day* > /tmp/response";
    exec($cmdStr);
    isSameDay(strtotime($day));
    $cmdStr = "grep 'Connection.cpp:727]response' /home/pirate/lcserver/log/lcserver.log >> /tmp/response";
    exec($cmdStr);
    $rsp = statisticRspFromLog("/tmp/response");
    statisticRspSize($rsp);
}
main();

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */