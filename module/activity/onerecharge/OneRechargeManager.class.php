<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OneRechargeManager.class.php 248900 2016-06-30 02:11:06Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/OneRechargeManager.class.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-06-30 02:11:06 +0000 (Thu, 30 Jun 2016) $
 * @version $Revision: 248900 $
 * @brief 单充回馈数据管理类
 *
 **/
class OneRechargeManager
{
	private $uid;
	private $data = array();
	private $dataBak = array();
	private static $Instance = array();

	private function __construct($uid = 0)
	{
		$this->uid = $uid;
		$this->data = OneRechargeDao::getInfo($this->uid);
		if (empty($this->data))
		{
			$this->initData();
		}

		// 先刷新数据再备份
		$this->refreshData();
		$this->dataBak = $this->data;
		Logger::debug('uid:%d OneRechargeManager data:%s', $this->uid, $this->data);
	}
	/**
	 * @param $uid
	 * @throws FakeException
	 * @return OneRechargeManager
	 */
	public static function getInstance($uid)
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

	public function initData()
	{
		$this->data = array(
				OneRechargeDef::UID => $this->uid,
				OneRechargeDef::REFRESH_TIME => Util::getTime(),
				OneRechargeDef::IF_REMAIN => OneRechargeDef::NOT_REMAIN,
				OneRechargeDef::VA_INFO => array(OneRechargeDef::VA_REWARD => array()),
		);
	}

	public function refreshData()
	{
		$conf =  EnActivity::getConfByName(ActivityName::ONERECHARGE);
		if ($this->data[OneRechargeDef::REFRESH_TIME] < $conf['start_time'])
		{
			$this->data[OneRechargeDef::REFRESH_TIME] = Util::getTime();
			$this->data[OneRechargeDef::IF_REMAIN] = OneRechargeDef::NOT_REMAIN;
			$this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD] = array();
		}
	}

	public function getHadRewardInfo($rewardId = 0)
	{
		if (0 == $rewardId)
		{
			return $this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD];
		}
		if (empty($this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD][$rewardId]))
		{
			return array();
		}
		return $this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD][$rewardId];
	}

	/**
	 * 废弃。
	 * 原来全部领取时的方法。现在有全部领取和N选1领取，改成了recordReward方法
	 * @param unknown $rewardId
	 */
	public function recordRewardInfo($rewardId, $num)
	{
		if (empty($this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD][$rewardId]))
		{
			$this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD][$rewardId] = $num;
		}
		else
		{
			$this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD][$rewardId] += $num;
		}
	}
	/**
	 * data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD]中存着：
	 * [
	 *     $rewardId0=>{
	 *                    0 => $select0，
	 *                    1 => $select1,
	 *                    ...//每领取一次就多一条记录
	 *                 },
	 *     $rewardId1=>{
	 *
	 *                 },
	 *     ...//每领取新的不同种类的充值类型奖励就多一条记录，比如3000是第一条记录，600是第二条记录
	 * ]
	 * @param int $rewardId
	 * @param int $select
	 */
	public function recordReward($rewardId, $select=0)
	{
	    $this->data[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD][$rewardId][] = $select;
	}
	public function getRemainStatus()
	{
		return $this->data[OneRechargeDef::IF_REMAIN];
	}

	public function changeRemainStatus($status)
	{
		$this->data[OneRechargeDef::IF_REMAIN] = $status;
	}

	public function update()
	{
		if ($this->data != $this->dataBak)
		{
			OneRechargeDao::update($this->uid, $this->data);
			$this->dataBak = $this->data;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */