<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readStar.script.php 133272 2014-09-19 08:30:40Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/scripts/readStar.script.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-09-19 08:30:40 +0000 (Fri, 19 Sep 2014) $
 * @version $Revision: 133272 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Star.def.php";

$inFileName = 'star.csv';
$outFileName = 'STAR';

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

$index = 0;
//对应配置表键名
$arrConfKey = array (
		StarDef::STAR_QUALITY						=> $index+=2,				// 名将品质
		StarDef::STAR_FAVOR_GIFT 					=> $index+=3,				// 名将喜爱的礼物
		StarDef::STAR_FAVOR_ACT 					=> ++$index,				// 名将喜爱的行为
		StarDef::STAR_FAVOR_ABILITY 				=> ++$index,				// 名将的特殊能力
		StarDef::STAR_NEED_HER0						=> $index+=3,				// 激活所需武将ID
		StarDef::STAR_LEVEL_ID						=> ++$index,				// 名将升级所需经验表id
		StarDef::STAR_CAN_FEEL						=> ++$index,				// 名将是否能感悟
		StarDef::STAR_FEEL_SKILLS					=> ++$index,				// 名将感悟等级对应技能ID组
		StarDef::STAR_CHALLENGE_ARMY				=> ++$index,				// 挑战对应部队ID组
		StarDef::STAR_FEEL_ABILITY					=> ++$index,				// 名将感悟属性ID组
		StarDef::STAR_FEEL_EXP						=> ++$index,				// 感悟成长经验表ID
		StarDef::STAR_NORMAL_SKILLS					=> ++$index, 				// 更换普通技能ID
);

$arrKeyV1 = array(StarDef::STAR_FAVOR_GIFT, StarDef::STAR_FAVOR_ACT);
$arrKeyV2 = array(StarDef::STAR_FEEL_SKILLS, StarDef::STAR_CHALLENGE_ARMY, StarDef::STAR_NORMAL_SKILLS);
$arrKeyV3 = array(StarDef::STAR_FAVOR_ABILITY, StarDef::STAR_FEEL_ABILITY);

$file = fopen("$inputDir/$inFileName", 'r');
echo "read $inputDir/$inFileName\n";

// 略过 前两行
$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
	{
		break;
	}

	$conf = array();
	foreach ($arrConfKey as $key => $index)
	{
		if( in_array($key, $arrKeyV1, true) )
		{
			if (empty($data[$index])) 
			{
				$conf[$key] = array();
			}
			else 
			{
				$conf[$key] = array2Int(str2array($data[$index]));
			}
		}
		else if( in_array($key, $arrKeyV2, true) )
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
						trigger_error( "invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					$conf[$key][$ary[0]] = $key != StarDef::STAR_FEEL_SKILLS ? $ary[1] : array($ary[1], $ary[2]);
				}
				ksort($conf[$key]);
			}
		}
		else if( in_array($key, $arrKeyV3, true) )
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
						trigger_error( "star:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					//1好感度等级, 0能力id, 2需要用户等级
					$conf[$key][$ary[1]] = array($ary[0], $ary[2]);
				}
			}
		}
		else
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	foreach ($conf[StarDef::STAR_CHALLENGE_ARMY] as $key => $value)
	{
		$conf[StarDef::STAR_CHALLENGE_ARMY][$key] = array('weight' => $value);
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