<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGiftManager.class.php 208547 2015-11-10 11:19:26Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/RechargeGiftManager.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-10 11:19:26 +0000 (Tue, 10 Nov 2015) $
 * @version $Revision: 208547 $
 * @brief 
 *  
 **/

/**
 * Class RechargeGiftManager
 * <code>
 * $data:array
 * {
 *  'uid' => int,
 *  'update_time' => int 最后一次领奖时间,
 *  'va_reward' => array
 *  	{
 *  		$rewardId:int => $select:int 奖励档位:$rewardId中的第$select个选项,$select为0时表示非选择类型都领取完奖励
 *  	}
 * }
 * </code>
 *  之所以要存奖励档位中的选项id,是为了记录完整的领奖信息
 */

class RechargeGiftManager
{
	private $uid = NULL;
	private $data = array();
	private $dataModify = array();
	private static $Instance = array();
	
	private function __construct($uid)
	{
		// 由于把构造置为private方法只在getInstant里用,所以不在这重复判断$uid为0的情况
		$this->uid = $uid;
		$this->dataModify = RechargeGiftDao::getAllInfo($uid);
		if (empty($this->dataModify))
		{
			$this->initData();
		}
		// 检查如果是新一轮活动则刷新数据
		$this->refreshData();
		// 先刷新再备份	
		$this->data = $this->dataModify;
		Logger::debug('uid:%d RechargeGiftManager dataModify:%s', $this->uid, $this->dataModify);
	}
	
	public function initData()
	{
		$this->dataModify = array(
						RechargeGiftDef::UID => $this->uid,
						RechargeGiftDef::UPDATE_TIME => Util::getTime(),
						RechargeGiftDef::VA_REWARD => array(),
				);
		RechargeGiftDao::insert($this->dataModify);
	}
	/**
	 * 用于活动新一轮刷新数据
	 */
	public function refreshData()
	{
		$startTime = RechargeGiftUtil::getConfStartTime();
		// 数据库记录的最后一次更新时间小于活动开始时间时,意味着新一轮活动开始,则重置累dataModify
		if ($this->dataModify[RechargeGiftDef::UPDATE_TIME] >= $startTime)
		{
			return ;
		}
		$this->dataModify = array(
				RechargeGiftDef::UID => $this->uid,
				RechargeGiftDef::UPDATE_TIME => Util::getTime(),
				RechargeGiftDef::VA_REWARD => array(),
		);
	}
	
	/**
	 * @param $uid
	 * @return RechargeGiftManager 对象
	 */
	public static function getInstance($uid = 0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
			if (empty($uid))
			{
				throw new FakeException("the uid in session is null");
			}
		}
		if (empty(self::$Instance[$uid]))
		{
			self::$Instance[$uid] = new self($uid);
		}
		
		return self::$Instance[$uid];
	}
	/**
	 * 只在单例测试中为了保证上一个测试点的数值不影响下一个测试,才调用
	 * @param unknown $uid
	 */
	public static function release($uid)
	{
		if (isset(self::$Instance[$uid]))
		{
			unset(self::$Instance[$uid]);
		}
	}
	
	public function isObtainReward($rewardId)
	{
		$type = RechargeGiftUtil::getRewardType($rewardId);
		$hadRewardArr = array_keys($this->dataModify[RechargeGiftDef::VA_REWARD]);
		if (in_array($rewardId, $hadRewardArr))
		{
			return true;
		}
		return false;
	}
	
	public function recordReward($rewardId, $select = 0)
	{
		// 虽然这个函数只在发奖时调用（已经检查了重复领奖）,但是为了保证接口的功能性的完整如果真的出现重复记录奖励id则不允许
		if ($this->isObtainReward($rewardId))
		{
			Logger::warning("had record rewardId:%d, cant record!", $rewardId);
			return ;
		}
		$this->dataModify[RechargeGiftDef::VA_REWARD][$rewardId] = $select;
	}
		
	public function getRewardArr()
	{
		$rewardArr = array();
		$rewardArr = array_keys($this->dataModify[RechargeGiftDef::VA_REWARD]);
		Logger::debug('getRewardArr:%s', $rewardArr);
		return $rewardArr;
	}
	
	public function update()
	{
		if ($this->data != $this->dataModify)
		{
			$this->dataModify[RechargeGiftDef::UPDATE_TIME] = Util::getTime();
			RechargeGiftDao::update($this->uid, $this->dataModify);
			$this->data = $this->dataModify;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */