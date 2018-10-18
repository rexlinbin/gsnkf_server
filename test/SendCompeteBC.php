<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendCompeteBC.php 135714 2014-10-11 03:20:27Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendCompeteBC.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-11 03:20:27 +0000 (Sat, 11 Oct 2014) $
 * @version $Revision: 135714 $
 * @brief 
 *  
 **/

/**
 * 给所有比武用户发补偿
 */
class SendCompeteBC extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$usage = "usage::btscript game001 SendCompeteBC.php check|fix\n";
		
		$createTime = strtotime('2014-09-05 24:00:00');
		$rewardTime = strtotime('2014-09-09 16:00:00');
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
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
					if (self::isFix($uid, $rewardTime)) 
					{
						Logger::info('uid:%d already fix, ignore', $uid);
						$uid = next($arrUid);
						continue;
					}
					$level = $arrUser[$uid]['level'];
					$reward = array(
							RewardType::GOLD => 100,
							'title' => "比武补偿",
							'msg' => "比武积分未重置补偿",
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
		printf("The acount of users with compensation is %d\n", $num);
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