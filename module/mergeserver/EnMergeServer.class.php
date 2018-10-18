<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnMergeServer.class.php 177999 2015-06-10 14:12:14Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mergeserver/EnMergeServer.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-10 14:12:14 +0000 (Wed, 10 Jun 2015) $
 * @version $Revision: 177999 $
 * @brief 
 * 
 **/
 
/**********************************************************************************************************************
 * Class       : EnMergeServer
 * Description : 合服活动内部接口类
 * Inherit     : 
 **********************************************************************************************************************/
class EnMergeServer
{
	/**
	 * 获得某个时间段范围内，进行合服的服务器个数，如果不在时间范围内，返回1
	 * 
	 * @param int $startTime	开始时间
	 * @param int $endTime		结束时间
	 * @param int $max			最大值，默认5
	 * @throws InterException
	 * @return int
	 */
	public static function getMergeServerCount($startTime, $endTime, $max = 5)
	{
		if (!defined ('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			return 1;
		}

		$mergeTime = strtotime(GameConf::MERGE_SERVER_OPEN_DATE);
		if($mergeTime < $startTime || $mergeTime > $endTime)
		{
			return 1;
		}
		
		$count = count(GameConf::$MERGE_SERVER_DATASETTING);
		if ($count <= 0) 
		{
			throw new InterException('invalid merge server date setting[%s]',  GameConf::$MERGE_SERVER_DATASETTING);
		}
		
		return $count > $max ? $max : $count;
	}
	
	/**
	 * loginNotify 登陆通知 
	 * 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function loginNotify()
	{
		Logger::trace('EnMergeServer::loginNotify begin...');
		
		if (FALSE === MergeServerUtil::checkBasicConfig())
		{
			Logger::debug('loginNotify end because of basic config.');
			return;
		}
		
		if (!MergeServerUtil::checkRewardTime(MergeServerDef::MSERVER_TYPE_LOGIN)
			&& !MergeServerUtil::checkRewardTime(MergeServerDef::MSERVER_TYPE_COMPENSATION)) 
		{
			Logger::debug('loginNotify end because of login and compensation over');
			return;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		$userMergeServerObj = MergeServerObj::getInstance($uid);
		$needUpdate = FALSE;
		
		if (MergeServerUtil::checkRewardTime(MergeServerDef::MSERVER_TYPE_LOGIN))
		{
			if (Util::isSameDay($userMergeServerObj->getLoginTime()))
			{
				Logger::debug('loginNotify already[user:%d,curr:%s,db:%s].', $uid, strftime("%Y%m%d-%H%M%S", Util::getTime()),
								strftime("%Y%m%d-%H%M%S", $userMergeServerObj->getLoginTime()));
			}
			else
			{
				Logger::debug('loginNotify[user:%d,curr:%s,db:%s].', $uid, strftime("%Y%m%d-%H%M%S", Util::getTime()),
								strftime("%Y%m%d-%H%M%S", $userMergeServerObj->getLoginTime()));
				$userMergeServerObj->increLoginCount();
				$needUpdate = TRUE;
			}
		}
		
		$haveCompensation = FALSE;
		$rewardArrGroup = array();
		if (MergeServerUtil::checkRewardTime(MergeServerDef::MSERVER_TYPE_COMPENSATION))
		{	
			if (FALSE === $userMergeServerObj->isCompensated())
			{
				$rewardArrGroup = self::getCompensationReward($uid);
				
				if (FALSE === $rewardArrGroup) 
				{
					Logger::warning('uid[%d] getCompensationReward error!', $uid);;
				}
				else if (!empty($rewardArrGroup))
				{
					$userMergeServerObj->setCompensateTime(Util::getTime());
					$needUpdate = TRUE;
					$haveCompensation = TRUE;
				}
				else
				{
					Logger::warning('uid[%d] compensation rewardArrGroup is empty, please check.', $uid);
				}
			}
			else 
			{
				Logger::debug('uid[%d] is already compensated, ignore.', $uid);
			}
		}
		
		if ($needUpdate) 
		{
			$userMergeServerObj->update();
			
			if ($haveCompensation)
			{
				RewardUtil::rewardUtil2Center($uid, $rewardArrGroup, RewardSource::MERGE_SERVER_COMPENSATION);
			}
		}
		
		Logger::trace('EnMergeServer::loginNotify end...');
	}

	/**
	 * getArenaPrestigeRewardRate 获得竞技场声望倍率 
	 * 
	 * @static
	 * @access public
	 * @return int 倍率
	 */
	public static function getArenaPrestigeRewardRate()
	{
		Logger::trace('EnMergeServer::getArenaPrestigeRewardRate begin...');
		
		$ret = MergeServerDef::MSERVER_BASE_RATE;
		if (FALSE === MergeServerUtil::checkEffect(MergeServerDef::MSERVER_TYPE_ARENA))
		{
			Logger::debug('getArenaPrestigeRewardRate end[rate:%d] because of merge server activity %s is not effect', $ret,
					    	MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_ARENA));
			return $ret;
		}
		$ret *= MergeServerDef::MSERVER_ARENA_PRESTIGE_RATE;

		Logger::trace('EnMergeServer::getArenaPrestigeRewardRate end[%d]...', $ret);
		return $ret; 
	}

	/**
	 * getGoldTreeRewardRate 获得摇钱树攻击次数倍率 
	 * 
	 * @static
	 * @access public
	 * @return int 倍率
	 */
	public static function getGoldTreeRewardRate()
	{
		Logger::trace('EnMergeServer::getGoldTreeRewardRate begin...');
		
		$ret = self::getRateByCopyId(ACT_COPY_TYPE::GOLDTREE_COPYID);
		
		Logger::trace('EnMergeServer::getGoldTreeRewardRate end[%d]...', $ret);
		return $ret;
	}

	/**
	 * getExpTreasureRewardRate 获得经验宝物攻击次数倍率 
	 * 
	 * @static
	 * @access public
	 * @return int 倍率
	 */
	public static function getExpTreasureRewardRate()
	{
		Logger::trace('EnMergeServer::getExpTreasureRewardRate begin...');
		
		$ret = self::getRateByCopyId(ACT_COPY_TYPE::EXPTREAS_COPYID);
		
		Logger::trace('EnMergeServer::getExpTreasureRewardRate end[%d]...', $ret);
		return $ret;
	}

	/**
	 * isMonthCardEffect 月卡大礼包是否生效 
	 * 
	 * @param $time 判断该时间戳是否在合服活动月卡礼包的生效时间内
	 * @static
	 * @access public
	 * @return bool 是否生效
	 */
	public static function isMonthCardEffect($time = 0)
	{
		Logger::trace('EnMergeServer::isMonthCardEffect begin...');
		
		if (FALSE === MergeServerUtil::checkBasicConfig())
		{
			Logger::debug('EnMergeServer::isMonthCardEffect end[false] because of basic config.');
			return FALSE;
		}
		
		$checkTime = intval($time) == 0 ? Util::getTime() : intval($time);
		if (FALSE === MergeServerUtil::checkRewardTime(MergeServerDef::MSERVER_TYPE_MONTH_CARD, $checkTime))
		{
			Logger::debug('EnMergeServer::isMonthCardEffect end[false] because of activity[%s] time[%s] is over.', 
						MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_MONTH_CARD), strftime("%Y%m%d-%H%M%S", $checkTime));
			return FALSE;
		}
		
		Logger::trace('EnMergeServer::isMonthCardEffect end[true]...');
		return TRUE;
	}
	
	/**
	 * 获得资源矿合服加成
	 * 
	 * @return number
	 */
	public static function getMineralRate()
	{
		$ret = MergeServerDef::MSERVER_BASE_RATE;
		if (FALSE === MergeServerUtil::checkEffect(MergeServerDef::MSERVER_TYPE_MINERAL))
		{
			Logger::debug('getMineralRate end[rate:%d] because of merge server activity %s is not effect', $ret, MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_MINERAL));
			return $ret;
		}
		
		$config = MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_MINERAL);
		if (empty($config) || !isset($config['rate']))
		{
			Logger::debug('getMineralRate end[rate:%d] because of merge server activity %s config is empty or no rate', $ret, MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_MINERAL));
			return $ret;
		}
		
		$ret = $config['rate'] / UNIT_BASE;
		Logger::debug('getMineralRate end[rate:%d]', $ret);
		return $ret;
	}

	/**
	 * getRateByCopyId 根据副本id获得倍率
	 * 
	 * @param int $copyId 副本id
	 * @static
	 * @access private
	 * @return int 倍率
	 */
	private static function getRateByCopyId($copyId)
	{
		$ret = MergeServerDef::MSERVER_BASE_RATE;
		if (FALSE === MergeServerUtil::checkEffect(MergeServerDef::MSERVER_TYPE_EXP_GOLD))
		{
			Logger::debug('getRateByCopyId end[copyId:%d, rate:%d] because of merge server activity %s is not effect', $copyId, $ret,
                           MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_EXP_GOLD));
			return $ret;
		}

		$config = MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_EXP_GOLD); 
		if (empty($config) || !isset($config[$copyId]))
		{
			Logger::debug('getRateByCopyId end[copyId:%d, rate:%d] because of merge server activity %s config is empty or do not have copyId', $copyId, $ret,
                           MergeServerUtil::getStringDesc(MergeServerDef::MSERVER_TYPE_EXP_GOLD));
			return $ret;
		}

		$ret *= intval($config[$copyId]);
		Logger::debug('getRateByCopyId end[copyId:%d, rate:%d]', $copyId, $ret);
		return $ret;
	}
	
	/**
	 * getCompensationReward 获取用户补偿数组
	 *
	 * @param int $uid 用户id
	 * @static
	 * @access private
	 * @return bool/array 没有错误发生，返回用户补偿数组，否则返回FALSE
	 */
	private static function getCompensationReward($uid)
	{
		Logger::trace('EnMergeServer::getCompensationReward uid:%d begin...', $uid);
		
		/*
		 * 合服补偿中有2部分奖励，一部分和两个服务器的天数差有关，一部分是固定值
		 * 其中和天数有关的奖励：合服补偿数值=合服基础补偿*（补偿系数+min（开服天数差，天数最大值））
		 * 与天数无关的奖励直接按照配置值进行补偿
		 * 合服天数差＝本服器开服时间－最先开服服务器的开服时间
		 */
	
		// 获得配置
		$config = MergeServerUtil::getRewardConfig(MergeServerDef::MSERVER_TYPE_COMPENSATION);
		$base = $config['base']->toArray();    		// 合服补偿基础值
		$fix = $config['fix']->toArray();			// 合服补偿固定值
		$coef = $config['coef'];					// 补偿系数
		$maxDay = $config['max'];					// 天数最大值
		
		// 计算该用户所在服开服时间，相对于合服中所有服务器最早开服时间的天数差
		$userServerId = Util::getServerId();
		$userOpenDate = MergeServerUtil::getOpenDate($userServerId);
		if (FALSE === $userOpenDate)
		{
			Logger::warning("can not get user server open date:serverId[%d], date setting[%s]", $userServerId, GameConf::$MERGE_SERVER_DATASETTING);
			return FALSE;
		}
		
		$minOpenDate = MergeServerUtil::getMinOpenDate();
		if (FALSE === $minOpenDate)
		{
			Logger::warning("can not get min server open date:date setting[%s]", GameConf::$MERGE_SERVER_DATASETTING);
			return FALSE;
		}
		
		$diffDaysFromMin = MergeServerUtil::getDaysBetween($userOpenDate, $minOpenDate);
		if (FALSE === $diffDaysFromMin)
		{
			Logger::warning("date format is wrong:userOpenDate[%s], minOpenDate[%s]", $userOpenDate, $minOpenDate);
			return FALSE;
		}
		
		// 计算合服补偿中的浮动部分：合服补偿数值=合服基础补偿*（补偿系数+min（开服天数差，天数最大值））
		foreach ($base as &$reward)
		{
			$magic = ($coef + min($diffDaysFromMin, $maxDay));
			if (is_int($reward['val']))
			{
				$reward['val'] *= $magic;
			}
			elseif (is_array($reward['val']))
			{
				foreach ($reward['val'] as &$single)
				{
					if (isset($single[1]) && is_int($single[1])) 
					{
						$single[1] *= $magic;
					}
					else
					{
						Logger::warning("btstore data:MERGESERVER_COMPENSATION is wrong:%s", $reward);
						return FALSE;
					}
				}
			}
			else
			{
				// do nothing
			}
		}
	
		//  合服补偿中有2部分奖励，一部分和两个服务器的天数差有关，一部分是固定值
		$compensationGroup = array($base, $fix);
		
		Logger::debug("EnMergeServer::getCompensationReward:serverId:%d,userOpenDate:%d,minOpenDate:%d,diff:%d,coef:%d,maxDay:%d,compensationGroup:%s"
						,$userServerId, $userOpenDate, $minOpenDate, $diffDaysFromMin, $coef, $maxDay, $compensationGroup);
		
		Logger::trace('EnMergeServer::getCompensationReward uid:%d compensation end...', $uid);
		return $compensationGroup;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
