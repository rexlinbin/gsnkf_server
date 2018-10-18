<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyRoulette.class.php 166807 2015-04-10 10:35:40Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/roulette/MyRoulette.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-04-10 10:35:40 +0000 (Fri, 10 Apr 2015) $
 * @version $Revision: 166807 $
 * @brief 
 *  
 **/
class MyRoulette
{
	private $uid = NULL;
	private $buffer = NULL;
	private $rouletteInfo = NULL;
	
	private static $_instance = NULL;
	
	private function __construct($uid=0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
			
			if (empty($uid))
			{
				throw new FakeException('uid in session is null.');
			}
		}
		
		$this->uid = $uid;
		$rouletteInfo = RouletteDao::getRouletteInfo($uid, RouletteDef::$ALL_TABLE_FIELD);
		$this->rouletteInfo = $rouletteInfo;
		$this->buffer = $rouletteInfo;
		$this->rfrRouletteInfo();
	}
	
	public function getUid()
	{
		return $this->uid;
	}
	
	public static function getInstance($uid=0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		
		if ( (NULL == self::$_instance) || (self::$_instance->getUid() != $uid ) )
		{
			self::$_instance = new self($uid);
		}
		
		return self::$_instance;
	}
	
	public static function release()
	{
		if (self::$_instance != NULL)
		{
			self::$_instance = NULL;
		}
	}
	
	public function getRouletteInfo()
	{
		return $this->rouletteInfo;
	}
	
	public function rfrRouletteInfo()
	{
		//如果当前积分轮盘信息为空或者活动开后第一次进（刷新时间比此次活动开启的时间早）就所有数据都初始化
 		if (empty($this->rouletteInfo) 
 				|| ($this->rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] < RouletteLogic::getActStartTime()))
 		{
			if (empty($this->rouletteInfo))
			{
				$init = TRUE;
				Logger::info('User %d enter activity roulette firstly! Init rouletteInfo.',$this->uid);
			}
			else
			{
				$init = FALSE;
				Logger::info('Activity roulette open! Start to reset rouletteInfo of user %d.',$this->uid);
			}
			
			$this->rouletteInfo[RouletteDef::SQL_FIELD_UID] = $this->getUid();
			$this->rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM] = 0;
			$this->rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM] = 0;
			$this->rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM] = 0;
			$this->rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL] = 0;
			$this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD] = array('arrRewarded' => array());
			$this->rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime();
			$this->rouletteInfo[RouletteDef::SQL_LAST_ROLL_TIME] = 0;
			
			if ($init)
			{
				RouletteDao::insertRouletteInfo($this->rouletteInfo);
				$this->buffer = $this->rouletteInfo;
			}
			
			return ;
 		}
		
		if (FALSE == Util::isSameDay($this->rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME]))
		{
			Logger::trace('This user %d firstly enter this act today. Reset rouletteInfo.',$this->uid);
			$this->rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime();
			$this->rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM] = 0;
		}
	}
	
	public function getDayFreeRouletteNum()
	{
		return $this->rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM];
	}
	
	public function getAccumFreeRouletteNum()
	{
		return $this->rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM];
	}
	
	public function getAccumGoldRouletteNum()
	{
		return $this->rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM];
	}
	
	public function getRouletteIntegeral()
	{
		return $this->rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL];
	}
	
	public function rouletteFreeOnce()
	{
		$this->rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM] += 1;
		$this->rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM] += 1;
	}
	
	public function rouletteGoldOnce()
	{
		$this->rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM] += 1;
	}
	
	public function addIntegeral($integeral)
	{
		$this->rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL] += $integeral;
	}
	
	public function updateRollTime()
	{
		$this->rouletteInfo[RouletteDef::SQL_LAST_ROLL_TIME] = Util::getTime();
	}
	
	public function receiveRankReward()
	{
		$this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED] = Util::getTime();
	}
	
	/*
	public function getBoxIntegeralInfo()
	{
		return $this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD];
	}
	*/
	
	/*
	public function changeBoxIntegeralInfo($boxInfo)
	{
		$this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD] = $boxInfo;
	}
	*/
	
	public function getArrRewarded()
	{
		return $this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD]['arrRewarded'];
	}
	
	public function receiveReward($index)
	{
		$index = intval($index);
		if ( $index < 1 )
		{
			throw new FakeException('invalid index:%d', $index);
		}
		
		if ( in_array( $index, $this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD]['arrRewarded']) )
		{
			throw new FakeException('already get reward. index:%d', $index);
		}
		
		$curIntegeral = $this->getRouletteIntegeral();
		$maxRewardIndex = RouletteLogic::getBoxNum( $curIntegeral );
		if ( $index > $maxRewardIndex )
		{
			throw new FakeException('intergeral not enough. intergeral:%d, index:%d', $curIntegeral, $index);
		}
		
		$this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD]['arrRewarded'][] = $index;
	}
	
	public function save()
	{
		if (!empty($this->rouletteInfo) && ($this->rouletteInfo != $this->buffer))
		{
			RouletteDao::updateRouletteInfo($this->uid, $this->rouletteInfo);
			$this->buffer = $this->rouletteInfo;
		}
	}
	
	public function initRouletteInfo()
	{
		$this->rouletteInfo[RouletteDef::SQL_FIELD_UID] = $this->getUid();
		$this->rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM] = 0;
		$this->rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM] = 0;
		$this->rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM] = 0;
		$this->rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL] = 0;
		$this->rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD] = array('arrRewarded' => array());
		$this->rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime();
		$this->rouletteInfo[RouletteDef::SQL_LAST_ROLL_TIME] = 0;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */