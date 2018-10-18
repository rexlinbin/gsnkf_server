<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pet.script.php 230249 2016-03-01 10:36:25Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/script/Pet.script.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-01 10:36:25 +0000 (Tue, 01 Mar 2016) $
 * @version $Revision: 230249 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

$csvFile = 'pet.csv';
$outFileName = 'PET';

if ( isset( $argv[ 1 ] ) && $argv[ 1 ] == '-h' )
{
	exit( "usage: $csvFile $outFileName \n" );
}

if ( $argc < 2 )
{
	echo "Please input enough arguments:!{$csvFile}\n";
	exit;
}

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	echo $argv[1]."/{$csvFile} open failed! exit!\n";
	exit;
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$index = 6;
$keyArr = array(
		'petTid' => 0,
		'qulity' => $index,
		'expTbl' => ++$index,
		'initSkillPoint' => ++$index,
		'skillPointInc' => ++$index,
		'skillPointLvInterval' => ++$index,
		'productSkillsWeightArr' => ++$index,
		'talentSkillsWeightArr' => ++$index,
		'initNormalSkills' => ++$index,
		'canLrnSkills' => ++$index,
		'initSkillSlot' => ++$index,
		'skillSlotLimit' => ++$index,
		'lrnSkillLvLimit' => ++$index,
		'skillUpWeightArr' => ++$index,
		'skillSlotOpenWeightArr' => ($index+=2)-1,
		'swallowAddPoint' => ++$index,
		'canSwallowArr' => ++$index,
		'resetGold' => ++$index,
		'petQuality' => ++$index,
		'petType' => ++$index,
		'talentSkillNum' => ++$index,
		'petPrice' => ($index+=5)-1,
		'lockNum' => ($index+=3) -1,
		'handbookAdditionArr' => $index,
		'ifEvolve' => $index+=2, //是否可进阶
		'evolveLevel' => ++$index, //宠物进阶等级限制
		'evolveCost' => ++$index, //进阶消耗
		'evolveAttr' => ++$index, //进阶获得属性
		'evolveSkill' => ++$index, //进阶解锁技能等级提升

		'washAttr' => ++$index,	//可洗练属性
		'washValue' => ++$index,	//进阶洗练价值
		'washItem' => ++$index,	//洗练物品和档位
		'itemNum' => ++$index,	//洗练消耗物品个数
		'washNum' => ++$index,	//单次洗练属性数量 
		'washWave' => ++$index,	//洗练波动
        'washReturn' => ++$index, //洗练返还值
);

$arrOne = array('canLrnSkills','initNormalSkills','petPrice',);
$arrTwo = array( 
		'productSkillsWeightArr',
		'talentSkillsWeightArr',
		'skillUpWeightArr', 
		'skillSlotOpenWeightArr',
		'canSwallowArr' ,
		'handbookAdditionArr',
		'evolveLevel',
        'evolveAttr',
		'evolveSkill',
		'washAttr',

		'washValue',
		'washItem',
		'itemNum',
		'washNum',
		'washWave'
);

$arrThree = array(
		'evolveCost',
	);



//恶心的配置表。。。。先读出来再说
$confList = array();
while ( true )
{
	$conf  = array();
	
	$data = fgetcsv($file);
	if ( empty( $data )||empty( $data[ 0 ] ) )
	{
		break;
	}
	
	foreach ( $keyArr as $key => $index )
	{
		if ( in_array( $key , $arrOne) )
		{
			if ( empty( $data[$index] ) )
			{
				$conf[$key] = array();
			}
			else 
			{
				$conf[$key] = array_map( 'intval' , explode( ',' , $data[ $index ]));
			}
		}
		else if ( in_array( $key, $arrTwo ) )
		{
			if (empty( $data[$index] ))
			{
				$conf[$key] = array();
			}
			else 
			{
				$conf[$key] = explode( ',' , $data[$index]);
				foreach ( $conf[$key] as $key2 => $val2 )
				{
					$conf[$key][$key2] = array_map( 'intval' , explode( '|' , $val2));
				}

				$confAfter = array();
				if ( $key == 'skillUpWeightArr' || $key == 'skillSlotOpenWeightArr' )
				{
					foreach ( $conf[$key] as $oneIndex => $confInfo )
					{
						$confAfter[$confInfo[1]] = array( 'weight' => $confInfo[0],'val' => $confInfo[1] );
					}
					$conf[$key] = $confAfter;
				}
				else if( $key == 'productSkillsWeightArr' || $key == 'talentSkillsWeightArr')
				{
					foreach ( $conf[$key] as $oneIndex => $confInfo )
					{
						$confAfter[$oneIndex] = array( 
								'id' => $confInfo[0],
								'lv' => $confInfo[1],
								'weight' => $confInfo[2],
						 );
					}
					$conf[$key] = $confAfter;
				}
				else if($key == 'evolveLevel' || $key == 'evolveSkill' || $key == 'washValue' || $key == 'washItem'
					|| $key == 'itemNum')
				{
					foreach($conf[$key] as $oneIndex => $confInfo)
					{
						$confAfter[$confInfo[0]] = $confInfo[1];
					}
					ksort($confAfter);
					$conf[$key] = $confAfter;
				}
				else if($key == 'washNum' || $key == 'washWave')
				{
					foreach($conf[$key] as $oneIndex => $confInfo)
					{
						$confAfter[$confInfo[0]] = $confInfo;
						unset($confAfter[$confInfo[0]][0]);
                        var_dump($confAfter[$confInfo[0]]);
						if($key == 'washNum')
						{
                            $rr = array();
							foreach($confAfter[$confInfo[0]] as $index => $tmpVal)
							{
								$rr[$index - 1] = array('weight' => $tmpVal);
							}
                            $confAfter[$confInfo[0]] = $rr;
						}
					}
					$conf[$key] = $confAfter;
				}
				else if($key == 'washAttr')
				{
					foreach ( $conf[$key] as $oneIndex => $confInfo )
					{
						$confAfter[$confInfo[0]] = array(
							'weight' => $confInfo[1],
							'value' => $confInfo[2],
						);
					}
					$conf[$key] = $confAfter;
				}
                else if($key == 'evolveAttr')
                {
                    foreach ( $conf[$key] as $oneIndex => $confInfo )
                    {
                        $confAfter[$confInfo[0]][] = array(
                            $confInfo[1],
                            $confInfo[2],
                        );
                    }
                    $conf[$key] = $confAfter;
                }
			}
		}
		else if(in_array( $key, $arrThree ) )
		{
			$tmpRet = array();
			$tmp1 = str2Array($data[$index], ';');
			foreach($tmp1 as $index => $indexConf)
			{
				$tmp2 = str2Array($indexConf, ',');
				foreach($tmp2 as $k => $v)
				{
					$tmpRet[$index][] = array2Int(str2Array($v, '|'));
				}
			}
			$conf[$key] = $tmpRet;
		}
		else 
		{
			$conf[$key] = intval( $data[$index] );
		}
	}

	$maxEvolveLevel = 0;
	foreach($conf['evolveLevel'] as $k => $v)
	{
		if($v > $maxEvolveLevel)
		{
			$maxEvolveLevel = $v;
		}
	}
	$conf['maxEvolveLevel'] = $maxEvolveLevel;
	$confList[ $conf[ 'petTid' ] ] = $conf;
}

//var_dump($confList);
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	echo $argv[2].'/'.$outFileName. " open failed! exit!\n";
	exit;
}
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
