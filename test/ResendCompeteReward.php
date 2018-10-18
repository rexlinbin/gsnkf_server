<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ResendCompeteReward.php 135715 2014-10-11 03:20:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/ResendCompeteReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-11 03:20:43 +0000 (Sat, 11 Oct 2014) $
 * @version $Revision: 135715 $
 * @brief 
 *  
 **/


class ResendCompeteReward extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$usage = "usage::btscript game001 ResendCompeteReward.php check|fix\n";
		
		$rewardTime = strtotime('2014-09-27 23:00:00');
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$i = 0;
		$num = 0;
		$rank = 0;
		$count = CData::MAX_FETCH_SIZE;
		$order = CompeteDef::COMPETE_POINT;
		$arrfield = array(CompeteDef::REWARD_TIME);
		$rewardConf = btstore_get()->COMPETE_REWARD;
		$maxRank = 200;
		
		//收集所有需要通知的uid，然后一起通知
		$arrNotifyUid = array();
		MailConf::$NO_CALLBACK = true;
		RewardCfg::$NO_CALLBACK = true;
		
		$arrUserRank = array();
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$arrRankInfo = CompeteDao::getRankList($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE, $order, $arrfield);
			$count = count($arrRankInfo);
			++$i;
			//没有数据直接退出
			if ($count == 0)
			{
				break;
			}
			//拉取用户等级
			$arrUid = array_keys($arrRankInfo);
			$arrUserInfo = EnUser::getArrUser($arrUid, array('level'));
			//连续修改多少个后休眠
			$sleepCount = 0;
			$uid = current($arrUid);
			while ($uid != false)
			{
				++$rank;
				try
				{
					if( $rank > CompeteConf::REWARD_NUM )
					{
						Logger::trace('uid:%d, rank:%d, ignore', $uid, $rank);
						CompeteLogic::init($uid, $arrRankInfo[$uid][$order], Util::getTime());
						$uid = next($arrUid);
						continue;
					}
					if( CompeteLogic::isRobotUid($uid) )
					{
						Logger::trace('uid:%d is NPC, ignore.', $uid);
						$uid = next($arrUid);
						continue;
					}
					if (!isset($arrUserInfo[$uid]))
					{
						Logger::fatal('fail to get user %d', $uid);
						$uid = next($arrUid);
						continue;
					}
					if (self::isReward($uid, $rewardTime))
					{
						Logger::info('uid:%d already reward, ignore', $uid);
						$uid = next($arrUid);
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
					$level = $arrUserInfo[$uid]['level'];
					$reward = array(
							RewardType::SILVER => $conf[CompeteDef::COMPETE_REWARD_SILVER] * $level,
							RewardType::SOUL => $conf[CompeteDef::COMPETE_REWARD_SOUL] * $level,
							RewardType::GOLD => $conf[CompeteDef::COMPETE_REWARD_GOLD],
							RewardType::ARR_ITEM_TPL => $conf[CompeteDef::COMPETE_REWARD_ITEM]->toArray(),
							RewardType::HORNOR => $conf[CompeteDef::COMPETE_REWARD_HONOR],
							RewardDef::EXT_DATA => array( 'rank' => $rank ),
					);
					Logger::info('generate reward for user:%d, reward:%s', $uid, $reward);
					if ($fix)
					{
						//发邮件通知用户
						MailTemplate::sendCompeteRank($uid, $rank, $reward[RewardType::SOUL],
						$reward[RewardType::SILVER], $reward[RewardType::GOLD], $reward[RewardType::HORNOR],$reward[RewardType::ARR_ITEM_TPL]);
						//发奖励到奖励中心
						$rid = EnReward::sendReward($uid, RewardSource::COMPETE_RANK, $reward);
						//初始化用户信息
						CompeteLogic::init($uid, $arrRankInfo[$uid][$order], Util::getTime());
						//成就优化
						if ($rank <= $maxRank)
						{
							$arrUserRank[$uid] = $rank;
						}
						$arrNotifyUid[] = $uid;
					}
		
					if (++$sleepCount == CompeteConf::NUM_OF_REWARD_PER)
					{
						usleep(CompeteConf::SLEEP_MTIME);
						$sleepCount = 0;
					}
					$uid = next($arrUid);
					$num++;
				}
				catch (Exception $e )
				{
					Logger::fatal('fail to generateReward for uid:%d, $reward:%s', $uid, $reward);
					$uid = next($arrUid);
				}
			}
		}
		
		if ($fix)
		{
			RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::MAIL_CALLBACK, array() );
			RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::REWARD_NEW, array() );
			
			$batchNum = 1000;
			$slice = array();
			foreach($arrUserRank as $uid => $rank) {
				$slice[$uid] = $rank;
				if(sizeof($slice) >= $batchNum) {
					RPCContext::getInstance()->executeTask($uid,
					'achieve.updateTypeArrBySystem',
					array(AchieveDef::COMPETE_RANK, $slice));
					$slice = array();
				}
			}
			if(sizeof($slice) > 0) {
				RPCContext::getInstance()->executeTask($uid,
				'achieve.updateTypeArrBySystem',
				array(AchieveDef::COMPETE_RANK, $slice));
			}
			$arrField = array(CompeteDef::COMPETE_POINT => 1000);
			CompeteDao::updateAll($arrField);
		}
		printf("The acount of users without reward is %d\n", $num);
	}
	
	public static function isReward($uid, $rewardTime)
	{
		$data = new CData();
		$ret = $data->select(array(RewardDef::SQL_RID))
					->from(RewardDef::SQL_TABLE)
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
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */