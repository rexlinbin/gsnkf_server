<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySignManager.class.php 232026 2016-03-10 08:34:26Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/HappySignManager.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2016-03-10 08:34:26 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232026 $
 * @brief 
 *  
 **/
/**
 * Class HappySignManager
 * <code>
 * $data:array
 * {
 *  'uid' => int,
 *  'sign_time' => int 签到时间 即当天第一次登陆的时间,
 *  'login_num' => int 活动期间内累计登陆天数,
 *  'va_reward' => array
 *  	{
 *  		$rewardId:int => $select:int 奖励档位:$rewardId中的第$select个选项,$select为0时表示非选择类型都领取完奖励
 *  	}
 * }
 * </code>
 *
 *  之所以要存奖励档位中的选项id,是为了记录完整的领奖信息
 */

class HappySignManager
{
	private $uid;
	private $data = array();
	private $dataModify = array();
	private static $Instance = array();
	
	private function __construct($uid = 0)
	{
		// 由于把构造置为private方法只在getInstant里用,所以不在这重复判断$uid为0的情况
		$this->uid = $uid;
		$this->dataModify = HappySignDao::getInfo($this->uid);
		if (empty($this->dataModify))
		{
			$this->initData();
		}
		
		// 先刷新数据再备份
		$this->refreshData();
		$this->data = $this->dataModify;
		Logger::debug('uid:%d HappySignManager dataModify:%s', $this->uid, $this->dataModify);
	}
	
	public function initData()
	{
		$this->dataModify = array(
					HappySignDef::UID => $this->uid,
					HappySignDef::FIRST_LOGIN_TIME => Util::getTime(),	
					HappySignDef::LOGIN_NUM => 1, 
					HappySignDef::VA_REWARD => array(),
			);
		HappySignDao::insert($this->dataModify);
	}
	
	public function refreshData()
	{
		$needUpdate = false;
		// 如果是新一轮活动,则重置奖励数组和登陆天数
		$conf =  EnActivity::getConfByName(ActivityName::HAPPYSIGN);
		if ( $this->dataModify[HappySignDef::FIRST_LOGIN_TIME] < $conf['start_time'] )
		{
			$this->dataModify[HappySignDef::VA_REWARD] = array();
			$this->dataModify[HappySignDef::LOGIN_NUM] = 0;
			$needUpdate = true;
		}
		// 如果当天第一次登陆,则更新签到时间
		if (!Util::isSameDay($this->dataModify[HappySignDef::FIRST_LOGIN_TIME]))
		{
			$this->dataModify[HappySignDef::FIRST_LOGIN_TIME] = Util::getTime();
			$this->addSignNum();
			$needUpdate = true;
		}
		if ($needUpdate)
		{
			$this->update();
		}
	}
	
	/**
	 * @param  $uid
	 * @return HappySignManager
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
	 * 获取领过的奖励信息
	 * @return 奖励数组
	 */
	public function getRewardInfo()
	{
		$rewardArr = array();
		$rewardArr = array_keys($this->dataModify[HappySignDef::VA_REWARD]);
		Logger::debug('getRewardInfo:%s', $rewardArr);
		return $rewardArr;
	}
	
	public function isObtainReward($rewardId)
	{
		$type = HappySignUtil::getRewardType($rewardId);
		$hadRewardArr = array_keys($this->dataModify[HappySignDef::VA_REWARD]);
		if (in_array($rewardId, $hadRewardArr))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * 领取奖励
	 * @param unknown $rewardId
	 */
	public function recordReward($rewardId, $select = 0)
	{
		// 虽然这个函数只在发奖时调用（已经检查了重复领奖）,但是为了保证接口的功能性的完整如果真的出现重复记录奖励id则不允许
		if ($this->isObtainReward($rewardId))
		{
			Logger::warning("had record rewardId:%d, cant record!", $rewardId);
			return ;
		}
		$this->dataModify[HappySignDef::VA_REWARD][$rewardId] = $select;
	}
	
	/**
	 * 得到活动期间内的签到天数信息
	 */
	public function getSignNum()
	{
		return $this->dataModify[HappySignDef::LOGIN_NUM];
	}
	/**
	 * 增加签到天数
	 */
	public function addSignNum()
	{
		++$this->dataModify[HappySignDef::LOGIN_NUM];
	}
	
	public function update()
	{
		if ($this->data != $this->dataModify)
		{
			HappySignDao::update($this->uid, $this->dataModify);
			$this->data = $this->dataModify;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */