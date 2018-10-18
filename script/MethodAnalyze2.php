<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MethodAnalyze2.php 69831 2013-10-22 03:29:46Z MingTian $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/MethodAnalyze2.php $
 * @author $Author: MingTian $(hoping@babeltime.com)
 * @date $Date: 2013-10-22 03:29:46 +0000 (Tue, 22 Oct 2013) $
 * @version $Revision: 69831 $
 * @brief
 *
 **/

require_once ('/home/pirate/rpcfw/lib/TableGenerator.class.php');
require_once ('/home/pirate/rpcfw/lib/Util.class.php');

$arrRet = array();
function countFile($filename)
{
	$pattern = '#.+method:(.+), err:(.+), request count:(.+), total cost:(.+)\(ms\), framework cost:(.+)\(ms\), method cost:(.+)\(ms\), request size:(.+)\(byte\), response size:(.+)\(byte\)#';
	$file = fopen ( $filename, 'r' );
	if (empty ( $file ))
	{
		echo sprintf ( "open file:%s failed\n", $filename );
		exit ( 0 );
	}

	global  $arrRet;
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
			$method = $arrMatch [1];
			$err = $arrMatch [2];
			$requestCount = $arrMatch [3];
			$totalCost = $arrMatch [4];
			$frameCost = $arrMatch [5];
			$methodCost = $arrMatch [6];
			$requestSize = $arrMatch [7];
			$responseSize = $arrMatch [8];

			if (isset ( $arrRet [$method] ))
			{
				$arrRet [$method] ['num'] ++;
				$arrRet [$method] ['requestCount'] += $requestCount;
				$arrRet [$method] ['totalCost'] += $totalCost;
				$arrRet [$method] ['frameCost'] += $frameCost;
				$arrRet [$method] ['methodCost'] += $methodCost;
				$arrRet [$method] ['requestSize'] += $requestSize;
				$arrRet [$method] ['responseSize'] += $responseSize;
			}
			else
			{
				$arrRet [$method] = array ('num' => 1, 'suc' => 0, 'fail' => 0,
						'requestCount' => $requestCount, 'totalCost' => $totalCost,
						'frameCost' => $frameCost, 'methodCost' => $methodCost,
						'requestSize' => $requestSize, 'responseSize' => $responseSize );
			}

			if ($err == 'ok' || $err == 'fake')
			{
				$arrRet [$method] ['suc'] ++;
			}
			else
			{
				$arrRet [$method] ['fail'] ++;
			}
		}
		else
		{
			echo sprintf ( "line:%s not match\n", $line );
		}
	}
	fclose($file);
}

function main()
{

	global $argc, $argv;
	if ($argc < 3)
	{
		echo "usage: php $argv[0] day file1 file2\n";
		exit ( 0 );
	}
	
	$day = $argv[1];
	
	$arrFilename = $argv;
	unset($arrFilename[0]);
	unset($arrFilename[1]);

	$from = 'hoping@babeltime.com';
	$to = 'development@bj.babeltime.com';
	$server = gethostname ();
	$subject = "[STAT][$server]rpcfw请求统计-$day";

	foreach ($arrFilename as $filename)
	{
		countFile($filename);
	}

	$arrConfig = array ('method' => array ('colName' => '方法名' ),
			'num' => array ('colName' => '总请求次数' ), 'ratio' => array ('colName' => '请求数占比' ),
			'requestCount' => array ('colName' => '平均外部请求数', 'threshold' => 15,
					'format' => array ('gt' => 'red' ) ),
			'totalCost' => array ('colName' => '平均总请耗时(ms)', 'threshold' => 100,
					'format' => array ('gt' => 'red' ) ),
			'frameCost' => array ('colName' => '平均框架耗时(ms)', 'threshold' => 10,
					'format' => array ('gt' => 'red' ) ),
			'sucRate' => array ('colName' => '请求成功率(百分比)', 'threshold' => 99.99,
					'format' => array ('lt' => 'red' ) ),
			'failNum' => array('colName'=>'失败次数'),
			'requestSize' => array ('colName' => '平均请求大小(byte)', 'threshold' => 6000,
					'format' => array ('gt' => 'red' ) ),
			'responseSize' => array ('colName' => '平均响应大小(byte)', 'threshold' => 6000,
					'format' => array ('gt' => 'red' ) ) );
	$tableGen = new TableGenerator ( $arrConfig );

	global $arrRet;
	ksort ( $arrRet );

	$totalNum = 1;
	$totalCost = 0;
	$totalRequestCount = 0;
	$totalFrameCost = 0;
	$totalMethodCost = 0;
	$totalRequestSize = 0;
	$totalResponseSize = 0;
	$totalSuc = 0;
	$totalFail = 0;
	foreach ( $arrRet as $arrRow )
	{
		$totalNum += $arrRow ['num'];
		$totalCost += $arrRow ['totalCost'];
		$totalRequestCount += $arrRow ['requestCount'];
		$totalFrameCost += $arrRow ['frameCost'];
		$totalMethodCost += $arrRow ['methodCost'];
		$totalRequestSize += $arrRow ['requestSize'];
		$totalResponseSize += $arrRow ['responseSize'];
		$totalSuc += $arrRow ['suc'];
		$totalFail += $arrRow ['fail'];
	}

	foreach ( $arrRet as $key => &$arrRow )
	{
		$num = $arrRow ['num'];
		$arrRow ['method'] = $key;
		$arrRow ['ratio'] = sprintf ( '%.2f', $num / $totalNum * 100 );
		$arrRow ['requestCount'] = intval ( $arrRow ['requestCount'] / $num );
		$arrRow ['totalCost'] = intval ( $arrRow ['totalCost'] / $num );
		$arrRow ['frameCost'] = intval ( $arrRow ['frameCost'] / $num );
		$arrRow ['sucRate'] = sprintf ( '%.2f', $arrRow ['suc'] / $num * 100 );
		$arrRow ['failNum'] =  $arrRow['fail'];
		$arrRow ['requestSize'] = intval ( $arrRow ['requestSize'] / $num );
		$arrRow ['responseSize'] = intval ( $arrRow ['responseSize'] / $num );
		unset ( $arrRow );
	}

	$arrRet ['总计'] = array ('num' => $totalNum, 'method' => '总计', 'ratio' => '100.00',
			'requestCount' => intval ( $totalRequestCount / $totalNum ),
			'totalCost' => intval ( $totalCost / $totalNum ),
			'frameCost' => intval ( $totalFrameCost / $totalNum ),
			'sucRate' => sprintf ( '%.2f', $totalSuc / $totalNum * 100 ),
			'failNum' => $totalFail,
			'requestSize' => intval ( $totalRequestSize / $totalNum ),
			'responseSize' => intval ( $totalResponseSize / $totalNum ) );

	$message = $tableGen->generate ( $arrRet );
	$content = $tableGen->generateCsv ( $arrRet );

	//write csv file
	$handle = fopen ( "csv/method." . $day . ".csv", "w" );
	fwrite ( $handle, $content );
	fclose ( $handle );

	$arrAttachment = array (
			array ('name' => 'method.csv', 'type' => 'test/csv', 'content' => $content ) );
	Util::sendMail ( $to, $from, $subject, $message, true, $arrAttachment );
}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */