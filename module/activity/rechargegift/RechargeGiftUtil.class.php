<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGiftUtil.class.php 207312 2015-11-04 11:48:43Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/RechargeGiftUtil.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-04 11:48:43 +0000 (Wed, 04 Nov 2015) $
 * @version $Revision: 207312 $
 * @brief 
 *  
 **/
class RechargeGiftUtil
{
	public static function getConfStartTime()
	{
		$conf = EnActivity::getConfByName(ActivityName::RECHARGEGIFT);
		return $conf['start_time'];
	}
	
	public static function getConfData()
	{
		$conf =  EnActivity::getConfByName(ActivityName::RECHARGEGIFT);
		$dataConf = $conf['data'];
		return $dataConf;
	}
	
	public static function getRewardType($rewardId)
	{
		$data = self::getConfData();
		if (!empty($data[$rewardId][RechargeGiftDef::UNSELECT_REWARD]))
		{
			return RechargeGiftDef::UNSELECT_TYPE;
		}
		else 
		{
			return RechargeGiftDef::SELECT_TYPE;
		}
	}
	
	public static function getReqGold($rewardId)
	{
		$data = self::getConfData();
		return $data[$rewardId][RechargeGiftDef::REQ_GOLD];
	}
	
	public static function getConfRewardArr($rewardId)
	{
		$data = self::getConfData();
		$rewardType = self::getRewardType($rewardId);
		$rewardArrName = (RechargeGiftDef::UNSELECT_TYPE == $rewardType) ? RechargeGiftDef::UNSELECT_REWARD : RechargeGiftDef::SELECT_REWARD ; 
		return $data[$rewardId][$rewardArrName];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */