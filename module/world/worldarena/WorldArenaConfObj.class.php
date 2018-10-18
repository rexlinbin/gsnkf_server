<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaConfObj.class.php 244613 2016-05-30 06:49:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArenaConfObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-30 06:49:52 +0000 (Mon, 30 May 2016) $
 * @version $Revision: 244613 $
 * @brief 
 *  
 **/
 
class WorldArenaConfObj
{
	/**
	 * 唯一实例
	 * @var WorldArenaConfObj
	 */
	private static $sInstance = NULL;

	/**
	 * 活动版本
	 * @var int
	 */
	private $mVersion;

	/**
	 * 开始时间
	 * @var int
	 */
	private $mStartTime;

	/**
	 * 结束时间
	 * @var int
	 */
	private $mEndTime;

	/**
	 * 需要开启时间
	 * @var int
	 */
	private $mNeedOpenTime;

	/**
	 * 具体配置数据
	 * @var array
	 */
	private $mConf;

	/**
	 * 返回唯一实例
	 *
	 * @param string $field
	 * @return WorldArenaConfObj
	 */
	public static function getInstance($field = WorldArenaField::INNER)
	{
		if (!isset(self::$sInstance))
		{
			self::$sInstance = new self($field);
		}
		return self::$sInstance;
	}

	/**
	 * 释放实例
	 */
	public static function release()
	{
		if (isset(self::$sInstance))
		{
			unset(self::$sInstance);
		}
	}

	/**
	 * 构造函数
	 * @param int $field
	 * @throws ConfigException
	 */
	function __construct($field)
	{
		if ($field == WorldArenaField::CROSS)
		{
			$activityConf = ActivityConfLogic::getConf4Backend(ActivityName::WORLDARENA, 0);
		}
		else
		{
			$activityConf = EnActivity::getConfByName(ActivityName::WORLDARENA);
		}
		Logger::trace('WorldArenaConfObj raw conf[%s]', $activityConf);

		$this->mVersion = $activityConf['version'];
		$this->mStartTime = floor($activityConf['start_time'] / 60) * 60;
		$this->mEndTime = floor($activityConf['end_time'] / 60) * 60;
		$this->mNeedOpenTime = floor($activityConf['need_open_time'] / 60) * 60;

		if (empty($activityConf['data']))
		{
			if ($this->mStartTime > 0)
			{
				throw new ConfigException('WorldArenaConfObj.construct failed, no data in activityConf[%s]', $activityConf);
			}
			Logger::info('WorldArenaConfObj.construct failed, empty activityConf[%s]', $activityConf);
		}
		else
		{
			$this->mConf = $activityConf['data'];
		}
		Logger::trace('WorldArenaConfObj cur conf[%s]', $this->mConf);
	}

	/**
	 * 活动版本
	 * 
	 * @return number
	 */
	public function getActivityVersion()
	{
		return $this->mVersion;
	}

	/**
	 * 活动开始时间
	 * 
	 * @return number
	 */
	public function getActivityStartTime()
	{
		return $this->mStartTime;
	}

	/**
	 * 活动结束时间
	 * 
	 * @return number
	 */
	public function getActivityEndTime()
	{
		return $this->mEndTime;
	}

	/**
	 * 活动需要的最晚开服时间
	 * 
	 * @return number
	 */
	public function getActivityNeedOpenTime()
	{
		return $this->mNeedOpenTime;
	}
	
	/**
	 * 判断一个时间是否处于这个活动时间内，默认是当前时间
	 * 
	 * @param number $time
	 * @return boolean
	 */
	public function isValid($time = 0)
	{
		if ($time == 0)
		{
			$time = Util::getTime();
		}
	
		if ($time < $this->mStartTime
			|| $time > $this->mEndTime
			|| $this->mStartTime == 0
			|| $this->mEndTime == 0)
		{
			return FALSE;
		}
	
		return TRUE;
	}
	
	/**
	 * 获得某一个字段的配置
	 * 
	 * @param unknown $field
	 * @return multitype:
	 */
	public function getConf($field)
	{
		return $this->mConf[$field];
	}
	
	/**
	 * 根据参数给出的时间，获取每个活动周期开始的时间
	 * 
	 * @param number $time
	 */
	public function getPeriodBgnTime($time = 0)
	{
		if (WorldArenaConf::$TEST_MODE > 0)
		{
			$hour = date('H', empty($time) ? Util::getTime() : $time);
			return strtotime(date('Y-m-d H:', (empty($time) ? Util::getTime() : $time)) . '00:00') - ((WorldArenaConf::$TEST_MODE + $hour % 2) % 2 * 3600);
		}
		
		return WorldArenaUtil::getCurrWeekStart($time);
	}
	
	/**
	 * 根据参数给出的时间，获取每个活动周期结束的时间
	 * 
	 * @param number $time
	 */
	public function getPeriodEndTime($time = 0)
	{
		if (WorldArenaConf::$TEST_MODE > 0)
		{
			return $this->getPeriodBgnTime($time) + 2 * 3600; // 120分钟
		}
				
		return $this->getPeriodBgnTime($time) + 7 * SECONDS_OF_DAY;
	}
	
	/**
	 * 根据参数给出的时间，获取每个活动周期报名开始的时间
	 * 
	 * @param number $time
	 */
	public function getSignupBgnTime($time = 0)
	{
		if (WorldArenaConf::$TEST_MODE > 0)
		{
			return $this->getPeriodBgnTime($time) + WorldArenaConf::$TEST_OFFSET[0];
		}
		
		$timeConfig = $this->getConf('timeConfig');
		return $this->getPeriodBgnTime($time) + $timeConfig[0];
	}
	
	/**
	 * 根据参数给出的时间，获取每个活动周期报名结束的时间
	 * 
	 * @param number $time
	 */
	public function getSignupEndTime($time = 0)
	{
		if (WorldArenaConf::$TEST_MODE > 0)
		{
			return $this->getPeriodBgnTime($time) + WorldArenaConf::$TEST_OFFSET[1];
		}
		
		$timeConfig = $this->getConf('timeConfig');
		return $this->getPeriodBgnTime($time) + $timeConfig[1];
	}
	
	/**
	 * 根据参数给出的时间，获取每个活动周期攻打开始的时间
	 * 
	 * @param number $time
	 */
	public function getAttackBgnTime($time = 0)
	{
		if (WorldArenaConf::$TEST_MODE > 0)
		{
			return $this->getPeriodBgnTime($time) + WorldArenaConf::$TEST_OFFSET[2];
		}
		
		$timeConfig = $this->getConf('timeConfig');
		return $this->getPeriodBgnTime($time) + $timeConfig[2];
	}
	
	/**
	 * 根据参数给出的时间，获取每个活动周期攻打结束的时间
	 * 
	 * @param number $time
	 */
	public function getAttackEndTime($time = 0)
	{
		if (WorldArenaConf::$TEST_MODE > 0)
		{
			return $this->getPeriodBgnTime($time) + WorldArenaConf::$TEST_OFFSET[3];
		}
		
		$timeConfig = $this->getConf('timeConfig');
		return $this->getPeriodBgnTime($time) + $timeConfig[3];
	}
	
	/**
	 * 根据参数给出的时间，获取参数时间所在的周期内，所处的阶段
	 * 
	 * @param number $time
	 */
	public function getStage($time = 0)
	{
		if (!$this->isValid($time)) 
		{
			return WorldArenaDef::STAGE_TYPE_INVALID;
		}
		
		if ($time == 0) 
		{
			$time = Util::getTime();
		}
		
		if ($time < $this->getSignupBgnTime($time)) 
		{
			return WorldArenaDef::STAGE_TYPE_BEFORE_SIGNUP;
		}
		
		if ($time < $this->getSignupEndTime($time))
		{
			return WorldArenaDef::STAGE_TYPE_SIGNUP;
		}
		
		if ($time < $this->getAttackBgnTime($time))
		{
			return WorldArenaDef::STAGE_TYPE_RANGE_ROOM;
		}
		
		if ($time < $this->getAttackEndTime($time))
		{
			return WorldArenaDef::STAGE_TYPE_ATTACK;
		}
		
		return WorldArenaDef::STAGE_TYPE_REWARD;
	}
	
	/**
	 * 获得报名需要的等级
	 */
	public function getNeedLevel()
	{
		return $this->getConf('need_level');
	}
	
	/**
	 * 获取玩家报名期间更新战斗信息的冷却时间
	 */
	public function getColdTime()
	{
		return $this->getConf('update_cold_time');
	}
	
	/**
	 * 获得保护时间
	 */
	public function getProtectTime()
	{
		return $this->getConf('protect_time');
	}
	
	/**
	 * 获得免费的攻击次数
	 */
	public function getFreeAtkNum()
	{
		return $this->getConf('free_atk_num');
	}
	
	/**
	 * 获得拉取对手时候的目标系数
	 */
	public function getTargetCoef()
	{
		return $this->getConf('target_coef');
	}
	
	/**
	 * 获得每个房间的标准人数
	 */
	public function getRoomCapacity()
	{
		return $this->getConf('room_user_count');
	}
	
	/**
	 * 获得房间最少人数
	 */
	public function getRoomMinCount()
	{
		return $this->getConf('room_min_user_count');
	}
	
	/**
	 * 击败对手胜利的奖励
	 */
	public function getWinReward()
	{
		return $this->getConf('win_reward');
	}
	
	/**
	 * 主动攻击，失败获得的奖励
	 */
	public function getLoseReward()
	{
		return $this->getConf('lose_reward');
	}
	
	/**
	 * 根据参数的连杀次数，获得连杀奖励
	 * 
	 * @param int $contiNum
	 */
	public function getContiReward($contiNum)
	{
		$ret = array();
		
		$arrReward = $this->getConf('conti_reward');
		foreach ($arrReward as $count => $reward)
		{
			if ($contiNum <= $count)
			{
				return $reward;
			}
		}
		
		throw new ConfigException('conti reward config error, cur config[%s], cur counti num[%d]', $arrReward, $contiNum);
	}
	
	/**
	 * 获取连杀奖励的有效档位
	 * 
	 * @return array
	 */
	public function getValidConti()
	{
		$arrReward = $this->getConf('conti_reward');
		return array_keys($arrReward);
	}
	
	/**
	 * 根据参数的终结连杀次数，获得终结连杀奖励
	 * 
	 * @param int $terminalContiNum
	 */
	public function getTerminalContiReward($terminalContiNum)
	{
		$ret = array();
		
		$arrReward = $this->getConf('termianl_conti_reward');
		foreach ($arrReward as $count => $reward)
		{
			if ($terminalContiNum <= $count)
			{
				return $reward;
			}
		}
		
		throw new ConfigException('terminal conti reward config error, cur config[%s], cur terminal counti num[%d]', $arrReward, $terminalContiNum);
	}
	
	/**
	 * 获取终结连杀奖励的有效档位
	 *
	 * @return array
	 */
	public function getValidTerminalConti()
	{
		$arrReward = $this->getConf('termianl_conti_reward');
		return array_keys($arrReward);
	}
	
	/**
	 * 返回玩家可以购买挑战次数的上限
	 */
	public function getMaxBuyAtkNum()
	{
		$ret = 0;
		
		$arrCost = $this->getConf('buy_cost');
		foreach ($arrCost as $maxNum => $cost)
		{
			if ($maxNum > $ret) 
			{
				$ret = $maxNum;
			}
		}
		
		return $ret;
	}
	
	/**
	 * 根据参数的购买次数，获取这次购买的花费
	 * 
	 * @param int $num
	 */
	public function getBuyAtkCost($num)
	{		
		$arrCost = $this->getConf('buy_cost');
		foreach ($arrCost as $maxNum => $cost)
		{
			if ($num <= $maxNum) 
			{
				return $cost;
			}
		}
		
		throw new ConfigException('cost config error, cur config[%s], cur num[%s]', $arrCost, $num);
	}
	
	/**
	 * 根据排名获取位置排名奖励
	 * 
	 * @param int $rank
	 * @throws ConfigException
	 * @return array
	 */
	public function getPosRankReward($rank)
	{		
		$arrReward = $this->getConf('pos_rank_reward');
		foreach ($arrReward as $maxRank => $reward)
		{
			if ($rank <= $maxRank)
			{
				return $reward;
			}
		}
		
		throw new ConfigException('pos rank reward config error, cur config[%s], cur rank[%s]', $arrReward, $rank);
	}
	
	/**
	 * 根据排名获取击杀总数排名奖励
	 *
	 * @param int $rank
	 * @throws ConfigException
	 * @return array
	 */
	public function getKillRankReward($rank)
	{
		$arrReward = $this->getConf('kill_rank_reward');
		foreach ($arrReward as $maxRank => $reward)
		{
			if ($rank <= $maxRank)
			{
				return $reward;
			}
		}
	
		throw new ConfigException('kill rank reward config error, cur config[%s], cur rank[%s]', $arrReward, $rank);
	}
	
	/**
	 * 根据排名获取最大连杀排名奖励
	 *
	 * @param int $rank
	 * @throws ConfigException
	 * @return array
	 */
	public function getContiRankReward($rank)
	{
		$arrReward = $this->getConf('conti_rank_reward');
		foreach ($arrReward as $maxRank => $reward)
		{
			if ($rank <= $maxRank)
			{
				return $reward;
			}
		}
	
		throw new ConfigException('conti rank reward config error, cur config[%s], cur rank[%s]', $arrReward, $rank);
	}
	
	/**
	 * 返回三榜都是第一的奖励内容
	 * 
	 * @return array
	 */
	public function getKingReward()
	{
		$arrReward = $this->getConf('king_reward');
		return $arrReward;
	}
	
	/**
	 * 根据是金币重置还是银币重置，获取最大的充值次数
	 * 
	 * @param int $type
	 */
	public function getMaxResetNum($type)
	{
		$arrCost = array();
		if ($type == WorldArenaDef::RESET_TYPE_GOLD) 
		{
			$arrCost = $this->getConf('gold_reset_cost');
		}
		else if ($type == WorldArenaDef::RESET_TYPE_SILVER) 
		{
			$arrCost = $this->getConf('silver_reset_cost');
		}
		else 
		{
			throw new InterException('invalid reset type[%s], all valid type[%s]', $type, WorldArenaDef::$VALID_RESET_TYPE);
		}
		
		$ret = 0;
		foreach ($arrCost as $maxNum => $cost)
		{
			if ($maxNum > $ret)
			{
				$ret = $maxNum;
			}
		}
		
		return $ret;
	}
	
	/**
	 * 根据是金币重置，还是银币重置，以及重置的次数，获取花费
	 * 
	 * @param int $type
	 * @param int $num
	 */
	public function getResetCost($type, $num)
	{
		$arrCost = array();
		if ($type == WorldArenaDef::RESET_TYPE_GOLD)
		{
			$arrCost = $this->getConf('gold_reset_cost');
		}
		else if ($type == WorldArenaDef::RESET_TYPE_SILVER)
		{
			$arrCost = $this->getConf('silver_reset_cost');
		}
		else
		{
			throw new InterException('invalid reset type[%s], all valid type[%s]', $type, WorldArenaDef::$VALID_RESET_TYPE);
		}
		
		foreach ($arrCost as $maxNum => $cost)
		{
			if ($num <= $maxNum)
			{
				return $cost;
			}
		}
		
		throw new ConfigException('cost config error, cur config[%s], $type[%s], cur num[%s]', $arrCost, $type, $num);
	}
	
	/**
	 * 返回自动分组时候服务器需要开启的天数
	 * 
	 * @return int
	 */
	public function getNeedOpenDays()
	{
		return $this->getConf('need_open_days');
	}
	
	/**
	 * 是否在挑战冷却时间
	 * 
	 * @return boolean
	 */
	public function inAttackCdPeriod($time = 0)
	{
		if ($time == 0)
		{
			$time = Util::getTime();
		}
		
		$attackEndTime = $this->getAttackEndTime();
		$cdBgnTime = $attackEndTime - WorldArenaConf::CD_DURATION;
		if ($time >= $cdBgnTime && $time <= $attackEndTime) 
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * 获得挑战cd
	 * 
	 * @return number
	 */
	public function getAttackCd()
	{
		return $this->getConf('attack_cd');
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */