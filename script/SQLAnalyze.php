<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SQLAnalyze.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/SQLAnalyze.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
require_once ('/home/pirate/rpcfw/lib/TableGenerator.class.php');
require_once ('/home/pirate/rpcfw/lib/Util.class.php');

function main()
{

	global $argc, $argv;
	if ($argc < 2)
	{
		echo "usage: php $argv[0] file\n";
		exit ( 0 );
	}

	$filename = $argv [1];
	if (isset ( $argv [2] ))
	{
		$day = $argv [2];
	}
	else
	{
		$day = date ( 'Ymd', strtotime ( '-1 days' ) );
	}

	$from = 'hoping@babeltime.com';
	$to = 'rd@babeltime.com';
	$server = gethostname ();
	$subject = "[STAT][$server]dataproxy请求统计-$day";

	$pattern = '#(\d+) (.+)#';
	$file = fopen ( $filename, 'r' );
	if (empty ( $file ))
	{
		echo sprintf ( "open file:%s failed\n", $filename );
		exit ( 0 );
	}

	$arrConfig = array ('num' => array ('colName' => '总请求次数' ),
			'ratio' => array ('colName' => '请求占比' ), 'sql' => array ('colName' => 'SQL' ) );
	$tableGen = new TableGenerator ( $arrConfig );

	$totalNum = 0;
	$arrRet = array ();
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
			$num = $arrMatch [1];
			$totalNum += $num;
			$sql = $arrMatch [2];
			$arrRet [] = array ('num' => $num, 'sql' => $sql );
		}
		else
		{
			echo sprintf ( "line:%s not match\n", $line );
		}
	}

	foreach ( $arrRet as &$arrRow )
	{
		$arrRow ['ratio'] = sprintf ( '%.2f', $arrRow ['num'] / $totalNum * 100 );
		unset ( $arrRow );
	}

	$arrRet [] = array ('num' => $totalNum, 'sql' => '总计', 'ratio' => '100.00' );

	$message = $tableGen->generate ( $arrRet );
	$content = $tableGen->generateCsv ( $arrRet );
	
	//write csv file
	$handle = fopen("csv/sql." . $day . ".csv", "w");
	fwrite($handle, $content);
	fclose($handle);
	
	$arrAttachment = array (
			array ('name' => 'sql.csv', 'type' => 'test/csv', 'content' => $content ) );
	Util::sendMail ( $to, $from, $subject, $message, true, $arrAttachment );
}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */