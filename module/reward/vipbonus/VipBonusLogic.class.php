<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: VipBonusLogic.class.php 237823 2016-04-12 09:28:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/vipbonus/VipBonusLogic.class.php $
 * @author $Author: MingTian $(hoping@babeltime.com)
 * @date $Date: 2016-04-12 09:28:41 +0000 (Tue, 12 Apr 2016) $
 * @version $Revision: 237823 $
 * @brief 
 *  
 **/

class VipBonusLogic
{
	public static function getVipBonusInfo($uid)
	{
		Logger::trace('getVipBonusInfo. uid:%d', $uid);
		
		$vipBonus = BonusManager::getInstance();
		return array(
				VipBonusDef::BONUS => $vipBonus->getBonusReceTime() > 0 ? 1 : 0,
				VipBonusDef::WEEK_GIFT => array_keys($vipBonus->getWeekGift()), 
		);
	}
	
	public static function fetchVipBonus($uid)
	{
		//检查配置
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		$conf = btstore_get()->VIP_DAILYBONUS;
		if (!isset($conf[$vip])) 
		{
			throw new FakeException('user can not fetch vip:%d bonus', $vip);
		}
		
		//检查是否已领
		$vipBonus = BonusManager::getInstance();
		if ($vipBonus->getBonusReceTime() > 0) 
		{
			throw new FakeException('user has fetched vip bonus today');
		}
		
		//发奖
		$ret = RewardUtil::reward3DArr($uid, $conf[$vip]->toArray(), StatisticsDef::ST_FUNCKEY_VIP_DAILY_BONUS);
		
		//更新
		$vipBonus->setBonusReceTime(Util::getTime());
		$vipBonus->update();
		RewardUtil::updateReward($uid, $ret);
	
		return "ok";
	}
	
	public static function buyWeekGift($uid, $vip)
	{
		//检查配置
		$conf = btstore_get()->VIP_WEEKGIFT;
		if (!isset($conf[$vip]))
		{
			throw new FakeException('user can not buy vip:%d week gift', $vip);
		}
		
		//检查金币
		$user = EnUser::getUserObj($uid);
		if (!$user->subGold($conf[$vip]['cost'], StatisticsDef::ST_FUNCKEY_VIP_WEEK_GIFT_COST)) 
		{
			throw new FakeException('user has no enough gold to buy vip:%d week gift', $vip);
		}
		
		//检查是否已经买过
		$vipBonus = BonusManager::getInstance();
		if(key_exists($vip, $vipBonus->getWeekGift()))
		{
			throw new FakeException("user has buy vip:%d week gift already", $vip);
		}
	
		//发奖
		$ret = RewardUtil::reward3DArr($uid, $conf[$vip]['reward']->toArray(), StatisticsDef::ST_FUNCKEY_VIP_WEEK_GIFT_REWARD);
		
		//更新
		$vipBonus->addWeekGift($vip, Util::getTime());
		$vipBonus->update();
		$user->update();
		RewardUtil::updateReward($uid, $ret);
	
		return "ok";
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */