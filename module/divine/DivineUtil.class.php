<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DivineUtil.class.php 252543 2016-07-20 08:22:01Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/DivineUtil.class.php $
 * @author $Author: GuohaoZheng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-07-20 08:22:01 +0000 (Wed, 20 Jul 2016) $
 * @version $Revision: 252543 $
 * @brief 
 *  
 **/
class DivineUtil
{
	public static function refreshCurStars()
	{
		return self::rollStars( DivineCfg::CURRENT_STARS_NUM );
	}
	
	public static function refreshTargStars( $level , $finishNum = 0 )
	{
		$conf = btstore_get()->DIVI_PRIZE[ $level ][ 'tar_aster_arr' ];
		if ( !isset( $conf[ $finishNum ] ) )
		{
			$rollNum = $conf[ count( $conf ) -1 ][ 0 ];
		}
		else 
		{
			//根据等级和完成的次数从配置表取出接下来目标星座要刷出几颗星
			$rollNum = btstore_get()->DIVI_PRIZE[ $level ][ 'tar_aster_arr' ][ $finishNum ][ 0 ];
		}
		
		return self::rollStars( $rollNum );
	}
	
	public static function rollStars( $num )
	{
		$sampleArr = btstore_get()->DIVI_ASTER[ 'sample_arr' ];
		$result = Util::backSample( $sampleArr , $num );
		return $result;
	}
	
	public static function reward( $uid , $rewardArr )
	{
		$diviInst = DivineObj::getInstance($uid);
		$diviLevel = $diviInst->getLevel();
		
		$user = EnUser::getUserObj( $uid );
		$bag = null;

		$bagInst = BagManager::getInstance()->getBag($uid);
		$bagIsfull =  $bagInst->isFull();//只在一开始的时候检查一下背包
		foreach ( $rewardArr as $oneRewardArr )
		{
			if ( !isset( $oneRewardArr[ 0 ] )||!isset( $oneRewardArr[ 1 ] ) )
			{
				throw new ConfigException( 'no conf in in rewardArr' );
			}
			
			switch ( $oneRewardArr[ 0 ] )
			{
				case DivineCfg::REWARD_SOUL :
					$user->addSoul( $oneRewardArr[ 1 ] );
					break;
				case DivineCfg::REWARD_SILVER:
					$user->addSilver( $oneRewardArr[ 1 ] );
					break;
				case DivineCfg::REWARD_GOLD:
					$user->addGold( $oneRewardArr[ 1 ], StatisticsDef::ST_FUNCKEY_DIVI_REWARD);
					break;
				case DivineCfg::REWARD_ITEM:
					$bag = BagManager::getInstance()->getBag($uid);
					if( $diviLevel > 1 )
					{
						$rewardItemNum = $oneRewardArr[ 2 ];
					}
					else
					{
						$rewardItemNum = DivineCfg::REWARD_NUM;
					}
			
					if( $bagIsfull )
					{
						throw new FakeException( 'bag is full' );
					}
					if ( !$bag->addItemByTemplateID( $oneRewardArr[ 1 ], $rewardItemNum, true ))
					{
						throw new InterException( 'add item failed, item id %d, num %d ', $oneRewardArr[ 1 ], $rewardItemNum );
					}
					break;
				default:
					Logger::fatal( ' nothing for this sick babe? ' );
					break;
			}
			
		}
		
		$bagChanged = false;
		if ( $bag != null )
		{
			$bagChanged = true;
			/* 
			$user = EnUser::getUserObj( $uid );
			$userInfo = array( 
					'uid' 	=> $uid,
					'uname' => $user->getUname(),
					'utid'	=> $user->getUtid(),
			 ); */
			
			//ChatTemplate::getDiviItem( $userInfo , array( $rewardArr[ 1 ] => DivineCfg::REWARD_NUM  ));
		}
		return $bagChanged;
	}
	
	public static function sendToRewardCenter( $uid ,$undrewPrizeArr )
	{
		if ( empty( $undrewPrizeArr ) )
		{
			return ;
		}
		//遍历奖励转化成奖励中心接受的形式
		$standardPrizeArr = self::standardArr( $undrewPrizeArr );
		EnReward::sendReward($uid, RewardSource::DIVI_REMAIN, $standardPrizeArr);
	}
	
	public static function standardArr( $undrewPrizeArr )
	{
		$standardArr = array();
		foreach ( $undrewPrizeArr as $key => $val )
		{
			if ( empty( $val ) )
			{
				throw new InterException( 'divi prize: %d should not be empty' , $key );
			}
			switch ( $val[ 0 ] )
			{
				case DivineCfg::REWARD_SILVER:
					self::addKeyValue($standardArr, RewardType::SILVER, $val[ 1 ]);
					break;
				case DivineCfg::REWARD_SOUL:
					self::addKeyValue($standardArr, RewardType::SOUL , $val[ 1 ]);
					break;
				case DivineCfg::REWARD_GOLD:
					self::addKeyValue($standardArr, RewardType::GOLD , $val[ 1 ]);
					break;
				case DivineCfg::REWARD_ITEM:
				    $num = isset( $val[2] ) ? $val[2] : DivineCfg::REWARD_NUM;
					self::addKeyValueItem($standardArr, RewardType::ARR_ITEM_TPL, array( $val[ 1 ] => $num ));
					break;
				default:
					throw new InterException( 'no such type(%d) of prize in divi' , $val[0] );	
			}
		}
		
		return $standardArr;
	}
	
	private static function addKeyValue(&$arr, $key, $value)
	{
		if (!isset($arr[$key]))
		{
			$arr[$key] = $value;
		}
		else
		{
			$arr[$key] += $value;
		}
	}
	
	private static function addKeyValueItem( &$arr, $key, $value )
	{
		foreach ( $value as $id => $num )
		{
			if ( !isset( $arr[ $key ][ $id ] ) )
			{
				$arr[ $key ][ $id ] = $num;
			}
			else
			{
				$arr[ $key ][ $id ] += $num;
			}
		}
	}
	
	public static function refreshReward( $prizeLevel )
	{
		$newRewardList = array();
		
		$prizeConf = btstore_get()->DIVI_PRIZE[$prizeLevel];
		if ( count( $prizeConf['integ_arr']  ) != count( $prizeConf['newReward'] ) )
		{
			throw new ConfigException( 'interarr newrewardarr num different' );
		}
		foreach ( $prizeConf['newReward'] as $key => $rewardInfoArr )
		{
			$ret = Util::backSample( $rewardInfoArr , 1);
			$newRewardList[$key] = $ret[0];
		}
		
		return $newRewardList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */