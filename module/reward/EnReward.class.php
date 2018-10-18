<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnReward.class.php 128479 2014-08-21 14:09:07Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/EnReward.class.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2014-08-21 14:09:07 +0000 (Thu, 21 Aug 2014) $
 * @version $Revision: 128479 $
 * @brief 
 *  
 **/


class EnReward
{
	/**
	 * 给某个人发奖
	 * @param int $uid
	 * @param int $type
	 * @param array $reward, 具体支持哪些奖励 @see RewardType and RewardDef
	 * @param int $time 为竞技场发奖优化支持的传入发件的时间戳
	 * <code>
	 * 	{
	 * 		RewardType::ARR_ITEM_ID : 物品ID数组
	 * 		RewardType::ARR_ITEM_TPL:
	 * 		{
	 * 			tplId:num	物品模板ID => 对应的个数	
	 * 		}
	 * 		RewardType::GOLD :
	 * 		RewardType::SILVER :
	 * 		RewardType::EXE :
	 * 		RewardType::STAMINA :
	 * 		RewardType::PRESTIGE :
	 * 		RewardType::JEWEL :
	 * 		RewardType::ARR_HERO_TPL :
	 *		{
	 * 			tplId:num	英雄模板ID => 对应的个数	
	 * 		}
	 * 		RewardDef::EXT_DATA: 奖励详细信息数组（发奖励者自定）
	 * 	}
	 * </code>
	 */
	
	public static function sendReward($uid, $type, $reward, $db = '')
	{
		$uid = intval( $uid );
		if ( empty( $reward ) )
		{
			return 'noReward';
		}
		return RewardLogic::sendReward($uid, $type, $reward, $db );
	}
	
	
	public static function getRewardByUidTime($uid, $source, $startTime, $arrField = array() )
	{
		if( empty($arrField) )
		{
			$arrField = array(
					RewardDef::SQL_RID,
					RewardDef::SQL_SOURCE,
					RewardDef::SQL_SEND_TIME,
					RewardDef::SQL_VA_REWARD
			);
		}
		$arrRet = RewardDao::getRewardByUidTime($uid, $source, $startTime, $arrField);
		return $arrRet;
	}
	
	public static function vipDailyBonus($uid)
	{
		throw new InterException( ' invalid method vipDailyBonus' );
		if (empty($uid))
		{
			Logger::debug('vipDailyBonus invalid uid: %s', $uid);
			return;
		}
		
		$user = EnUser::getUserObj($uid);
		$lastLoginTime = $user->getLastLoginTime();
		if ( Util::isSameDay( $lastLoginTime ) )
		{
			Logger::debug('vipDailyBonus already login today');
			return;
		}
		
		$vip = $user->getVip();
		$level = $user->getLevel();
		$conf = btstore_get()->VIP_DAILYBONUS;
		if ( !isset( $conf[$vip] ) )
		{
			Logger::debug('vipDailyBonus no conf for vip: %d',$vip );
			return;
		}
		
		$time = Util::getTime();
		$timedayBegin = intval(strtotime(date('Y-m-d', $time)));//TODO
		$ret = RewardDao::getVipBonusToday($uid, $timedayBegin);
		if ( !empty( $ret ) )
		{
			Logger::debug('vipDailyBonus already send');
			return ;
		}
		
		$reward = $conf[$vip]->toArray();
		
		if ( isset( $reward['silvermul'] ) )
		{
			if (!isset( $reward[RewardType::SILVER] ))
			{
				$reward[RewardType::SILVER] = 0;
			}
			$reward[RewardType::SILVER]+= $reward['silvermul'] *$level;
			unset( $reward['silvermul'] );
		}
		if ( isset( $reward['soulmul'] ) )
		{
			if (!isset( $reward[RewardType::SOUL] ))
			{
				$reward[RewardType::SOUL] = 0;
			}
			$reward[RewardType::SOUL]+= $reward['soulmul'] *$level;
			unset( $reward['soulmul'] );
		}

		if (!empty( $reward ))
		{
			$reward[RewardDef::EXT_DATA] = array('vip' => $vip);
			self::sendReward($uid, RewardSource::VIP_DAILY_BONUS, $reward);
		}
		
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */