<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Foundry.script.php 210406 2015-11-18 04:06:08Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/forge/script/Foundry.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-18 04:06:08 +0000 (Wed, 18 Nov 2015) $
 * @version $Revision: 210406 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Forge.def.php";

$inFileName = 'foundry_equipment.csv';
$outFileName = 'FOUNDRY';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
	exit("usage: $inFileName $outFileName\n");
}

if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:inputDir && outputDir!\n" );
}

$inputDir = $argv[1];
$outputDir = $argv[2];

//数据对应表
$index = 1;
$arrConfKey = array (
		ForgeDef::FOUNDRY_BASE			=> $index++,
		ForgeDef::FOUNDRY_FORM			=> $index++,
		ForgeDef::FOUNDRY_COST			=> $index++,
		ForgeDef::FOUNDRY_ITEM			=> $index++,
		ForgeDef::BASE_QUALITY			=> ($index+=4)-1,
		ForgeDef::FORM_QUALITY			=> $index++,
);

$arrKeyV1 = array(
		ForgeDef::FOUNDRY_BASE,
);

$arrKeyV2 = array(
		ForgeDef::FOUNDRY_COST,
);

$arrKeyV3 = array(
		ForgeDef::FOUNDRY_ITEM,
);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while ( TRUE )
{
	$data = fgetcsv($file);
	if ( empty($data) )
	{
		break;
	}

	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
		if( in_array($key, $arrKeyV1, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$conf[$key] = array2Int( str2array($data[$index]) );
			}
		}
		elseif( in_array($key, $arrKeyV2, true) )
		{
			if (empty($data[$index]))
			{
				$conf[$key] = array();
			}
			else
			{
				$arr = str2array($data[$index]);
				$conf[$key] = array();
				foreach( $arr as $value )
				{
					if(!strpos($value, '|'))
					{
						trigger_error( "foundry:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					if (!in_array($ary[0], array(1,2,3)))
					{
						trigger_error( "foundry:$data[0] invalid cost type $ary[0]" );
					}
					$conf[$key][][$ary[0]] = $ary[1];
				}
			}
		}
		elseif ( in_array($key, $arrKeyV3, true) )
		{
			for ($i = 0; $i < 3; $i++)
			{
				if (empty($data[$index + $i]))
				{
					$conf[$key][$i] = array();
				}
				else
				{
					$arr = str2array($data[$index + $i]);
					$conf[$key][$i] = array();
					foreach( $arr as $value )
					{
						if(!strpos($value, '|'))
						{
							trigger_error( "foundry:$data[0] invalid $key, need v2\n" );
						}
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$i][$ary[0]] = $ary[1];
					}
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	$confList[$data[0]] = $conf;
}

fclose($file);

print_r($confList);

//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */