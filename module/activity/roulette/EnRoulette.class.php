<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnRoulette.class.php 171082 2015-05-05 11:09:46Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/EnRoulette.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-05 11:09:46 +0000 (Tue, 05 May 2015) $
 * @version $Revision: 171082 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";

class EnRoulette
{
	// 积分轮盘解析函数
	public static function readRouletteCSV($arrData,$version, $startTime, $endTime , $needOpenTime)
	{
		$ZERO = 0;
		$arrConfKey = array(
				'id' => $ZERO,
				RouletteDef::BTSTORE_ROULETTE_NEED_GOLD => ++$ZERO,
				RouletteDef::BTSTORE_ROULETTE_INTEGERAL => ++$ZERO,
				RouletteDef::BTSTORE_ROULETTE_REWARD => ++$ZERO,
				'box_integeral' => ++$ZERO,
				'box_reward1' => ++$ZERO,
				'box_reward2' => ++$ZERO,
				'box_reward3' => ++$ZERO,
				RouletteDef::BTSTORE_ROULETTE_FIELD_WEIGHT => ++$ZERO,
				RouletteDef::BTSTORE_ROULETTE_ACCUM_DROP => ++ $ZERO,
				'drop1' => $ZERO+=2,
				'drop2' => ++$ZERO,
				'drop3' => ++$ZERO,
				'drop4' => ++$ZERO,
				'drop5' => ++$ZERO,
				'drop6' => ++$ZERO,
				'drop7' => ++$ZERO,
				'drop8' => ++$ZERO,
				'drop9' => ++$ZERO,
				'drop10' => ++$ZERO
		);
		
		//兼容老配置，老配置可刷
		if (count($arrData[0]) > 21 )
		{
			$arrConfKey[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY] = ++$ZERO;
			$arrConfKey[RouletteDef::BTSTORE_ROULETTE_MIN_POINT] = ++$ZERO;
			$arrConfKey[RouletteDef::BTSTORE_ROULETTE_RANK_REWARD] = ++$ZERO;
		}
		
		$confList = array();
		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			$conf = array();
			
			foreach ($arrConfKey as $key => $index)
			{
				switch ($key)
				{
					case RouletteDef::BTSTORE_ROULETTE_REWARD:
						$arrRouletteRewardConf = Util::str2Array($data[$index],',');
						$conf[$key] = array();
						foreach ($arrRouletteRewardConf as $index => $rouletteConf)
						{
							$conf[$key][] = array_map('intval', Util::str2Array($rouletteConf,'|'));
						}
						break;
					case 'box_integeral':
						$arrRouletteIntegeralConf = Util::str2Array($data[$index],',');
						$conf[$key] = array();
						foreach ($arrRouletteIntegeralConf as $index => $rouletteConf)
						{
							$arrRouletteIntegeralInfo = array_map('intval', Util::str2Array($rouletteConf,'|'));
							$conf[$key][$arrRouletteIntegeralInfo[0]] = $arrRouletteIntegeralInfo[1];
						}
						break;
					case 'box_reward1':
					case 'box_reward2':
					case 'box_reward3':
						$arrBoxRewardConf = Util::str2Array($data[$index],',');
						$conf[$key] = array();
						foreach ($arrBoxRewardConf as $index => $boxRewardConf)
						{
							$conf[$key][] = array_map('intval', Util::str2Array($boxRewardConf,'|'));
						}
						break;
					case RouletteDef::BTSTORE_ROULETTE_FIELD_WEIGHT:
						$arrWeightConf = Util::str2Array($data[$index],',');
						$conf[$key] = array();
						foreach ($arrWeightConf as $index => $weightConf)
						{
							$arrWeightInfo = array_map('intval', Util::str2Array($weightConf,'|'));
							$conf[$key][$arrWeightInfo[0]] = $arrWeightInfo[1];
						}
						break;
					case RouletteDef::BTSTORE_ROULETTE_ACCUM_DROP:
						$arrAccumDropConf = Util::str2Array($data[$index],',');
						$conf[$key] = array();
						foreach ($arrAccumDropConf as $index => $accumDropConf)
						{
							$arrAccumDropInfo = array_map('intval', Util::str2Array($accumDropConf,'|'));
							$conf[$key][$arrAccumDropInfo[0]] = $arrAccumDropInfo[1];
						}
						break;
					case 'drop1':
					case 'drop2':
					case 'drop3':
					case 'drop4':
					case 'drop5':
					case 'drop6':
					case 'drop7':
					case 'drop8':
					case 'drop9':
					case 'drop10':
						$arrDropConf = Util::str2Array($data[$index],',');
						$conf[$key] = array();
						foreach ( $arrDropConf as $index => $dropConf )
						{
							$arrDropInfo = array_map('intval', Util::str2Array($dropConf,'|'));
							$conf[$key][] = array(
									'type' => $arrDropInfo[0],
									'id' => $arrDropInfo[1],
									'num' => $arrDropInfo[2],
									'weight' => $arrDropInfo[3],
							);
						}
						break;
					case RouletteDef::BTSTORE_ROULETTE_RANK_REWARD:
						$conf[$key] = array();
						for ($i = $index; $i < $index+count(RouletteDef::$rank_level);$i++)
						{
							$arrRewardInfo = array();
							$arrRankRewardConf = Util::str2Array($data[$i],',');
							foreach ($arrRankRewardConf as $rewardConf)
							{
								$arrRewardInfo[] = array_map('intval', Util::str2Array($rewardConf,'|'));
							}
							$conf[$key][] = $arrRewardInfo;
						}
						break;
					default:
						$conf[$key] = intval($data[$index]);
				}
			}
			
			$confList[RouletteDef::BTSTORE_ROULETTE_NEED_GOLD] = $conf[RouletteDef::BTSTORE_ROULETTE_NEED_GOLD];
			$confList[RouletteDef::BTSTORE_ROULETTE_INTEGERAL] = $conf[RouletteDef::BTSTORE_ROULETTE_INTEGERAL];
			$confList[RouletteDef::BTSTORE_ROULETTE_REWARD] = $conf[RouletteDef::BTSTORE_ROULETTE_REWARD];
			$confList['box_integeral'] = $conf['box_integeral'];
			$confList['box_reward'] = array(
					$conf['box_reward1'],
					$conf['box_reward2'],
					$conf['box_reward3'],
			);
			$confList[RouletteDef::BTSTORE_ROULETTE_FIELD_WEIGHT] = $conf[RouletteDef::BTSTORE_ROULETTE_FIELD_WEIGHT];
			$confList[RouletteDef::BTSTORE_ROULETTE_ACCUM_DROP] = $conf[RouletteDef::BTSTORE_ROULETTE_ACCUM_DROP];
			$confList['drops'] = array(
					$conf['drop1'],
					$conf['drop2'],
					$conf['drop3'],
					$conf['drop4'],
					$conf['drop5'],
					$conf['drop6'],
					$conf['drop7'],
					$conf['drop8'],
					$conf['drop9'],
					$conf['drop10'],
			);
			
			if (!isset($conf[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY])
					&& !isset($conf[RouletteDef::BTSTORE_ROULETTE_MIN_POINT])
					&& !isset($conf[RouletteDef::BTSTORE_ROULETTE_RANK_REWARD]))
			{
				$dayBetween = intval( (strtotime(date("Y-m-d ",$endTime)) - strtotime(date("Y-m-d ",$startTime)) ) / SECONDS_OF_DAY );
				
				
				$conf[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY] = $dayBetween + 1;
				$conf[RouletteDef::BTSTORE_ROULETTE_MIN_POINT] = 1000000;
				$conf[RouletteDef::BTSTORE_ROULETTE_RANK_REWARD] = array();
			}
			else 
			{
				$rollDay = 0;
				if (!empty($conf[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY]))
				{
					$rollDay = $conf[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
				}
				$zeroStartTime = intval(strtotime(date("Y-m-d ",$startTime)));
				if ($zeroStartTime + ($rollDay + 1) * SECONDS_OF_DAY -1 > $endTime)
				{
					throw new ConfigException('@cehua:give one day for rank reward.');
				}
			}
			$confList[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY] = $conf[RouletteDef::BTSTORE_ROULETTE_ROLL_DAY];
			$confList[RouletteDef::BTSTORE_ROULETTE_MIN_POINT] = $conf[RouletteDef::BTSTORE_ROULETTE_MIN_POINT];
			$confList[RouletteDef::BTSTORE_ROULETTE_RANK_REWARD] = $conf[RouletteDef::BTSTORE_ROULETTE_RANK_REWARD];
		}
		
		if (empty($confList['drops']))
		{
			throw new ConfigException('@cehua: Need new conf for new drop.');
		}
		
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */