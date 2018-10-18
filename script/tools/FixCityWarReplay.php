<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixCityWarReplay.php 197759 2015-09-10 05:22:17Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixCityWarReplay.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-10 05:22:17 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197759 $
 * @brief 
 *  
 **/
class FixCityWarReplay extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$cityId = intval($arrOption[1]);
		
		$now = Util::getTime();
		list($signStartTime, $signEndTime) = CityWarLogic::getSignupTime();
		if ($now >= $signStartTime && $now <= $signEndTime)
		{
			$signStartTime -= CityWarConf::ROUND_DURATION;
			$signEndTime -= CityWarConf::ROUND_DURATION;
		}
		$list = CityWarDao::getCityAttackList($cityId, $signStartTime, $signEndTime);
		
		//获得战报的详细信息, 跟场次有关
		foreach ($list as $key => $value)
		{
			$signupId = $value[CityWarDef::SIGNUP_ID];
			$timer = $value[CityWarDef::ATTACK_TIMER];
			$ret = TimerDAO::getTask($timer);
			if (empty($value[CityWarDef::ATTACK_REPLAY])) 
			{
				printf("cityId:%d signupId:%d has no replay\n", $cityId, $signupId);
				Logger::info('cityId:%d signupId:%d has no replay', $cityId, $signupId);
				if ($ret['execute_time'] < Util::getTime()) 
				{
					printf("cityId:%d signupId:%d is execute already\n", $cityId, $signupId);
					Logger::info('cityId:%d signupId:%d is execute already', $cityId, $signupId);
					if ($fix) 
					{
						$arrField = array(CityWarDef::ATTACK_TIMER => 0);
						CityWarDao::updateAttack($signupId, $arrField);
					}
				}
				else 
				{
					printf("cityId:%d signupId:%d is not execute yet\n", $cityId, $signupId);
					Logger::info('cityId:%d signupId:%d is not execute yet', $cityId, $signupId);
				}
				
				
			}
		}
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */