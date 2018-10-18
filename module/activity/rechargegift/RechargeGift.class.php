<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RechargeGift.class.php 206954 2015-11-03 12:14:35Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/rechargegift/RechargeGift.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-03 12:14:35 +0000 (Tue, 03 Nov 2015) $
 * @version $Revision: 206954 $
 * @brief 
 *  
 **/
class RechargeGift implements IRechargeGift
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if (empty($this->uid))
		{
			throw new FakeException('the uid in session is null');
		}
		if (!EnActivity::isOpen(ActivityName::RECHARGEGIFT))
		{
			throw new FakeException('activity rechargeGift is not opened');
		}
	}
	
	public function getInfo()
	{
		Logger::trace('ChargeGift getInfo begin, uid:%d', $this->uid);
		$ret = RechargeGiftLogic::getInfo($this->uid);
		Logger::trace('ChargeGift getInfo end, uid:%d', $this->uid);
		return $ret;
	}
	
	public function obtainReward($rewardId, $select = 0)
	{
		Logger::trace('ChargeGift obtainReward begin, uid:%d and rewardId:%d and selectId:%d', $this->uid, $rewardId, $select);
		$ret = RechargeGiftLogic::obtainReward($this->uid, $rewardId, $select);
		Logger::trace('ChargeGift obtainReward end, uid:%d and rewardId:%d and selectId:%d', $this->uid, $rewardId, $select);
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */