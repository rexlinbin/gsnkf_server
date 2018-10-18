<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendCompeteReward.php 99673 2014-04-14 02:56:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendCompeteReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-04-14 02:56:41 +0000 (Mon, 14 Apr 2014) $
 * @version $Revision: 99673 $
 * @brief 
 *  
 **/

/**
 * 此脚本在某服发奖未发完的情况下使用。
 * 它遍历了所有的比武用户，找出没有发奖的用户，补发的是最后一档的奖励。
 */
class SendCompeteReward extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$usage = "usage::btscript game001 SendCompeteReward.php check|fix\n";
		
		$rewardTime = strtotime('2014-03-29 23:00:00');
		$createTime = strtotime('2014-03-28 24:00:00');
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$rewardConf = btstore_get()->COMPETE_REWARD->toArray();
		$conf = $rewardConf[count($rewardConf)];
		
		$i = 0;
		$num = 0;
		$uid = 0;
		$count = CData::MAX_FETCH_SIZE;
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$arrUid = self::getArrUser($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			$count = count($arrUid);
			$i++;

			$arrUser = EnUser::getArrUser($arrUid, array('level', 'create_time'));
			$uid = current($arrUid);
			while ($uid != false)
			{
				try
				{
					if(self::isRobotUid($uid))
					{
						Logger::info('uid:%d is NPC, ignore.', $uid);
						$uid = next($arrUid);
						continue;
					}
					if (!isset($arrUser[$uid]))
					{
						Logger::warning('fail to get user %d', $uid);
						$uid = next($arrUid);
						continue;
					}
					$time = $arrUser[$uid]['create_time'];
					if ($time > $createTime) 
					{
						Logger::info('user:%d is new, ignore', $uid);
						$uid = next($arrUid);
						continue;
					}
					if (self::isReward($uid, $rewardTime)) 
					{
						Logger::info('uid:%d already reward, ignore', $uid);
						$uid = next($arrUid);
						continue;
					}
					if (self::isFix($uid, $rewardTime)) 
					{
						Logger::info('uid:%d already fix, ignore', $uid);
						$uid = next($arrUid);
						continue;
					}
					$level = $arrUser[$uid]['level'];
					$reward = array(
							RewardType::SILVER => $conf[CompeteDef::COMPETE_REWARD_SILVER] * $level,
							RewardType::SOUL => $conf[CompeteDef::COMPETE_REWARD_SOUL] * $level,
							RewardType::GOLD => $conf[CompeteDef::COMPETE_REWARD_GOLD],
							RewardType::ARR_ITEM_TPL => $conf[CompeteDef::COMPETE_REWARD_ITEM],
							'title' => "比武奖励",
							'msg' => "比武奖励发放失败补偿",
					);
					Logger::info('reward for user:%d, reward:%s', $uid, $reward);
					if ($fix)
					{
						EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $reward);
					}
					$num++;
					$uid = next($arrUid);
				}
				catch( Exception $e )
				{
					Logger::fatal('failed:%s', $e->getMessage() );
				}
			}
		}
		printf("The acount of users without reward is %d\n", $num);
	}

	public static function isRobotUid($uid)
	{
		return $uid >= SPECIAL_UID::MIN_ROBOT_UID && $uid <= SPECIAL_UID::MAX_ROBOT_UID;
	}
	
	public static function getArrUser($offset, $limit)
	{
		$data = new CData();
		$arrRet = $data->select(array(CompeteDef::COMPETE_UID))
					   ->from(CompeteDef::COMPETE_TABLE)
					   ->where(array(CompeteDef::COMPETE_UID, '>', SPECIAL_UID::MAX_ROBOT_UID))
					   ->orderBy(CompeteDef::COMPETE_UID, true)
					   ->limit($offset, $limit)
					   ->query();
		return 	Util::arrayExtract($arrRet, CompeteDef::COMPETE_UID);
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
	
	public static function isfix($uid, $rewardTime)
	{
		$data = new CData();
		$ret = $data->select(array(RewardDef::SQL_RID))
					->from( RewardDef::SQL_TABLE )
					->where(RewardDef::SQL_UID , '=', $uid)
					->where(RewardDef::SQL_SEND_TIME, '>', $rewardTime)
					->where(RewardDef::SQL_SOURCE , '=', RewardSource::SYSTEM_GENERAL)
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
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */