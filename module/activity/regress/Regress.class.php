<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Regress.class.php 133646 2014-09-22 07:03:57Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/regress/Regress.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-09-22 07:03:57 +0000 (Mon, 22 Sep 2014) $
 * @version $Revision: 133646 $
 * @brief 
 *  
 **/
class Regress
{
	public static function oldUserBonus()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (empty($uid))
		{
			Logger::debug("user is not login, ignore.");
			return;
		}
		//活动是否开启
		if(EnActivity::isOpen(ActivityName::REGRESS) == false)
		{		
			Logger::debug('activity is not open');
			return ;
		}
		//取活动配置
		$conf = EnActivity::getConfByName(ActivityName::REGRESS);
		$beginTime = $conf['start_time'];
		$data = $conf['data'][1];
		
		//检查玩家是否满足奖励要求
		$user = EnUser::getUserObj($uid);
		$createTime = $user->getCreateTime();
		$lastLoginTime = $user->getLastLoginTime();
		
		//检查创建时间
		$needCreateTime = strtotime($data['createtime']);
		if ($createTime > $needCreateTime)
		{
			Logger::info('create time:%d no reward', $createTime);
			return;
		}
		
		//检查用户发过奖没有
		if (self::isReward($uid, $beginTime))
		{
			Logger::debug('reward already');
			return ;
		}
		
		//检查用户未登陆天数
		if ($beginTime - $lastLoginTime >= $data['offline'] * SECONDS_OF_DAY) 
		{
			$reward = $data['offreward'];
			$rewardSource = RewardSource::REGRESS_ELITE;
		}
		else 
		{
			$reward = $data['reward'];
			$rewardSource = RewardSource::REGRESS_INSISTENT;
		}
		
		$reward = RewardUtil::format3DtoCenter($reward, $uid);
		
		Logger::info('send old user reward. uid:%d, reward:%s', $uid, $reward);
		EnReward::sendReward($uid, $rewardSource, $reward);
	}
	
	public static function isReward($uid, $beginTime)
	{
		//检查是否已经给过奖励
		$data = new CData();
		$arrRet = $data->select(array(RewardDef::SQL_RID, RewardDef::SQL_VA_REWARD))
					   ->from(RewardDef::SQL_TABLE)
					   ->where(RewardDef::SQL_UID, '=', $uid)
					   ->where(RewardDef::SQL_SEND_TIME, '>', $beginTime)
					   ->where(RewardDef::SQL_SOURCE, 'in', array(RewardSource::REGRESS_ELITE, RewardSource::REGRESS_INSISTENT))
					   ->query();

		return empty($arrRet)? false : true;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */