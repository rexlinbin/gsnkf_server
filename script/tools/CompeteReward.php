<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteReward.php 99705 2014-04-14 03:52:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/CompeteReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-04-14 03:52:03 +0000 (Mon, 14 Apr 2014) $
 * @version $Revision: 99705 $
 * @brief 
 *  
 **/
//抓取用户积分的脚本
//for i in `seq 13 15`; do echo $i;grep 'UPDATE pirate40010003.t_compete' /home/pirate/dataproxy/log/201403$i/dataproxy.log.*  |grep point    > log_$i ; done
//cat log_13  log_13  log_15  >  log_user
//sed "s/.*point = \([0-9]*\),.*uid = '\([0-9]*\)'.*/\2 \1/" log_user  > uid_point_all
//awk '{ map[$1]=$2; }END{ for(uid in map){ printf("%d %d\n", uid, map[uid]); }  }'  uid_point_all  > uid_point
//格式uid point
/**
 * 此脚本在服未发奖且用户积分已经被重置的情况下使用。
 * 先用上面的脚本抓取日志里面所有的用户积分数据，排序后作为本脚本的输入，给所有用户发相应的比武奖励。
 */
class CompeteReward extends BaseScript
{
	protected function executeScript($arrOption)
	{
		if(empty($arrOption[0]))
		{
			echo "No input file!\n";
            return;
		}
		$fileName = $arrOption[0];

		$file = fopen("$fileName", 'r');
		echo "read $fileName\n";

		$rank = 0;
		$rewardConf = btstore_get()->COMPETE_REWARD;
		$rewardTime = strtotime('2014-01-15 23:00:00');
		
		//收集所有需要通知的uid，然后一起通知
		$data = new CData();
		$arrNotifyUid = array();
		MailConf::$NO_CALLBACK = true;
		RewardCfg::$NO_CALLBACK = true;
		
		while (!feof($file))
		{
			$line = fgets($file);
			if (empty($line))
			{
				break;
			}
			
			$rank++;
			$info = explode(" ", $line);
			$uid = intval($info[0]);
			if(self::isRobotUid($uid))
			{
				Logger::trace('uid:%d is NPC, ignore.', $uid);
				continue;
			}
			if(self::isReward($uid, $rewardTime))
			{
				Logger::warning('uid:%d already reward, ignore', $uid);
				continue;
			}
			$arrUserInfo = EnUser::getArrUser(array($uid), array('uname','level'));
			if (empty($arrUserInfo))
			{
				Logger::fatal('fail to get user %d', $uid);
				continue;
			}
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
			Logger::info('generate reward for user:%d, rank:%d, uname:%s, reward:%s', $uid, $rank, $uname, $reward);
	
			//发邮件通知用户
			MailTemplate::sendCompeteRank($uid, $rank, $reward[RewardType::SOUL],
			$reward[RewardType::SILVER], $reward[RewardType::GOLD], $reward[RewardType::ARR_ITEM_TPL]);
	
			//发奖励到奖励中心
			$rid = EnReward::sendReward($uid, RewardSource::COMPETE_RANK, $reward);
			Logger::trace('user:%d reward id is %d in reward center', $uid, $rid);
				
			$arrNotifyUid[] = $uid;
		}
		
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::MAIL_CALLBACK, array() );
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::REWARD_NEW, array() );
		
		fclose($file);
		echo "ok\n";
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