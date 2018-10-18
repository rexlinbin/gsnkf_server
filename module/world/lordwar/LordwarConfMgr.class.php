<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LordwarConfMgr.class.php 154111 2015-01-21 07:32:27Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/LordwarConfMgr.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-01-21 07:32:27 +0000 (Wed, 21 Jan 2015) $
 * @version $Revision: 154111 $
 * @brief 
 *  
 **/
class LordwarConfMgr
{
	private $confInfo ;
	private $offset ;
	
	static $instance = null;
	
	private static $gFiled = '';
	
	/**
	 * @return LordwarConfMgr
	 */
	public static function getInstance($field = LordwarField::INNER)
	{
		
		if ( !isset( self::$instance ))
		{
			self::$instance = new self($field);
		}
		return self::$instance;
	}
	
	public static function release( $sess )
	{
		if (isset( self::$instance ))
		{
			unset( self::$instance );
		}
	}
	
	function __construct($field)
	{
		if( !isset( $this->confInfo ) )
		{
			$this->confInfo = array();
		}
		
		self::$gFiled = $field;
		if( self::$gFiled == LordwarField::CROSS )
		{
			$activityConf = ActivityConfLogic::getConf4Backend(ActivityName::LORDWAR, 0);
		}
		else
		{
			$activityConf = EnActivity::getConfByName(ActivityName::LORDWAR);
		}
		
/* 		$curTime = Util::getTime();
		if( $activityConf['start_time'] > $curTime || $activityConf['end_time'] < $curTime )
		{
			throw new FakeException( 'now not lordwar time, conf: %s', $activityConf );
		} */
		//XXX
		
		
		$this->confInfo = $activityConf;
		Logger::debug('all conf are: %s', $this->confInfo);
	}
	
	public function getSess()
	{
		if( empty( $this->confInfo['data'][1]['sess'] ) )
		{
			return 0;
		}
		if($this->confInfo['data'][1]['sess'] <= 0)
		{
			throw new ConfigException( 'invalid sess' );
		}
		return  $this->confInfo['data'][1]['sess'];
	}
	
	public function getConf($fieldName)
	{
		if( isset( $this->confInfo['data'][1][$fieldName] ) )
		{
			return $this->confInfo['data'][1][$fieldName];//TODO 1的问题，现在应该是没有了的
		}
		return array();
	}

	public function getBaseConf($field)
	{
		if ( !empty($this->confInfo[$field]) )
		{
			Logger::debug('base conf are: %s', $this->confInfo[$field]);
			return $this->confInfo[$field];
		}
		return 0;
	}
	
	public function getRound( $stamp = NULL )
	{
		$round = LordwarRound::OUT_RANGE;
		if(empty( $stamp ))
		{
			$stamp = Util::getTime();
		}
		
		$baseTimestamp = $this->getBaseConf('start_time');
		$baseEndTime = $this->getBaseConf( 'end_time' );
		if( $stamp < $baseTimestamp || $stamp > $baseEndTime || $baseTimestamp == 0 || $baseEndTime == 0 )
		{
			Logger::debug('not in valid round');
			return $round;
		}
		
		$baseTimestamp = floor( $baseTimestamp/60 ) * 60;
		Logger::debug( 'basetimestamp: %d', $baseTimestamp );
		
		$startTimeArr = $this->getConf( 'startTimeArr' );
		foreach ( $startTimeArr as $roundIndex => $offsetArr )
		{
			$roundStartTime = $baseTimestamp + 86400 * $offsetArr[0] + $offsetArr[1];
			Logger::debug('round: %d starttime: %d',$roundStartTime, $roundStartTime );
			if( $stamp > $roundStartTime )
			{
				$round = $roundIndex;
			}
		}
		
		return $round;
	}
	
	
	public function getRoundStartTime( $round )
	{
		if($round == LordwarRound::OUT_RANGE || $round == LordwarRound::BLANK)
		{
			throw new InterException( 'meaningless round %d', $round );
		}
		
		$baseTimestamp = $this->getBaseConf('start_time');
		$baseTimestamp = floor( $baseTimestamp/60 ) * 60;
		
		$startTimeArr = $this->getConf( 'startTimeArr' );
		
		$start = $baseTimestamp+86400 * $startTimeArr[$round][0]+$startTimeArr[$round][1];

		Logger::debug('round:%d start time is :%d, base: %d',$round,$start, $baseTimestamp);
		return $start;

	}
	
	

	public function getRegisterEnd()
	{
		$startTime = $this->getRoundStartTime(LordwarRound::REGISTER);
		$endTime = $startTime + $this->getConf('registerLastTime');
		
		return $endTime;
	}
	
	public function isRegisterTime($timeStamp = null)
	{
		if( !isset($timeStamp ) )
		{
			$timeStamp = Util::getTime();
		}
		
		$registerStartTime = $this->getRoundStartTime(LordwarRound::REGISTER);
		$registerEndTime = $this->getRegisterEnd();
		
		if( $timeStamp >= $registerStartTime && $timeStamp <= $registerEndTime )
		{
			return true;
		}
		
		return false;
	}
	
	public function isUpFmtTime()
	{
		return true;
		
	}
	
	public function getFmtCd($round, $status)
	{
		$upTimeArr = self::getConf( 'upFmtCdArr' );
		return 	$upTimeArr[LordwarRound::INNER_AUDITION];
		
		//后期修改的，cd时间确定了
		$upTimeArr = self::getConf( 'upFmtCdArr' );
		if( $round <= LordwarRound::INNER_AUDITION && $status < LordwarStatus::FIGHTEND )
		{
			return 	$upTimeArr[LordwarRound::INNER_AUDITION];
		}
		
		if( $status >= LordwarStatus::FIGHTEND )
		{
			$nextRound = LordwarUtil::getNextRound($round);
			return $upTimeArr[$nextRound];
		}
		else
		{
			return $upTimeArr[$round];
		}
		
		
		
	}
	
	public function getClearCdGold()
	{
		return $this->getConf('clrCdGold');
	}
	
	public function getAuditionOutLoseNum( $field )
	{
		$loseNumArr = $this->getConf( 'loseNumArr' );
		
		if( $field == LordwarField::INNER )
		{
			return $loseNumArr[0];
		}
		return $loseNumArr[1];
	}
	
	public function getAuditionBreakTime()
	{
		return $this->getConf( 'subRoundGapAudition' );
	}
	
	public function getPromotionSleepTime($round,$subRound)
	{
		$subRoundGap = $this->getConf('subRoundGapCross');
		$startTime = $this->getRoundStartTime($round);
		$thisSubRoundEndTime = $startTime + ($subRound+1) * $subRoundGap;
		$curTime = time();
		$sleepTime = $thisSubRoundEndTime - $curTime;
		$sleepTime =  $sleepTime > 0? $sleepTime:0;
		
		Logger::debug('$subRoundGap: %s, $startTime:%s,$thisSubRoundEndTime:%s,$sleepTime %s',$subRoundGap,$startTime,$thisSubRoundEndTime,$sleepTime);
		return $sleepTime;
	}
	
	public function getPromotionOutLoseNum( $field )
	{
		if( $field == LordwarField::INNER )
		{
			return 3;
		}
		
		if( $field == LordwarField::CROSS )
		{
			return 3;
		}
		
		//给了个默认值
		return 3;
	}
	
	/**
	 * get lordwar reward array 
	 * 
	 * @param int $rewardType		LordwarReward::SUPPORT/PROMOTION
	 * @param string $stage			inner/cross
	 * @param int $teamType			LordwarTeamType::WIN/LOSE
	 * @param int $rank				rank
	 * 
	 * @throws ConfigException
	 * 
	 * @return array				Reward array @see module Reward
	 * 
	 */
	public function getReward($rewardType, $stage, $teamType=NULL, $rank=NULL)
	{
		if ( $rewardType == LordwarReward::SUPPORT )
		{
			$rewardConf = $this->getConf("supportPrize");
			if ( empty($rewardConf[$stage]) )
			{
				throw new ConfigException("invalid lordwar support reward config starge:%s",
					$rewardType, $stage);
			}
			$rewardId = $rewardConf[$stage];
		}
		else if ( $rewardType == LordwarReward::RPOMOTION )
		{
			$rewardConf = $this->getConf("lordPrize");
			if ( empty($rewardConf[$stage][$teamType][$rank]) )
			{
				throw new ConfigException("invalid promotion reward: stage:%s teamType:%d, rank:%d",
					$stage, $teamType, $rank);
			}
			$rewardId = $rewardConf[$stage][$teamType][$rank];
		}
		else if ( $rewardType == LordwarReward::WHOLEWORLD )
		{
			$rewardId = $this->getConf("worldPrize");	
		}
		else
		{
			throw new InterException('not found rewardId. rewardType:%d, stage:%s, teamType:%d, rank:%d', 
						$rewardType, $stage, $teamType, $rank);
		}

		return self::getRewardById($rewardId);
	}
	
	private function getRewardById($rewardId)
	{
		if ( !isset(btstore_get()->LORDWAR_REWARD[$rewardId]) )
		{
			throw new ConfigException("invalid lordwar reward id:%d", $rewardId);
			
		}
		else
		{
			return btstore_get()->LORDWAR_REWARD[$rewardId]['reward'];
		}
	}
	
	/**
	 * 
	 * @param int $round	round @see LordwarRound
	 * 
	 * @return string		inner/cross
	 */
	public static function getStageByRound($round)
	{
		if ( $round < LordwarRound::CROSS_AUDITION )
		{
			return LordwarField::INNER;
		}
		else
		{
			return LordwarField::CROSS;
		}
	}
	
	
	public function getWorshipPrizeArr()
	{
		return self::getConf('worshipPrizeArr');
	}
	
	public function getWorshipCostArr()
	{
		return self::getConf( 'worshipCostArr' );
	}
	
	public function getWorshipRewardById($prizeId)
	{
		return $this->getRewardById($prizeId);
	}
	
	
	public function getSupportCostBase()
	{
		return self::getConf('supportCostBase');
	}
	
	public function getRegisterLevel()
	{
		return self::getConf( 'registerLv' );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */