<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendComRewardByUser.php 100286 2014-04-15 07:22:29Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendComRewardByUser.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-04-15 07:22:29 +0000 (Tue, 15 Apr 2014) $
 * @version $Revision: 100286 $
 * @brief 
 *  
 **/
/**
 * 此脚本在服有N个人没有发奖的情况下使用。
 * 先获得用户的积分和相应排名，然后发放相应的比武排名奖励。
 * 格式game uid rank
 * 在logic机器上执行：
 * awk '{printf("/home/pirate/programs/php/bin/php  /home/pirate/rpcfw/lib/ScriptRunner.php -g game%s -d pirate%s -f SendComRewardByUser.php  check %s  %s\n", $1, $1, $2, $3) }' users
 */
class SendComRewardByUser extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 SendComRewardByUser.php check|fix uid rank\n";
		
		$rewardTime = strtotime('2014-04-12 23:00:00');
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$uid = $arrOption[1];
		if(self::isRobotUid($uid))
		{
			Logger::warning('uid:%d is NPC', $uid);
			return ;
		}
		$arrUserInfo = EnUser::getArrUser(array($uid), array('uname','level'));
		if (empty($arrUserInfo))
		{
			Logger::fatal('fail to get user %d', $uid);
			return ;
		}
		if(self::isReward($uid, $rewardTime))
		{
			echo "user is reward\n";
			Logger::warning('uid:%d already reward', $uid);
			return ;
		}
		
		echo "user is not reward\n";
		$rank = $arrOption[2];
		$rewardConf = btstore_get()->COMPETE_REWARD;
		$conf = array();
		foreach ($rewardConf as $key => $value)
		{
			if ($rank >= $value[CompeteDef::COMPETE_REWARD_MIN]
			&& $rank <= $value[CompeteDef::COMPETE_REWARD_MAX])
			{
				$conf = $value;
				break;
			}
		}
		$uname = $arrUserInfo[$uid]['uname'];
		$level = $arrUserInfo[$uid]['level'];
		$reward = array(
				RewardType::SILVER => $conf[CompeteDef::COMPETE_REWARD_SILVER] * $level,
				RewardType::SOUL => $conf[CompeteDef::COMPETE_REWARD_SOUL] * $level,
				RewardType::GOLD => $conf[CompeteDef::COMPETE_REWARD_GOLD],
				RewardType::ARR_ITEM_TPL => $conf[CompeteDef::COMPETE_REWARD_ITEM]->toArray(),
				RewardDef::EXT_DATA => array( 'rank' => $rank ),
		);
		Logger::info('send reward for user:%d, rank:%d, uname:%s, reward:%s', $uid, $rank, $uname, $reward);
		
		if ($fix) 
		{
			//发奖励到奖励中心
			EnReward::sendReward($uid, RewardSource::COMPETE_RANK, $reward);
		}
	}
	
	public static function isReward($uid, $rewardTime)
	{
		$data = new CData();
		$ret = $data->select(array(RewardDef::SQL_RID))
					->from( RewardDef::SQL_TABLE )
					->where(RewardDef::SQL_UID , '=', $uid)
					->where(RewardDef::SQL_SEND_TIME, '>', $rewardTime)
					->where(RewardDef::SQL_SOURCE , '=', RewardSource::COMPETE_RANK)
					->query();
		if (empty($ret))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
	
	public static function isRobotUid($uid)
	{
		return $uid >= SPECIAL_UID::MIN_ROBOT_UID && $uid <= SPECIAL_UID::MAX_ROBOT_UID;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */