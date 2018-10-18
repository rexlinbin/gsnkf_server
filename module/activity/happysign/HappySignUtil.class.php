<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySignUtil.class.php 232026 2016-03-10 08:34:26Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/HappySignUtil.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-10 08:34:26 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232026 $
 * @brief 
 *  
 **/
class HappySignUtil
{
	public static function getConfData($rewardId)
	{
		$conf =  EnActivity::getConfByName(ActivityName::HAPPYSIGN);
		$dataConf = $conf['data'];
		return $dataConf[$rewardId];
	}
	
	/**
	 * 获取今天是活动中的第几天
	 */
	public static function getDateNumOfToday()
	{
		$conf = EnActivity::getConfByName(ActivityName::HAPPYSIGN);
		$startDateZeroTime = strtotime(date('Ymd', $conf['start_time']));
		return intval((Util::getTime() - $startDateZeroTime) / SECONDS_OF_DAY) + 1;
	}
	
	/**
	 * 获得奖励类型; 1:不可选类型, 2:可选类型
	 * @param int $rewardId		奖励id
	 * @return int $type	奖励类型
	 */
	public static function getRewardType($rewardId)
	{
		$data = self::getConfData($rewardId);
		if (!empty($data[HappySignDef::UNSELECT_REWARD]))
		{
			return HappySignDef::UNSELECT_TYPE;
		}
		else
		{
			return HappySignDef::SELECT_TYPE;
		}
	}
	
	public static function getConfRewardArr($rewardId)
	{
		$data = self::getConfData($rewardId);
		$rewardType = self::getRewardType($rewardId);
		$rewardArrName = (HappySignDef::UNSELECT_TYPE == $rewardType) ? HappySignDef::UNSELECT_REWARD : HappySignDef::SELECT_REWARD ;
		return $data[$rewardArrName];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */