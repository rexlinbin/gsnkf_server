<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SupplyLogic.class.php 260702 2016-09-05 11:06:48Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/supply/SupplyLogic.class.php $
 * @author $Author: GuohaoZheng $(tianming@babeltime.com)
 * @date $Date: 2016-09-05 11:06:48 +0000 (Mon, 05 Sep 2016) $
 * @version $Revision: 260702 $
 * @brief 
 *  
 **/
class SupplyLogic
{
	public static function getSupplyInfo($uid)
	{
		Logger::trace('SupplyLogic::getSupplyInfo Start.');
		
		$info = EnUser::getExtraField(UserExtraDef::USER_EXTRA_FIELD_EXECUTION_TIME, $uid);
		
		/* 20160822
		//没有这个用户的数据
		if (empty($info))
		{
			$info = 0;
		}
		*/ 
		
		EnRetrieve::refreshData($uid);
		
		$ret = 0;
		if ( TRUE == Util::isSameDay($info) )
		{
		    $ret = $info;
		}
		else
		{
		    $arrExec = EnUser::getExtraInfo('va_exec');
		    if ( !empty( $arrExec ) )
		    {
		        $ret = max($arrExec);
		    }
		}
		
		Logger::trace('SupplyLogic::getSupplyInfo End.');
		return $ret;
	}
	
	public static function supplyExecution($uid)
	{
		Logger::trace('SupplyLogic::supplyExecution Start.');
		
		EnRetrieve::refreshData($uid);
		
		//取得当前时间和时分秒
		$now = Util::getTime();
		$hour = strftime("%H:%M:%S", $now);
		
		//查询用户当前属于整点送体力的哪个时间段
		$flag = false;
		foreach (ActivityConf::$SUPPLY_TIME_ARR as $key => $value)
		{
			if ($hour >= $value[0] && $hour <= $value[1]) 
			{
				$flag = true;
				//获取上次补给时间
				$supplyTime = 0;
				$supplyIntTime = EnUser::getExtraField(UserExtraDef::USER_EXTRA_FIELD_EXECUTION_TIME, $uid);
				
				$arrExec = array();
				if ( TRUE == Util::isSameDay($supplyIntTime) )
				{
				    $supplyTime = $supplyIntTime;
				    $arrExec[] = $supplyTime;
				}
				else 
				{
				    $arrExec = EnUser::getExtraInfo('va_exec');
				    if ( !empty( $arrExec ) )
				    {
				        $supplyTime = max($arrExec);
				    }
				}
				
				$supplyHour = strftime("%H:%M:%S", $supplyTime);
				Logger::trace('last supply hour is:%s, now hour is:%s', $supplyHour, $hour);
				//如果上次补给时间在当前时间段内就领过奖，报错
				if (Util::isSameDay($supplyTime) 
				&& $supplyHour >= $value[0] 
				&& $supplyHour <= $value[1])
				{
					throw new FakeException('supply execution already');
				}
				
				if ( TRUE == Util::isSameDay($supplyIntTime) )
				{
				    EnUser::setExtraField(UserExtraDef::USER_EXTRA_FIELD_EXECUTION_TIME, $now, $uid);
				}
				
				if ( !empty( $arrExec ) && FALSE == Util::isSameDay($arrExec[0]) )
				{
				    $arrExec = array();
				}
				
				$arrExec[] = $now;
				
				EnUser::setExtraInfo('va_exec', $arrExec, $uid);
				
				//给用户加体力
				$user = EnUser::getUserObj($uid);
				$user->addExecution(ActivityConf::SUPPLY_NUM);
				if(EnActivity::isOpen(ActivityName::SUPPLY))
				{
					$user->addGold(ActivityConf::SUPPLY_NUM, StatisticsDef::ST_FUNCKEY_SUPPLY_REWARD);
				}
				$user->update();
			}
		}
		
		if ($flag == false) 
		{
			throw new FakeException('supply execution time is wrong');
		}
		
		EnActive::addTask(ActiveDef::SUPPLY);
		
		Logger::trace('SupplyLogic::supplyExecution End.');
		
		return ActivityConf::SUPPLY_NUM;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */