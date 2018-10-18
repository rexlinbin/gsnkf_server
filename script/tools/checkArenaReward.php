<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkArenaReward.php 119089 2014-07-07 11:59:17Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkArenaReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-07-07 11:59:17 +0000 (Mon, 07 Jul 2014) $
 * @version $Revision: 119089 $
 * @brief 
 *  
 **/
class CheckArenaReward extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (empty($arrOption[0]) || $arrOption[0] == 'help')
		{
			$this->usage();
			return;
		}
		
		$option = $arrOption[0];
		if ($option == 'check')
		{
			$fix = false;
		}
		elseif ($option == 'fix')
		{
			//暂时只修幸运奖励, 排行奖励补发双倍
			$fix = true; 
		}
		else
		{
			echo "invalid operation!\n";
			$this->usage();
			return;
		}
		
		//判断排行奖励和幸运奖励是否发成功的标志位
		$halfHour = 1800;
		$rankFlag = true;
		$luckyFlag = true;
		$data = new CData ();
		
		//当前时间
		$curTime = Util::getTime();
		echo "current time is " . $curTime . ".\n";
		$curHour = strftime("%H:%M:%S", $curTime);
		if ($curHour <= ArenaDateConf::LOCK_START_TIME)
		{
			$lastDate = intval(strftime("%Y%m%d", strtotime("- 1 day", $curTime)));
			$nextDate = intval(strftime("%Y%m%d", $curTime));
		}
		elseif ($curHour >= ArenaDateConf::LOCK_END_TIME)
		{
			$lastDate = intval(strftime("%Y%m%d", $curTime));
			$nextDate = intval(strftime("%Y%m%d", strtotime("+ 1 day", $curTime)));
		}
		echo "last reward date is " . $lastDate . ".\n";
		echo "next reward date is " . $nextDate . ".\n";
		$rewardTime = strtotime($lastDate . " " . ArenaDateConf::LOCK_START_TIME);
		echo "last reward time is " . $rewardTime . ".\n";
		
		// 检查history表中的发奖时间
		$ret = ArenaDao::getNewHis(array('update_time'));
		$updateTime = $ret['update_time'];
		echo "history update time is " . $updateTime . ".\n";
		
		// 检查两个时间是否相同, 不同则说明肯定没有发排名奖励
		if ($updateTime - $rewardTime > $halfHour)
		{
			$rankFlag = false;
		}
		
		// 检查lucky表的数据是否更新，因此判断有没有发幸运奖励
		$invalidLucky = array();
		$arrLucky = ArenaLuckyDao::select($nextDate, array('va_lucky'));
		if (empty($arrLucky)) 
		{
			$luckyFlag = false;
			
			if ($fix) 
			{
				//产生下轮的幸运排名
				$arrPos = array();
				foreach (ArenaConf::$LUCKY_POSITION_CONFIG as $cfg)
				{
					$circleNum = 1000;
					while($circleNum-- > 0)
					{
						$pos = rand($cfg[0], $cfg[1]);
						if (!in_array($pos, $arrPos))
						{
							$arrPos[] = $pos;
							$arrLucky[] = array('position' => $pos, 'gold' => $cfg[2]);
							break;
						}
					}
				}
				$arrField = array('begin_date' => $nextDate, 'va_lucky' => $arrLucky);
				$arrRet = ArenaLuckyDao::insert($arrField);
				//设置到memcache
				ArenaRound::setCurRound();
				ArenaRound::setCurRoundDate();
			}
		}
		else 
		{
			//否则，检查上一轮的幸运奖励是否发给用户了
			$arrLucky = ArenaLuckyDao::select($lastDate, array('va_lucky'));
			foreach ($arrLucky['va_lucky'] as $lucky)
			{
				$pos = $lucky['position'];
				$info = ArenaDao::getByPos($pos, array('uid', 'reward_time'));
				if (empty($info)) 
				{
					continue;
				}
				if ($info['reward_time'] == 0)
				{
					continue;
				}
				if (ArenaLogic::isNpc($info['uid'])) 
				{
					continue;
				}
				if (!isset($lucky['uid'])) 
				{
					$luckyFlag = false;
					$invalidLucky[$pos] = $info['uid'];
				}
				
				// 发到奖励中心了吗？
				$ret = $data->select(array(RewardDef::SQL_SEND_TIME))
							->from(RewardDef::SQL_TABLE)
							->where(array(RewardDef::SQL_UID, '=', $info['uid']))
							->where(array(RewardDef::SQL_SEND_TIME, '>=', $rewardTime))
							->where(array(RewardDef::SQL_SOURCE, '=', RewardSource::ARENA_LUCKY))
							->query();
				if (empty($ret))
				{
					$luckyFlag = false;
					$invalidLucky[$pos] = $info['uid'];
				}
				
			}
		}
		
		// 检查arena的发奖时间是否更新，判断给用户的排名奖励发了吗
		$invalidRank = array();
		$count = ArenaDao::getCount();
		for ($i = 1; $i <= $count; $i++)
		{
			$info = ArenaDao::getByPos($i, array('uid', 'reward_time'));
			if ($info['reward_time'] == 0)
			{
				continue;
			}
			if (ArenaLogic::isNpc($info['uid']))
			{
				continue;
			}
			if ($info['reward_time'] < $rewardTime)
			{
				$rankFlag = false;
				$invalidRank[$i] = $info['uid'];
				break;
			}
			
			// 发到奖励中心了吗
			$ret = $data->select(array(RewardDef::SQL_SEND_TIME))
						->from(RewardDef::SQL_TABLE)
						->where(array(RewardDef::SQL_UID, '=', $info['uid']))
						->where(array(RewardDef::SQL_SEND_TIME, '>=', $rewardTime))
						->where(array(RewardDef::SQL_SOURCE, '=', RewardSource::ARENA_RANK))
						->query();
			if (empty($ret))
			{
				$rankFlag = false;
				$invalidRank[$i] = $info['uid'];
				break;
			}
		}
		
		//根据排名奖励是否发放成功来确定是否补发
		if ($rankFlag)
		{
			echo "send rank reward sucessful.\n";
		}
		else 
		{
			echo "send rank reward failed.\n";
			echo "one of error user data is:\n";
			print_r($invalidRank);
			
			//暂时不修排名奖励
			if ($fix) 
			{
				$lastRound = ArenaRound::getCurRound() - 1;
				$pos1 = 1;
				$pos2 = ArenaConf::NUM_OF_QUERY;
				$arenaReward = btstore_get()->ARENA_REWARD;		
				$total = count($arenaReward);
				if ($pos2 > $total)
				{
					$pos2 = $total;
				}
				$lessRewardTime = Util::getTime() - 3600 * 13;
				$arrNotifyUid = array();
				MailConf::$NO_CALLBACK = true;
				
				while(true)
				{
					$arrPosInfo = ArenaDao::getPosRange4Reward($pos1, $pos2, $lessRewardTime, array('uid', 'position'));			
					if (empty($arrPosInfo))
					{
						break;
					}
		            $arrUid = Util::arrayExtract($arrPosInfo, 'uid');
		            $arrUserInfo = EnUser::getArrUser($arrUid, array('level'));					
		            $posInfo = current($arrPosInfo);
		            $sleepCount = 0;
		            while ($posInfo !== false)
		            {
		            	try
						{
							$uid = $posInfo['uid'];
							Util::kickOffUser($uid);
							if ( !isset($arrUserInfo[$uid]))
							{
								$posInfo = next($arrPosInfo);
								continue;
							}
							//读配置表
							$conf = btstore_get()->ARENA_REWARD[$posInfo['position']];
							$reward = array(
									RewardType::SOUL => $conf['soul'] * $arrUserInfo[$uid]['level'],
									RewardType::SILVER => $conf['silver'] * $arrUserInfo[$uid]['level'],
									RewardType::ARR_ITEM_TPL => $conf['items']->toArray(),
									RewardDef::EXT_DATA => array( 'rank' => $posInfo['position'] ),
							);
							
							//发邮件发奖励
							MailTemplate::sendArenaAward($uid, $lastRound, $posInfo['position'], $reward[RewardType::SOUL],
							$reward[RewardType::SILVER], $reward[RewardType::ARR_ITEM_TPL]);
							EnReward::sendReward($uid, RewardSource::ARENA_RANK, $reward);					
							$reward['items'] = $reward[RewardType::ARR_ITEM_TPL];
							unset($reward[RewardType::ARR_ITEM_TPL]);
							unset($reward[RewardDef::EXT_DATA]);
							//更新数据库
							ArenaDao::update($uid, array('reward_time' => $rewardTime, 'va_reward' => $reward));
						
							$arrNotifyUid[] = $uid;
							
							try 
							{
								//更新用户历史排名
								ArenaDao::updateHis($uid, $posInfo['position'], $rewardTime);
							}
							catch (Exception $e)
							{
								Logger::warning('fail to update arena history');
							}
							
							if (++$sleepCount == ArenaConf::NUM_OF_REWARD_PER)
							{
								usleep(ArenaConf::SLEEP_MTIME);
								$sleepCount = 0;
							}
							$posInfo = next($arrPosInfo);
						}
						catch (Exception $e )
						{
							Logger::fatal('fail to generateReward for uid:%d', $uid);
							$posInfo = next($arrPosInfo);
						}
		            }
		
		            //说明已经处理完了
		            if ($pos2 >= $total)
		            {
		            	break;
		            }
					
		            //循环取出接下来的100个用户进行发奖
					$pos1 = $pos2+1;
					$pos2 += ArenaConf::NUM_OF_QUERY;
					if ($pos2 > $total)
					{
						$pos2 = $total;
					}
				}
			}
		}
		
		if ($luckyFlag) 
		{
			echo "send lucky reward sucessful.\n";
		}
		else
		{
			echo "send lucky reward failed.\n";
			echo "error user data is:\n";
			print_r($invalidLucky);
			
			if ($fix)
			{
				$arrLucky = ArenaLuckyDao::select($lastDate, array('va_lucky'));		
				$lastRound = ArenaRound::getCurRound() - 1;
				$arrUpdateLucky = $arrLucky['va_lucky'];
				foreach ($arrLucky['va_lucky'] as $key => $lucky)
				{
					//已有uid，说明已经发过奖了
					if (isset($lucky['uid']))
					{
						continue;
					}
					$pos = $lucky['position'];
					$posInfo = ArenaDao::getByPos($pos, array('uid'));
					if (empty($posInfo))
					{
						continue;
					}
					$uid = $posInfo['uid'];
					Util::kickOffUser($uid);
					$user = EnUser::getUserObj($uid);
					$arrUpdateLucky[$key]['uid'] = $uid;
					$arrUpdateLucky[$key]['utid'] = $user->getUtid();
					$arrUpdateLucky[$key]['uname'] = $user->getUname();
					//发邮件和奖励
					$reward = array(
							RewardType::GOLD => $lucky['gold']
					);
					MailTemplate::sendArenaLuckyAward($uid, $lastRound, $pos, $reward[RewardType::GOLD]);
					EnReward::sendReward($uid, RewardSource::ARENA_LUCKY, $reward);
					ArenaLuckyDao::update($lastDate, array('va_lucky' => $arrUpdateLucky));
					echo "fix arena user " . $uid . "\n";
				}
			}
		}
		
		echo "ok\n";
	}
	
	private function usage()
	{
	
		echo "usage: btscript game001 checkArenaReward.php check|fix\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */