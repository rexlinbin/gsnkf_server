<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaRound.class.php 187298 2015-07-29 05:41:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/ArenaRound.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2015-07-29 05:41:25 +0000 (Wed, 29 Jul 2015) $
 * @version $Revision: 187298 $
 * @brief 
 *  
 **/

class ArenaRound
{
	const ROUND_KEY = 'arena.round'; 
	const ROUND_DATE_KEY = 'arena.round_date';
	
	public static function getCurRound()
	{
		$round = McClient::get(self::ROUND_KEY);
		if ($round == null)
		{
			$round = self::setCurRound();
		}
		return $round;
	}
	
	public static function setCurRound()
	{
		$round = self::getCurRoundFromDb();
		if ('STORED' != McClient::set(self::ROUND_KEY, $round, ArenaConf::MEM_EXPIRED_TIME))
		{
			Logger::fatal('fail to set arena round to memcached');			
		}
		return $round;
	}
	
	public static function getCurRoundDate()
	{
		$roundDate = McClient::get(self::ROUND_DATE_KEY);
		if ($roundDate == null)
		{
			$roundDate = self::setCurRoundDate();
		}
		return $roundDate;
	}
	
	public static function setCurRoundDate()
	{
		$roundDate = self::getCurRoundDateFromDb();
		if (!empty($roundDate))
		{
			if ('STORED' != McClient::set(self::ROUND_DATE_KEY, $roundDate, ArenaConf::MEM_EXPIRED_TIME))
			{
				Logger::fatal('fail to set arena round date to memcached');
			}
		}
		return $roundDate;
	}
	
	
	const tblName = 't_arena_lucky';
	
	/**
	 * 得到第几轮
	 * 看有几条数据
	 */
	private static function getCurRoundFromDb()
	{
		$data = new CData();
		$ret = $data->selectCount()->from(self::tblName)->where('begin_date', '>', 0)->query();
		return $ret[0]['count'];
	}
	
	/**
	 * 得到当前轮的日期
	 * 看最后一条数据日期
	 */
	private static function getCurRoundDateFromDb()
	{
		$data = new CData();
		$arrField = array('begin_date');
		$arrRet = $data->select($arrField)->from(self::tblName)->where(1, '=', 1)
			->orderBy('begin_date', false)->limit(0, 1)->query();
		if (!empty($arrRet))
		{
			return $arrRet[0]['begin_date'];
		}
		return $arrRet;
	}
	
	/**
	 * 是否锁定
	 * 1.如果lucky表里的发奖时间为空就锁定
	 * 2.如果小于开奖时间，不锁定
	 * 3.如果大于开奖时间，且lucky表里面是今天日期，就只锁定发奖时间段，如果是小于今天日期，就锁定开奖之后到12点
	 */
	public static function isLock()
	{
		$beginDate = self::getCurRoundDate();
		if (empty($beginDate))
		{
			//开服前可能为空，返回true为了让产生幸运奖励的脚本能过
			Logger::warning('empty beginDate.');			
			return true;
		}
		
		//大于开奖时间，beginDate存的日期加上开奖时间点22:00
		//否则返回当天开奖时间
		$rewardTime = self::getRewardTime($beginDate);
		$curTime = Util::getTime();
		Logger::debug('reward time is %d, now is %d', $rewardTime, $curTime);
		//当前时间大于发奖时间
		if ($curTime >= $rewardTime )
		{
			return true;		
		}
		return false;
	}
	
	//返回给前端开奖时间戳， 
	//如果小于开奖时间，返回当天开奖的时间戳,
	//如果大于开奖时间内，返回rewardDate:22:00
	public static function getRewardTime($rewardDate = null)
	{
		if ($rewardDate == null)
		{
			$rewardDate = ArenaRound::getCurRoundDate();
		}
		//获得当前时间
		$curTime = Util::getTime();
		$beginDateForNow = strftime("%Y%m%d", $curTime);
		//计算发奖时间到达没有
		$diffDay = ArenaLuckyLogic::diffDate($beginDateForNow, $rewardDate);
		Logger::debug('there is %d days before reward:%d', $diffDay, $rewardDate);
		//刚好是这一轮结束的日子
		if ($diffDay % ArenaDateConf::LAST_DAYS == 0)
		{
			$curTimeStr = strftime("%H:%M:%S", $curTime);
			//在下一轮开始前
			if ($curTimeStr <= ArenaDateConf::LOCK_END_TIME)
			{
				return strtotime($beginDateForNow . " " . ArenaDateConf::LOCK_START_TIME);
			}
		}
		return strtotime($rewardDate . " " . ArenaDateConf::LOCK_START_TIME);
			
	}
	
	public static function arenaRankSnap($force = FALSE)
	{
		Logger::trace('ArenaRound::arenaRankSnap begin...');
		
		if (!self::isLock() && !$force)
		{
			Logger::fatal('ArenaRound::arenaRankSnap not the right time to take snapshot for arena');
			return;
		}
		Logger::info('ArenaRound::arenaRankSnap start to take snapshot for arena, curr round:%d, cur round date:%d', self::getCurRound(), self::getCurRoundDate());
		
		$pos1 = 1;
		$pos2 = ArenaConf::NUM_OF_QUERY;
		$total = ArenaConf::ARENA_RANK_SNAPSHOT_MAX_NUM;
		if ($pos2 > $total)
		{
			$pos2 = $total;
		}
		
		while(true)
		{
			$arrPosInfo = false;
			for ($i = 0; $i < 3; $i++)
			{
				try 
				{
					$arrPosInfo = ArenaDao::getByArrPos(range($pos1, $pos2), array('uid', 'position'));
				}
				catch (Exception $e)
				{
					Logger::warning('ArenaRound::arenaRankSnap get info from arena failed!');
					continue;
				}
				break;
			}
			if ($arrPosInfo === false)
			{
				Logger::fatal('ArenaRound::arenaRankSnap Fix Me! pos info is not generated successfully. now pos:%d', $pos1);
				$arrPosInfo = array();
			}
			Logger::trace('ArenaRound::arenaRankSnap there is %d user between %d and %d', count($arrPosInfo), $pos1, $pos2);
		
			//连续修改多少个后休眠
			$sleepCount = 0;
			$posInfo = current($arrPosInfo);
			while ($posInfo !== false)
			{
				try
				{
					$uid = $posInfo['uid'];
					$position = $posInfo['position'];
					ArenaDao::updateHis($uid, $position, Util::getTime());
			
			 		if (++$sleepCount == ArenaConf::NUM_OF_REWARD_PER)
			 		{
			 			usleep(ArenaConf::SLEEP_MTIME);
			 			$sleepCount = 0;
					}
			 		$posInfo = next($arrPosInfo);
			 	}
			 	catch (Exception $e )
			 	{
			 		Logger::fatal('ArenaRound::arenaRankSnap fail to update arena history for uid:%d', $uid);
			 		$posInfo = next($arrPosInfo);
			 	}
			}
		
			 //说明已经处理完了
			if ($pos2 >= $total)
			{
				break;
			}
			
			//循环取出接下来的100个用户进行发奖
			$pos1 = $pos2 + 1;
			$pos2 += ArenaConf::NUM_OF_QUERY;
			if ($pos2 > $total)
			{
				$pos2 = $total;
			}
			Logger::trace('ArenaRound::arenaRankSnap pos between %d and %d', $pos1, $pos2);
		}
		
		Logger::trace('ArenaRound::arenaRankSnap end...');
	}
		
	/**
	 * 发奖励，给脚本调用
	 */
	public static function generateReward($redo, $limit)
	{
		if (!$redo && !self::isLock())
		{
			Logger::trace('today is not the day of generateReward');
			return;
		}
		
		Logger::info('start to generateReward for postion');					
		
		$pos1 = 1;
		$pos2 = ArenaConf::NUM_OF_QUERY;
		$arenaReward = btstore_get()->ARENA_REWARD;
		$total = count($arenaReward);
		if ($pos2 > $total)
		{
			$pos2 = $total;
		}
		$curRound = self::getCurRound();
		Logger::trace('current round is :%d', $curRound);
		
		//3600*limit是随意取的值。用来区分是否是上一轮发的奖。
		//最大可取值 每轮天数×24×3600 - 发奖锁定时间	
		//别取太小了，不然出错重做的时候把当前期的当上一期就悲剧了。	
		$lessRewardTime = Util::getTime() - 3600 * $limit;
		
		$rate = ArenaLogic::getActiveRate();
		
		//收集所有需要通知的uid，然后一起通知
		$arrNotifyUid = array();
		MailConf::$NO_CALLBACK = true;
		RewardCfg::$NO_CALLBACK = true;
		
		$arrUidRank = array();
		while(true)
		{
			$arrPosInfo = false;
			for ($i = 0; $i < 3; $i++)
			{
				try {
					$arrPosInfo = ArenaDao::getPosRange4Reward($pos1, $pos2, $lessRewardTime, 
						array('uid', 'position'));
				}
				catch (Exception $e)
				{
					Logger::warning('get info from arena failed!');
					continue;
				}
				break;
			}	
			if ($arrPosInfo === false) 
			{
				Logger::fatal('Fix Me! Reward is not generated successfully. now pos:%d', $pos1);
				$arrPosInfo = array();
			}	
			Logger::trace('there is %d user between %d and %d', count($arrPosInfo), $pos1, $pos2);

            $arrUid = Util::arrayExtract($arrPosInfo, 'uid');
            $arrUserInfo = EnUser::getArrUser($arrUid, array('level'));			
            $posInfo = current($arrPosInfo);
            
            //连续修改多少个后休眠
            $sleepCount = 0;
            while ($posInfo !== false)
            {
            	try
				{
					$uid = $posInfo['uid'];
					if (ArenaLogic::isNpc($uid)) 
					{
						Logger::trace('uid:%d is NPC, ignore.', $uid);
						$posInfo = next($arrPosInfo);
						continue;
					}
					if ( !isset($arrUserInfo[$uid]))
					{
						Logger::fatal('fail to get user %d', $uid);
						$posInfo = next($arrPosInfo);
						continue;
					}
					//获取到的玩家，应该都能在配置中找到对应position的奖励
					$position = $posInfo['position'];
					$conf = btstore_get()->ARENA_REWARD[$position];
					$maxLevel = max($arrUserInfo[$uid]['level'], 30);
					$reward = array(
							RewardType::SOUL => $conf['soul'] * $maxLevel * $rate,
							RewardType::SILVER => $conf['silver'] * $maxLevel * $rate,
							RewardType::ARR_ITEM_TPL => $conf['items']->toArray(),
							RewardType::PRESTIGE => $conf['prestige'] * $rate,
							RewardDef::EXT_DATA => array( 'rank' => $position ),
					);
					Logger::trace('generate reward for user:%d, reward:%s', $uid, $reward);
					
					//发邮件通知用户
					MailTemplate::sendArenaAward($uid, $curRound, $position, $reward[RewardType::SOUL],
					$reward[RewardType::SILVER], $reward[RewardType::PRESTIGE], $reward[RewardType::ARR_ITEM_TPL]);
					//发奖励到奖励中心
					$rid = EnReward::sendReward($uid, RewardSource::ARENA_RANK, $reward);
					Logger::trace('user:%d reward id is %d in reward center', $uid, $rid);
					
					$reward['items'] = $reward[RewardType::ARR_ITEM_TPL];
					unset($reward[RewardType::ARR_ITEM_TPL]);
					unset($reward[RewardDef::EXT_DATA]);
					//更新数据库
					ArenaDao::update($uid, array('reward_time' => Util::getTime(), 'va_reward' => $reward));
					
					$arrUidRank[$uid] = $position;
					//EnAchieve::updateArenaRank($uid, $position);

					$arrNotifyUid[] = $uid;
					/* 海贼中使用竞技场上一轮排名作为玩家实力的一个衡量。在阵营战中作为分组的依据。
					 * 这个东西，现在没有用。且竞技场发奖有压力，所以先不要写这个了
					try 
					{
						//更新用户历史排名
						ArenaDao::updateHis($uid, $position, Util::getTime());
					}
					catch (Exception $e)
					{
						Logger::warning('fail to update arena history');
					}
					*/
					
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
			$pos1 = $pos2 + 1;
			$pos2 += ArenaConf::NUM_OF_QUERY;
			if ($pos2 > $total)
			{
				$pos2 = $total;
			}
			Logger::trace('pos between %d and %d', $pos1, $pos2);
		}
		
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::MAIL_CALLBACK, array() );
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::REWARD_NEW, array() );
		
		$batchNum = 1000;
		$slice = array();
		foreach($arrUidRank as $uid => $rank) {
			$slice[$uid] = $rank;
			if(sizeof($slice) >= $batchNum) {
				RPCContext::getInstance()->executeTask($uid,
				 	'achieve.updateTypeArrBySystem',
				  	array(AchieveDef::ARENA_RANK, $slice));
				$slice = array();
			}
		}
		if(sizeof($slice) > 0) {
				RPCContext::getInstance()->executeTask($uid,
				 	'achieve.updateTypeArrBySystem',
				  	array(AchieveDef::ARENA_RANK, $slice));
		}
	}
	
	public static function arenaCheckReward()
	{	
		if (!self::isLock())
		{
			Logger::trace('today is not the day of generateReward');
			return;
		}
		//判断排名发奖成功没有, 根据发奖时间是否都更新至今天的发奖时间了
		$minRewardTime = ArenaDao::getMinRewardTime();
		if (Util::isSameDay($minRewardTime) == false) 
		{
			$redo = true;
			$limit = ArenaConf::REWARD_REDO_LIMIT_HOURS;
			self::generateReward($redo, $limit);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */