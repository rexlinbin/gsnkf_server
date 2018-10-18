<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ExpUserBase.script.php 165828 2015-04-07 06:42:58Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/script/ExpUserBase.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-04-07 06:42:58 +0000 (Tue, 07 Apr 2015) $
 * @version $Revision: 165828 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Copy.def.php";

$csvFile = 'expcopy.csv';
$outFile = 'EXPUSER';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $csvFile $outFile\n");
}


if ( $argc < 3 )
{
	echo "Please input enough arguments:!expcopy.csv output\n";
	trigger_error ("input error params.");
}

$ZERO = 0;

$arrConfKey = array(
		EXP_USER_FIELD::BASE_ID => $ZERO,
		EXP_USER_FIELD::ARMY_ID => ++$ZERO,
		EXP_USER_FIELD::BASE_LEVEL => ($ZERO += 3),
		EXP_USER_FIELD::DROP_ID => ++$ZERO,
);

$file = fopen($argv[1]."/$csvFile", 'r');

$line = fgetcsv($file);
$line = fgetcsv($file);

$base = array();
$bases = array();

while (TRUE)
{
	$base = array();
	$line = fgetcsv($file);
	if (empty($line) || empty($line[0]))
	{
		break;
	}
	
	foreach ($arrConfKey as $key => $value)
	{
		$base[$key] = intval($line[$value]);
	}
	
	$bases[$base[EXP_USER_FIELD::BASE_ID]] = $base;
}

fclose($file);

$file = fopen($argv[2]."/$outFile", 'w');
fwrite($file, serialize($bases));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */