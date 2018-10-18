<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendLuckyReward.php 127587 2014-08-18 03:54:27Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendLuckyReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-08-18 03:54:27 +0000 (Mon, 18 Aug 2014) $
 * @version $Revision: 127587 $
 * @brief 
 *  
 **/
//文件格式position uid
/**
 * 此脚本在服未发幸运排名奖励的时候补发
 */
class SendLuckyReward extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 SendLuckyReward filename check|fix\n";
		
		if(empty($arrOption[0]))
		{
			echo "No input file!\n";
            return;
		}
		$fileName = $arrOption[0];
		
		$fix = false;
		if(isset($arrOption[1]) &&  $arrOption[1] == 'fix')
		{
			$fix = true;
		}

		$file = fopen("$fileName", 'r');
		echo "read $fileName\n";

		$beginDate = 20140813;
		$rewardTime = strtotime('2014-08-13 22:00:00');
		$ret = ArenaLuckyDao::select($beginDate, array('va_lucky'));
		$luckyList = Util::arrayIndex($ret['va_lucky'], 'position');
		
		while (!feof($file))
		{
			$line = fgets($file);
			if (empty($line))
			{
				break;
			}
			
			$info = explode(" ", $line);
			$pos = intval($info[0]);
			$uid = intval($info[1]);
			
			if (isset($luckyList[$pos]['uid'])) 
			{
				Logger::warning('uid:%d already reward, ignore', $uid);
				continue;
			}
			if(self::isReward($uid, $rewardTime))
			{
				Logger::warning('uid:%d already reward, ignore', $uid);
				continue;
			}
			if(self::isfix($uid, $rewardTime))
			{
				Logger::warning('uid:%d already fix, ignore', $uid);
				continue;
			}
			$data = self::getReward($uid, $rewardTime);
			if (isset($data[RewardDef::SQL_VA_REWARD])
			&& $pos != $data[RewardDef::SQL_VA_REWARD][RewardDef::EXT_DATA]['rank'])
			{
				Logger::warning('uid:%d pos:%d is not equal rank:%d', $uid, $pos, $data[RewardDef::SQL_VA_REWARD][RewardDef::EXT_DATA]['rank']);
				continue;
			}
			$arrUserInfo = EnUser::getArrUser(array($uid), array('uname','level'));
			if (empty($arrUserInfo))
			{
				Logger::fatal('fail to get user %d', $uid);
				continue;
			}
			
			$luckyList[$pos]['uid'] = $uid;
			if (ArenaLogic::isNpc($uid))
			{
				$luckyList[$pos]['utid'] = ArenaLogic::getNpcUtid($uid);
				$luckyList[$pos]['uname'] = ArenaLogic::getNpcName($uid);
			}
			else
			{
				$user = EnUser::getUserObj($uid);
				$luckyList[$pos]['utid'] = $user->getUtid();
				$luckyList[$pos]['uname'] = $user->getUname();
				$reward = array(
						RewardType::GOLD => $luckyList[$pos]['gold'],
						RewardDef::EXT_DATA => array('rank' => $pos),
						'title' => "竞技场幸运排名",
						'msg' => "8月13日竞技场幸运排名奖励发放失败补偿",
				);
				Logger::info('reward for user:%d, reward:%s', $uid, $reward);
				if ($fix)
				{
					EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $reward);
				}
			}
		}
		
		if ($fix) 
		{
			ArenaLuckyDao::update($beginDate, array('va_lucky' => array_values($luckyList)));
		}
		
		fclose($file);
		echo "ok\n";
	}
	
	public static function isReward($uid, $rewardTime)
	{
		$data = new CData();
		$ret = $data->select(array(RewardDef::SQL_RID))
					->from(RewardDef::SQL_TABLE)
					->where(RewardDef::SQL_UID , '=', $uid)
					->where(RewardDef::SQL_SEND_TIME, 'between', array($rewardTime, $rewardTime + SECONDS_OF_DAY))
					->where(RewardDef::SQL_SOURCE , '=', RewardSource::ARENA_LUCKY)
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
	
	public static function getReward($uid, $rewardTime)
	{
		$data = new CData();
		$ret = $data->select(array(RewardDef::SQL_VA_REWARD))
					->from(RewardDef::SQL_TABLE)
					->where(RewardDef::SQL_UID , '=', $uid)
					->where(RewardDef::SQL_SEND_TIME, 'between', array($rewardTime, $rewardTime + SECONDS_OF_DAY))
					->where(RewardDef::SQL_SOURCE , '=', RewardSource::ARENA_RANK)
					->query();
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */