<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: readFlop.script.php 80519 2013-12-12 08:01:49Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/flop/script/readFlop.script.php $
 * @author $Author: TiantianZhang $(tianming@babeltime.com)
 * @date $Date: 2013-12-12 08:01:49 +0000 (Thu, 12 Dec 2013) $
 * @version $Revision: 80519 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/def/Reward.def.php";
require_once dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ). "/conf/User.cfg.php";

$inFileName = 'common_draw.csv';
$outFileName = 'FLOP';

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
		FlopDef::FLOP_TEMPLATE_ID					=> $index++,				//翻牌模板id
		FlopDef::FLOP_DROP_ARRAY					=> $index++,				//翻牌掉落表id数组
		FlopDef::FLOP_ROB_MIN						=> $index++,				//翻牌掠夺最低等级
		FlopDef::FLOP_ROB_SUC						=> $index++,				//翻牌掠夺成功获得银币基础值
		FlopDef::FLOP_ROB_FAIL						=> $index++,				//翻牌掠夺失败损失银币基础值
		FlopDef::FLOP_RAND_NUM						=> $index++,				//翻牌银币随机系数
		FlopDef::FLOP_DROP_GOLD						=> $index++, 				//翻牌掉落金币数量
		FlopDef::FLOP_DROP_SPECIAL					=> $index++,				//翻牌特殊掉落
);

$arrKeyV2 = array(FlopDef::FLOP_DROP_ARRAY, FlopDef::FLOP_DROP_SPECIAL);

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
		if( in_array($key, $arrKeyV2, true) )
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
						trigger_error( "flop:$data[0] invalid $key, need v2\n" );
					}
					$ary = array2Int(str2Array($value, '|'));
					if ($key == FlopDef::FLOP_DROP_SPECIAL)
					{
						$conf[$key][$ary[0]] = $ary[1];
						if ($ary[0] >= UserConf::MAX_FLOP_NUM)
						{
							trigger_error("flop:$data[0] invalid flop num");
						}
					}
					else 
					{
						$conf[$key][] = array(
								'type' => $ary[0],
								'dropId' => $ary[1],
								'weight' => $ary[2],
						);
					}
				}
			}
		}
		else 
		{
			$conf[$key] = intval($data[$index]);
		}
	}
	
	$confList[$conf[FlopDef::FLOP_TEMPLATE_ID]] = $conf;
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