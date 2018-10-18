<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaLuckyLogic.class.php 161101 2015-03-11 10:34:02Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/ArenaLuckyLogic.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2015-03-11 10:34:02 +0000 (Wed, 11 Mar 2015) $
 * @version $Revision: 161101 $
 * @brief 
 *  
 **/

class ArenaLuckyLogic
{		
	/**
	 * 这个给脚本执行的，
	 * 产生幸运奖的排名
	 * 数据库为空时会产生当天的数据，如果合服到10点之后就会导致一直lock，再运行一次就可以解锁了
	 */
	public static function generatePosition()
	{
		Logger::trace('ArenaLuckyLogic::generatePosition Start');
		
		if (!ArenaRound::isLock())
		{
			Logger::trace('today is not the day of generatePosition');
			return;
		}

		$curTime = Util::getTime();
		
		//数据查询到的发奖日期为空，说明是开服前运行脚本
		$curDateFromDb = ArenaRound::getCurRoundDate();
		if (empty($curDateFromDb))
		{
			//开服第N天（包括当天）发奖
			$beginTime = strtotime(GameConf::SERVER_OPEN_YMD);
			$beginTime += (ArenaDateConf::LAST_DAYS - 1) * SECONDS_OF_DAY; 
			$beginDate = strftime("%Y%m%d", $beginTime);
			//echo '竞技场发奖日期为:' . $beginDate . "\n";
			//echo "别提前开服，否则竞技场发奖时间可能会错误\n";
		}
		else
		{
			//开服之后每次都是隔N天后发奖, 一旦打乱，以后都会出错
			$beginDateStr =  "+ " . ArenaDateConf::LAST_DAYS . " day";
			$beginDate = intval(strftime("%Y%m%d", strtotime($beginDateStr, $curTime)));
		}
		
		$arrPos = array();
		$lucky = array();
		foreach (ArenaConf::$LUCKY_POSITION_CONFIG as $cfg)
		{
			//循环多少次， 循环这么多次还没出结果可能是策划配置错误
			$circleNum = 1000;
			while($circleNum-- > 0)
			{
				$pos = rand($cfg[0], $cfg[1]);
				if (!in_array($pos, $arrPos))
				{
					$arrPos[] = $pos;
					$lucky[] = array('position' => $pos, 'gold' => $cfg[2]);
					break;
				}
			}
		}
		
		//begindate是天数，没有时分秒
		$arrField = array('begin_date' => $beginDate, 'active_rate' => 0, 'va_lucky' => $lucky);
		$arrRet = ArenaLuckyDao::insert($arrField);
		Logger::debug('insert return:%s', $arrRet);			
		
		//设置当前的轮数到memcache，如第N轮
		ArenaRound::setCurRound();
		//设置发奖时间到memcache
		ArenaRound::setCurRoundDate();
		Logger::trace('ArenaLuckyLogic::generatePosition Start');
	}
	
	public static function diffDate($date1, $date2)
	{
		$datetime1 = date_create($date1);
		$datetime2 = date_create($date2);
		$interval = date_diff($datetime2, $datetime1);
		return $interval->format('%R%a');		
	}
	
	/**
	 * 给脚本执行
	 * 给幸运排名发奖
	 * 每一步都update db， 出错后可以重做。
	 * 现在量很小，只有10个。如果量比较大，可以对position uid 使用select in
	 */
	public static function rewardLuckyPosition()
	{
		if (!ArenaRound::isLock())
		{
			Logger::trace('today is not the day of rewardLuckyPosition');
			return;
		}
		
		Logger::info('start to rewardLuckyPosition for lucky postion');
		
		$beginDate = ArenaRound::getCurRoundDate();		
		$arrLucky = ArenaLuckyDao::select($beginDate, array('va_lucky'));
		if (empty($arrLucky))
		{
			throw new SysException('fail to reward lucky position, cannot get lucky position.');
		}
		
		$curRound = ArenaRound::getCurRound();
		$arrUpdateLucky = $arrLucky['va_lucky'];
		foreach ($arrLucky['va_lucky'] as $key => $luckyPos)
		{
			//已有uid，说明已经发过奖了
			if (isset($luckyPos['uid']))
			{
				continue;
			}
			
			$pos = $luckyPos['position'];
			$posInfo = ArenaDao::getByPos($pos, array('uid'));
			if (empty($posInfo))
			{
				Logger::info('fail to reward lucky position %d, fail to get info by position', $pos);
				continue;
			}
			$uid = $posInfo['uid'];
			Logger::debug('get uid:%d for position:%d', $uid, $pos);
			
			$arrUpdateLucky[$key]['uid'] = $uid;
			if (ArenaLogic::isNpc($uid))
			{
				$arrUpdateLucky[$key]['utid'] = ArenaLogic::getNpcUtid($uid);
				$arrUpdateLucky[$key]['uname'] = ArenaLogic::getNpcName($uid);
			}
			else 
			{
				$user = EnUser::getUserObj($uid);
				$arrUpdateLucky[$key]['utid'] = $user->getUtid();
				$arrUpdateLucky[$key]['uname'] = $user->getUname();
				$reward = array(
						RewardType::GOLD => $luckyPos['gold'],
						RewardDef::EXT_DATA => array( 'rank' => $pos ),
				);
				MailTemplate::sendArenaLuckyAward($uid, $curRound, $pos, $reward[RewardType::GOLD]);
				// 发送奖励到奖励中心
				EnReward::sendReward($uid, RewardSource::ARENA_LUCKY, $reward);
			}
			
			// 更新到数据库
			$rate = ArenaLogic::getActiveRate();
			ArenaLuckyDao::update($beginDate, array('active_rate' => $rate, 'va_lucky' => $arrUpdateLucky));				
		}
		Logger::debug('finish rewarding lucky position:%s', $arrUpdateLucky);
	} 
	
	public static function getRewardLuckyList($uid)
	{
		if (EnSwitch::isSwitchOpen(SwitchDef::ARENA) == false)
		{
			throw new FakeException('user:%d does not open the arena', $uid);
		}
		$arrReward = ArenaLuckyDao::getRewardLuckyList(array('va_lucky'));
		$arrRet = array('last'=>array(), 'current'=>array());
		if (isset($arrReward[0]['va_lucky']))
		{
			$arrRet['last'] = $arrReward[0]['va_lucky'];
		}
		if (isset($arrReward[1]['va_lucky']))
		{
			$arrRet['current'] = $arrReward[1]['va_lucky'];
		}
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */