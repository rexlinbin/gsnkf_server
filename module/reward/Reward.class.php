<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Reward.class.php 251028 2016-07-11 10:26:26Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/Reward.class.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-07-11 10:26:26 +0000 (Mon, 11 Jul 2016) $
 * @version $Revision: 251028 $
 * @brief 
 *  
 **/

class Reward
{
	/**
	 * @see IReward::getRewardList
	 */
	public function getRewardList($offset = 0, $limit = 0)
	{
		$uid = RPCContext::getInstance()->getUid();

		$ret = RewardLogic::getRewardList($uid, $offset, $limit);
		
		return $ret;
	}
	
	public function receiveReward($rid)
	{
		$uid = RPCContext::getInstance()->getUid();
		
		return RewardLogic::receiveByArrRid($uid, array($rid) );
	}
	
	public function receiveByRidArr( $ridArr )
	{
		$uid = RPCContext::getInstance()->getUid();
		return RewardLogic::receiveByArrRid( $uid, $ridArr );
		
	}
	
	public function getGiftByCode($code)
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$ret = GiftCodeLogic::getGiftByCode($uid, $code);
		
		return $ret;
	}
	
	
	public function sendTopupFeedBack($uid, $arrReward)
	{
		$guid = RPCContext::getInstance()->getUid();
		if ( $guid > 0 && $uid != $guid)
		{
			throw new InterException('uid:%d != guid:%d', $uid, $guid);
		}
		Logger::info('sendTopupFeedBack. uid:%d, reward:%s', $uid, $arrReward);
		
		EnReward::sendReward($uid, RewardSource::TOP_UP_FEED_BACK, $arrReward);
	}
	
	public function sendSystemReward($uid, $arrReward, $title, $msg)
	{
		$guid = RPCContext::getInstance()->getUid();
		if ( $guid > 0 && $uid != $guid)
		{
			throw new InterException('uid:%d != guid:%d', $uid, $guid);
		}
		$arrReward[RewardDef::TITLE] = $title;
		$arrReward[RewardDef::MSG] = $msg;
		Logger::info('sendSystemReward. uid:%d, reward:%s', $uid, $arrReward);
		
		$arrSysRewardInfo = EnUser::getExtraInfo(UserExtraDef::SYS_REWARD_INFO, $uid);
		if ($arrSysRewardInfo === FALSE 
			|| !isset($arrSysRewardInfo['refresh_time'])
			|| !isset($arrSysRewardInfo['info'])
			|| !Util::isSameDay($arrSysRewardInfo['refresh_time'])) 
		{
			$arrSysRewardInfo['refresh_time'] = Util::getTime();
			$arrSysRewardInfo['info'] = array();
		}
		
		$curNum = 0;
		if (isset($arrSysRewardInfo['info'][$title])) 
		{
			$curNum = intval($arrSysRewardInfo['info'][$title]);
		}
		$limit = intval(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_MAX_SYS_REWARD_EVERYDAY]);
		if ($curNum >= $limit) 
		{
			return 'limit';
		}
	
		EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $arrReward);
		
		$arrSysRewardInfo['refresh_time'] = Util::getTime();
		$arrSysRewardInfo['info'][$title] = $curNum + 1;
		EnUser::setExtraInfo(UserExtraDef::SYS_REWARD_INFO, $arrSysRewardInfo, $uid);
		
		return 'ok';
	}
	
	public function getReceivedList($offset = 0, $limit = RewardDef::RECEIVED_NUM)
	{
		$offset = intval($offset);
		$limit = intval($limit);
		
		$uid = RPCContext::getInstance()->getUid();
		
		$ret = RewardLogic::getReceivedList($uid, $offset, $limit);
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */