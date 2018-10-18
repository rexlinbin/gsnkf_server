<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnLordwar.class.php 171767 2015-05-08 03:02:25Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/EnLordwar.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-05-08 03:02:25 +0000 (Fri, 08 May 2015) $
 * @version $Revision: 171767 $
 * @brief 
 *  
 **/
class EnLordwar
{
	public static function readLordwarCSV($arrData)
	{
		$index = 0;
		$keyArr = array(
				'id' => $index,
				'registerLv' => ++$index,
				'loseNumArr' => ++$index,
				'startTimeArr' => ++$index,
				'registerLastTime' => ++$index,
				'championLastTime' => ++$index,
				'sess' => ++$index,
				'subRoundGapAudition' => ++$index,
				'subRoundGapCross' => ++$index,
				'upFmtCdArr' => ++$index,
				'clrCdGold' => ++ $index,
				'innerFightPrize' => ++$index,
				'crossFightPrize' => ++$index,
				'supportCostBase' => ++$index,
				'supportPrize' => ++$index,
				'worldPrize' => ++$index,
				'worshipPrizeArr' => ++$index,   
				'worshipCostArr' => ++$index,
				LwShop::WM => ($index+= 3),
		);
		
		$arrayOne = array('loseNumArr','upFmtCdArr','supportPrize','worshipPrizeArr',);
		$arrayTwo= array(
				'startTimeArr','innerFightPrize','crossFightPrize',
				'supportCostBase', 'worshipCostArr', LwShop::WM,
		);
		
		$confList = array();
		foreach ( $arrData as $data )
		{
			$conf = array();
			if ( empty( $data )||empty( $data[0] ) )
			{
				break;
			}

			foreach ( $keyArr as $confKey => $confIndex )
			{
				if( in_array( $confKey , $arrayTwo) )
				{
					if(empty( $data[$confIndex] ))
					{
						$conf[$confKey] = array();
					}
					else
					{
						$tmp = explode( ',' , $data[$confIndex] );
						foreach ( $tmp as $key => $val )
						{
							$tmp[$key] = array_map( 'intval' , explode( '|' , $val));
						}
						$conf[$confKey] = $tmp;
					}
				}
				elseif( in_array( $confKey , $arrayOne) )
				{
					if( empty($data[$confIndex]) )
					{
						$conf[$confKey] = array();
					}
					else
					{
						$conf[$confKey] = array_map( 'intval' , explode( ',' , $data[$confIndex]));
					}
				}
				else 
				{
					$conf[$confKey] = intval( $data[$confIndex] );
				}
			}
			
			
			$tmp2 = array();
			foreach ( $conf['innerFightPrize'] as $innerPrizeType => $prizeArr )
			{
				$prizeAfter = array();
				foreach ( $prizeArr as $prizeIndex => $prizeId )
				{
					$prizeAfter[pow( 2 , intval( $prizeIndex ))] = $prizeId;
				}
				
				$tmp2['innerFightPrize'][$innerPrizeType] = $prizeAfter;
				
			}
			
			unset( $conf['innerFightPrize'][0] );
			unset( $conf['innerFightPrize'][1] );
			$conf['innerFightPrize'][LordwarTeamType::WIN] = $tmp2['innerFightPrize'][0];
			$conf['innerFightPrize'][LordwarTeamType::LOSE] =  $tmp2['innerFightPrize'][1] ;
			
			$tmp = array();
			foreach ( $conf['crossFightPrize'] as $innerPrizeType => $prizeArr )
			{
				$prizeAfter = array();
				foreach ( $prizeArr as $prizeIndex => $prizeId )
				{
					$prizeAfter[pow( 2 , intval( $prizeIndex ))] = $prizeId;
				}
			
				$tmp['crossFightPrize'][$innerPrizeType] = $prizeAfter;
			
			}
			unset( $conf['crossFightPrize'][0] );
			unset( $conf['crossFightPrize'][1] );
			$conf['crossFightPrize'][LordwarTeamType::WIN] =  $tmp['crossFightPrize'][0] ;
			$conf['crossFightPrize'][LordwarTeamType::LOSE] =  $tmp['crossFightPrize'][1] ;
			
			$conf['lordPrize'][LordwarField::INNER] =  $conf['innerFightPrize'];
			$conf['lordPrize'][LordwarField::CROSS] =  $conf['crossFightPrize'];
			unset( $conf['innerFightPrize'] );
			unset( $conf['crossFightPrize'] );
			
			
			$tmp = array();
			$tmp[LordwarField::INNER] = $conf['supportPrize'][0];
			$tmp[LordwarField::CROSS] = $conf['supportPrize'][1];
			$conf['supportPrize'] = $tmp;
			
			$tmp = array();
			$tmp[LordwarField::INNER] = $conf['supportCostBase'][0];
			$tmp[LordwarField::CROSS] = $conf['supportCostBase'][1];
			$conf['supportCostBase'] = $tmp;
			
			$tmp = array();
			foreach ( $conf['startTimeArr'] as $round => $timeArr )
			{
				$tmp['startTimeArr'][$round + LordwarRound::REGISTER] = $timeArr;
			}
			$conf['startTimeArr'] = $tmp['startTimeArr'];
			
			$tmp = array();
			foreach ( $conf['upFmtCdArr'] as $round => $timeArr )
			{
				$tmp['upFmtCdArr'][$round + LordwarRound::INNER_AUDITION] = $timeArr;
			}
			$conf['upFmtCdArr'] = $tmp['upFmtCdArr'];
			//这个地方的解析了好几个时段，但是只用了第一个，策划后期改的
			
			$tmp = array();
			foreach ( $conf[ LwShop::WM ] as $exchangKey => $exchangeConf )
			{

				$exchangeId = $exchangKey+1;
					
				switch ($exchangeConf[0])
				{
					case RewardConfType::SILVER:
						$tmp[$exchangeId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SILVER] = $exchangeConf[2];
						break;
					case RewardConfType::SOUL:
						$tmp[$exchangeId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_SOUL] = $exchangeConf[2];
						break;
					case RewardConfType::GOLD:
						$tmp[$exchangeId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_GOLD] = $exchangeConf[2];
						break;
					case RewardConfType::ITEM_MULTI:
						$item = array($exchangeConf[1] => $exchangeConf[2]);
						$tmp[$exchangeId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_ITEM] = $item;
						break;
					case RewardConfType::HERO_MULTI:
						$hero = array($exchangeConf[1] => $exchangeConf[2]);
						$tmp[$exchangeId][MallDef::MALL_EXCHANGE_ACQ][MallDef::MALL_EXCHANGE_HERO] = $hero;
						break;
					default:
						trigger_error("unsupported goods type: $exchangeConf[0].\n");
				}
				$tmp[$exchangeId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA][LwShop::NEEDWM] = $exchangeConf[3];
				$tmp[$exchangeId][MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_NUM] = $exchangeConf[4];
				
				$conf[ LwShop::WM ] = $tmp;
			}
			
			$confList[$conf['id']] = $conf;
			
		}
		return $confList;

	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */