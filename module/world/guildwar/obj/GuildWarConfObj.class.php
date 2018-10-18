<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarConfObj.class.php 158607 2015-02-12 04:03:58Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/obj/GuildWarConfObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-12 04:03:58 +0000 (Thu, 12 Feb 2015) $
 * @version $Revision: 158607 $
 * @brief 
 *  
 **/
 
class GuildWarConfObj
{
	/**
	 * 唯一实例
	 * @var GuildWarConfObj
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
	 * @return GuildWarConfObj
	 */
	public static function getInstance($field = GuildWarField::INNER)
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
		if ($field == GuildWarField::CROSS)
		{
			$activityConf = ActivityConfLogic::getConf4Backend(ActivityName::GUILDWAR, 0);
		}
		else
		{
			$activityConf = EnActivity::getConfByName(ActivityName::GUILDWAR);
		}
		
		$this->mVersion = $activityConf['version'];
		$this->mStartTime = floor($activityConf['start_time'] / 60) * 60;
		$this->mEndTime = floor($activityConf['end_time'] / 60) * 60;
		$this->mNeedOpenTime = floor($activityConf['need_open_time'] / 60) * 60;
		
		$maxId = 0;
		$arrData = $activityConf['data'];
		foreach ($arrData as $id => $data)
		{
			if ($id > $maxId) 
			{
				$maxId = $id;
			}
		}
		
		if (empty($maxId))
		{
			if ( $this->mStartTime > 0 )
			{
				throw new ConfigException('GuildWarConfObj.construct failed, no data in activityConf[%s]', $activityConf);
			}
			Logger::info('GuildWarConfObj.construct failed, empty activityConf[%s]', $activityConf);
		}
		else
		{
			$this->mConf = $arrData[$maxId];
		}  
		Logger::trace('GuildWarConfObj cur session[%d], cur conf[%s]', $this->getSession(), $this->mConf);
	}
	
	public function getActivityVersion()
	{
		return $this->mVersion;
	}
	
	public function getActivityStartTime()
	{
		return $this->mStartTime;
	}
	
	public function getActivityEndTime()
	{
		return $this->mEndTime;
	}
	
	public function getActivityNeedOpenTime()
	{
		return $this->mNeedOpenTime;
	}
	
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
	
	public function getConf($field)
	{
		return $this->mConf[$field];
	}
	
	public function getSession()
	{
		if (!$this->isValid()) 
		{
			return 0;
		}
		
		if (isset($this->mConf[GuildWarCsvTag::SESSION])) 
		{
			return $this->getConf(GuildWarCsvTag::SESSION);
		}
		
		return $this->getConf(GuildWarCsvTag::ID);
	}
	
	public function getRound($time)
	{
		if (!$this->isValid($time)) 
		{
			return GuildWarRound::INVALID;
		}
		
		$arrTimeConfig = $this->getTimeConfig();
		
		$ret = GuildWarRound::IDLE;
		foreach ($arrTimeConfig as $round => $aConfig)
		{
			if ($time >= ($aConfig[0] + $this->mStartTime))
			{
				$ret = $round;
			}
		}
		
		Logger::trace('GuildWarConfObj.getRound, arrTimeConfig[%s], currTime[%d], startTime[%d], retRound[%d]', $arrTimeConfig, $time, $this->mStartTime, $ret);
		return $ret;
	}
	
	public function getCurRound()
	{
		return $this->getRound(time());
	}
	
	public function getSubRound($time)
	{
		$round = $this->getRound($time);
		if ($round <= GuildWarRound::AUDITION)
		{
			return 0;
		}
		
		$roundStartTime = $this->getRoundStartTime($round);
		$interval = $this->getFinalsGap();
		$subRoundCount = $this->getSubRoundCount();
		Logger::trace('GuildWarConfObj.getSubRound, time[%s], round[%d], roundStartTime[%s], finalsGap[%d], subRoundCount[%d]', strftime('%Y%m%d-%H%M%S', $time), $round, strftime('%Y%m%d-%H%M%S', $roundStartTime), $interval, $subRoundCount);
		
		for ($i = 1; $i <= $subRoundCount; ++$i)
		{
			if ($time < $roundStartTime + $i * $interval) 
			{
				return $i;
			}
		}
				
		return PHP_INT_MAX;
	}
	
	public function getCurSubRound()
	{
		return $this->getSubRound(time());
	}
	
	public function getRoundStartTime($round)
	{
		if (!in_array($round, GuildWarRound::$ValidRound))
		{
			throw new FakeException('GuildWarConfObj.getRoundStartTime failed, invalid stage[%d], all valid stage[%s]', $round, GuildWarRound::$ValidRound);
		}
		
		$arrTimeConfig = $this->getTimeConfig();
		return $arrTimeConfig[$round][0] + $this->mStartTime;
	}
	
	public function getPreRoundStartTime($round)
	{
		return $this->getRoundStartTime($this->getPreRound($round));
	}
	
	public function getRoundEndTime($round)
	{
		if (!in_array($round, GuildWarRound::$ValidRound))
		{
			throw new FakeException('GuildWarConfObj.getRoundEndTime failed, invalid stage[%d], all valid stage[%s]', $round, GuildWarRound::$ValidRound);
		}
		
		$arrTimeConfig = $this->getTimeConfig();
		return $arrTimeConfig[$round][1] + $this->mStartTime;
	}
	
	public function getPreRoundEndTime($round)
	{
		return $this->getRoundEndTime($this->getPreRound($round));
	}
	
	public function getSubRoundStartTime($round, $subRound)
	{
		return $this->getRoundStartTime($round) + ($subRound - 1) * $this->getFinalsGap();
	}
	
	public function getSignUpStartTime()
	{
		return $this->getRoundStartTime(GuildWarRound::SIGNUP);
	}
	
	public function getSignUpEndTime()
	{
		return $this->getRoundEndTime(GuildWarRound::SIGNUP);
	}
	
	public function inSignUpTime($time = 0)
	{
		if ($time == 0)
		{
			$time = Util::getTime();
		}
	
		$signUpStartTime = $this->getSignUpStartTime();
		$signUpEndTime = $this->getSignUpEndTime();
		if ($time >= $signUpStartTime && $time <= $signUpEndTime)
		{
			return TRUE;
		}
		return FALSE;
	}
	
	public function getRankReward($rank, $isCandidate)
	{
		if (!isset($rank, GuildWarConf::$ValidRank))
		{
			throw new InterException('GuildWarConfObj.getRankReward failed, invalid rank[%d], all valid rank[%s]', $rank, GuildWarConf::$ValidRank);
		}
	
		$arrReward = array();
		if ($isCandidate)
		{
			$arrReward = $this->getCandidatePrize();
		}
		else
		{
			$arrReward = $this->getNotCandidatePrize();
		}
		
		$prizeId = $arrReward[GuildWarConf::$Rank2PrizeIndex[$rank]];
		if (!isset(btstore_get()->GUILDWAR_REWARD[$prizeId]))
		{
			throw new ConfigException('GuildWarConfObj.getRankReward failed, no prizeId[%d] in GUILDWAR_REWARD', $prizeId);
		}
		
		return btstore_get()->GUILDWAR_REWARD[$prizeId]->toArray();
	}
	
	public function getTimeConfig()
	{
		return $this->getConf(GuildWarCsvTag::TIME_CONFIG);
	}
	
	public function getDefaultMaxWinTimes()
	{
		return $this->getConf(GuildWarCsvTag::DEFAULT_WIN_TIME);
	}
	
	public function getBuyMaxWinCost($curBuyWinNum)
	{
		$arrCost = $this->getConf(GuildWarCsvTag::BUY_WIN_TIME_COST);
		
		$maxCount = $this->getMaxBuyWinCount();
		if ($curBuyWinNum >= $maxCount) 
		{
			return $arrCost[$maxCount - 1];
		}
		
		return $arrCost[$curBuyWinNum];
	}
	
	public function getMaxBuyWinCount()
	{
		return count($this->getConf(GuildWarCsvTag::BUY_WIN_TIME_COST));
	}
	
	public function getNeedLevel()
	{
		return $this->getConf(GuildWarCsvTag::NEED_LEVEL);
	}
	
	public function getNeedMemberCount()
	{
		return $this->getConf(GuildWarCsvTag::NEED_MEMBER_COUNT);
	}
	
	public function getFailNum()
	{
		return $this->getConf(GuildWarCsvTag::FAIL_NUM);
	}
	
	public function getAuditionGap()
	{
		return $this->getConf(GuildWarCsvTag::AUDITION_GAP);
	}
	
	public function getFinalsGap()
	{
		return $this->getConf(GuildWarCsvTag::FINALS_GAP);
	}
	
	public function getAuditionUpdCd()
	{
		$arrCdConf = $this->getConf(GuildWarCsvTag::CD);
		return $arrCdConf[GuildWarCsvTag::AUDITION_UPD_CD];
	}
	
	public function getAuditionUpdLimit()
	{
		$arrCdConf = $this->getConf(GuildWarCsvTag::CD);
		return $arrCdConf[GuildWarCsvTag::AUDITION_UPD_LIMIT];
	}
	
	public function getFinalsUpdCd()
	{
		$arrCdConf = $this->getConf(GuildWarCsvTag::CD);
		return $arrCdConf[GuildWarCsvTag::FINALS_UPD_CD];
	}
	
	public function getFinalsUpdLimit()
	{
		$arrCdConf = $this->getConf(GuildWarCsvTag::CD);
		return $arrCdConf[GuildWarCsvTag::FINALS_UPD_LIMIT];
	}
	
	public function getFinalsTeamUpdLimit()
	{
		$arrCdConf = $this->getConf(GuildWarCsvTag::CD);
		return $arrCdConf[GuildWarCsvTag::FINALS_TEAM_UPD_LIMIT];
	}
	
	public function getFinalsTeamUpdCd()
	{
		$arrCdConf = $this->getConf(GuildWarCsvTag::CD);
		return $arrCdConf[GuildWarCsvTag::FINALS_TEAM_UPD_CD];
	}
	
	public function getCandidatesCount()
	{
		return $this->getConf(GuildWarCsvTag::CANDIDATES_COUNT);
	}
	
	public function getSubRoundCount()
	{
		return intval(ceil($this->getCandidatesCount() / GuildWarConf::ONE_TIME_PLAYER));
	}
	
	public function getNextSubRound($subRound)
	{
		$subRoundCount = $this->getSubRoundCount();
		if ($subRound >= $subRoundCount) 
		{
			return $subRound;
		}
		
		return ++$subRound;
	}
	
	public function getPreSubRound($subRound)
	{
		if ($subRound <= 0) 
		{
			return $subRound;
		}
		
		return --$subRound;
	}
	
	public function getNextRound($round)
	{
		if ($round >= GuildWarRound::ADVANCED_2)
		{
			return GuildWarRound::ADVANCED_2;
		}
		return $round + 1;
	}
	
	public function getPreRound($round)
	{
		if ($round <= GuildWarRound::INVALID)
		{
			return GuildWarRound::INVALID;
		}
		return $round - 1;
	}
	
	public function getCandidatePrize()
	{
		return $this->getConf(GuildWarCsvTag::CANDIDATES_PRIZE);
	}
	
	public function getNotCandidatePrize()
	{
		return $this->getConf(GuildWarCsvTag::NOT_CANDIDATES_PRIZE);
	}
	
	public function getCheerBaseCost()
	{
		return $this->getConf(GuildWarCsvTag::CHEER_BASE_COST);
	}
	
	public function getCheerPrize($round)
	{
		$cheerPrizeId = $this->getConf(GuildWarCsvTag::CHEER_PRIZE);
		if (!isset(btstore_get()->GUILDWAR_REWARD[$cheerPrizeId]))
		{
			throw new ConfigException('GuildWarConfObj.getCheerPrize failed, no prizeId[%d] in GUILDWAR_REWARD', $cheerPrizeId);
		}
		
		return btstore_get()->GUILDWAR_REWARD[$cheerPrizeId]->toArray();
	}
	
	public function getAllServerPrize()
	{
		$allServerPrizeId = $this->getConf(GuildWarCsvTag::ALL_SERVER_PRIZE);
		if (!isset(btstore_get()->GUILDWAR_REWARD[$allServerPrizeId]))
		{
			throw new ConfigException('GuildWarConfObj.getAllServerPrize failed, no prizeId[%d] in GUILDWAR_REWARD', $allServerPrizeId);
		}
		
		return btstore_get()->GUILDWAR_REWARD[$allServerPrizeId]->toArray();
	}
	
	public function getWorshipPrize($type)
	{
		if (!in_array($type, GuildWarWorshipType::$ALL_TYPE))
		{
			throw new FakeException('GuildWarConfObj.getWorshipPrize failed, not valid worship type[%d], valid type[%s]', $type, GuildWarWorshipType::$ALL_TYPE);
		}
		
		$arrPrize = $this->getConf(GuildWarCsvTag::WORSHIP_PRIZE);
		if (!isset($arrPrize[$type])) 
		{
			throw new ConfigException('GuildWarConfObj.getWorshipPrize failed, no prize type[%d], valid type[%d]', $type, array_keys($arrPrize));
		}
		
		$prizeId = $arrPrize[$type];
		if (!isset(btstore_get()->GUILDWAR_REWARD[$prizeId]))
		{
			throw new ConfigException('GuildWarConfObj.getWorshipPrize failed, no prizeId[%d] in GUILDWAR_REWARD', $prizeId);
		}
		
		return btstore_get()->GUILDWAR_REWARD[$prizeId]->toArray();
	}
	
	public function getClearCdBaseCost()
	{
		return $this->getConf(GuildWarCsvTag::CLEAR_CD_BASE_COST);
	}
	
	public function getCheerLimit()
	{
		return $this->getConf(GuildWarCsvTag::CHEER_LIMIT);
	}
	
	public function getWorshipCost($type)
	{
		if (!in_array($type, GuildWarWorshipType::$ALL_TYPE)) 
		{
			throw new ConfigException('GuildWarConfObj.getWorshipCost failed, not valid worship type[%d], valid type[%s]', $type, GuildWarWorshipType::$ALL_TYPE);
		}
		
		$arrCost = $this->getConf(GuildWarCsvTag::WORSHIP_COST);
		return $arrCost[$type];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */