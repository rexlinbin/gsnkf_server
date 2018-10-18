<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnCityWar.class.php 112797 2014-06-06 07:57:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/EnCityWar.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-06-06 07:57:39 +0000 (Fri, 06 Jun 2014) $
 * @version $Revision: 112797 $
 * @brief 
 *  
 **/
class EnCityWar
{
	/**
	 * 是否报名城池战
	 * 
	 * @param int $guildId
	 * @return boolean
	 */
	public static function isSignup($guildId)
	{
		list($signupStartTime, $signupEndTime) = CityWarLogic::getSignupTime();
		$signupList = CityWarDao::getGuildSignupList($guildId, $signupStartTime, $signupEndTime);
		$cityList = CityWarDao::getGuildCityList($guildId);
		
		//列表非空说明军团报名了
		if (!empty($signupList) || !empty($cityList)) 
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
	/**
	 * 规定军团哪些操作是有禁止时间的
	 * type:
	 * 0.加入是在城池战报名结束前一个小时内禁止的
	 * 1.退出，踢出, 解散是在城池战报名结束前一个小时到战斗结束内禁止的
	 * 
	 * @param int $type
	 * @return boolean
	 */
	public static function inForbiddenTime($type = 0)
	{
		$now = Util::getTime();
		list($signupStartTime, $signupEndTime) = CityWarLogic::getSignupTime();
		$battleEndTime = CityWarLogic::getBattleEndTime();
		$forbidenStartTime = $signupEndTime - 3600;
		$forbidenEndTime = $type == 0 ? $signupEndTime : $battleEndTime;
		Logger::trace('now:%s forbidenStartTime:%s forbidenEndTime:%s', $now, $forbidenStartTime, $forbidenEndTime);
		if ($now >= $forbidenStartTime && $now <= $forbidenEndTime)
		{
			return true;
		}
		else 
		{
			return false;
		}
		
	}
	
	/**
	 * 获取这一轮城池战报名结束时间
	 * @return int 
	 */
	public static function getSignupEndTime()
	{
		list($signupStartTime, $signupEndTime) = CityWarLogic::getSignupTime();
		return $signupEndTime;
	}
	
	/**
	 * 获取上一次城池战报名结束时间
	 * 用于刷新军团成员周贡献
	 * @return int
	 */
	public static function getLastSignupEndTime()
	{
		return CityWarLogic::getLastSignupEndTime();
	}

	/**
	 * 获得城池占领效果
	 * 
	 * 1:军团组队银币奖励
	 * 2：试练塔银币奖励
	 * 3：摇钱树银币奖励
	 * 4：普通副本银币奖励
	 * 5：精英副本银币奖励
	 * 
	 * @param int $uid
	 * @param int $type
	 */
	public static function getCityEffect($uid, $type)
	{
		if (!in_array($type, CityWarDef::$CITY_VALID_TYPES)) 
		{
			throw new FakeException('invalid type:%d', $type);
		}
		
		$effect = 0;
		$cityId = CityWarLogic::getGuildCityId($uid);
		if (!empty($cityId) && !empty(btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_EFFECT][$type]))
		{
			$effect = btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_EFFECT][$type];
		}

		return $effect;
	}
	
	public static function getGuildCityId($uid)
	{
		return CityWarLogic::getGuildCityId($uid);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */