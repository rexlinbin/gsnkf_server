<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnTopupFund.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupfund/EnTopupFund.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class EnTopupFund
{
	//消费累计解析函数
	public static function readTopupFundCSV( $arr )
	{
		$index = 0;
		$keyArr = array(
				'id' => $index++,
				'needTopup' => ($index+=2)-1,
				'rewardArr' => $index++,
		);
		
		$arrTwo = array( 'rewardArr' );
		$confList = array();
		foreach ( $arr as $data )
		{
			$conf = array();
			if ( empty( $data ) || empty( $data[0] ) )
			{
				break;
			}
			foreach ( $keyArr as $key => $index )
			{
				if ( is_numeric( $data[ $index ] ) || empty( $data[ $index ] ) )
				{
					$conf[ $key ] = intval( $data[ $index ] );
				}	
				else if ( in_array( $key , $arrTwo) )
				{
					$conf[ $key ] = explode( ',' ,  $data[ $index ] );
					foreach ( $conf[ $key ] as $exKey => $exVal )
					{
						if ( is_numeric( $exVal ) )
						{
							$conf[ $key ][ $exKey ] = intval( $exVal );
						}
						else 
						{
							$conf[ $key ][ $exKey ] = array_map( 'intval' , explode( '|' , $exVal ) );
						}
					}
				}
			}
			$allReward = array();
			foreach ( $conf['rewardArr'] as $rewardKey => $rewardInfo)
			{
				switch ( $rewardInfo[0] )
				{
					case RewardConfType::EXECUTION:
					case RewardConfType::GOLD:
					case RewardConfType::HERO:
					case RewardConfType::ITEM:
					case RewardConfType::JEWEL:
					case RewardConfType::PRESTIGE:
					case RewardConfType::SILVER:
					case RewardConfType::SILVER_MUL_LEVEL:
					case RewardConfType::SOUL:
					case RewardConfType::SOUL_MUL_LEVEL:
					case RewardConfType::EXP_MUL_LEVEL:
					case RewardConfType::STAMINA:
						$allReward[] = array(
						'type' => $rewardInfo[0],
						'val' => $rewardInfo[2],
						);
					break;
					case RewardConfType::HERO_MULTI:
					case RewardConfType::ITEM_MULTI:
					case RewardConfType::TREASURE_FRAG_MULTI:
						$allReward[] = array(
							'type' => $rewardInfo[0],
							'val' => array( 
							array(
							$rewardInfo[1],
							$rewardInfo[2],
							),
							
						),
						);
					break;
					default:
						throw new ConfigException('invalid reward type: %d', $rewardInfo[0]);
				}
			}
			$conf['rewardArr'] = $allReward;
			$id = $conf[ 'id' ];
			unset( $conf[ 'id' ] );
			$confList[ $id ] = $conf;
		}
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */