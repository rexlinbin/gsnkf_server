<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HappySign.class.php 207395 2015-11-05 03:24:12Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/happysign/HappySign.class.php $
 * @author $Author: JiexinLin $(linjiexin@babeltime.com)
 * @date $Date: 2015-11-05 03:24:12 +0000 (Thu, 05 Nov 2015) $
 * @version $Revision: 207395 $
 * @brief 
 *  
 **/
class HappySign implements IHappySign
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if (empty($this->uid))
		{
			throw new FakeException('the uid in session is null');
		}
		if (!EnActivity::isOpen(ActivityName::HAPPYSIGN))
		{
			throw new FakeException('activity happySign is not opened');
		}
	}
	
	public function getSignInfo()
	{
		Logger::trace('HappySign getSignInfo begin, uid:%d', $this->uid);
		$ret = HappySignLogic::getSignInfo($this->uid);
		Logger::trace('HappySign getSignInfo end, uid:%d', $this->uid);
		return $ret;
	}
	
	public function gainSignReward($rewardId, $select = 0)
	{
		Logger::trace('HappySign gainSignReward begin, uid:%d rewardId:%d selectId:%d', $this->uid, $rewardId, $select);
		$ret = HappySignLogic::gainSignReward($this->uid, $rewardId, $select);
		Logger::trace('HappySign gainSignReward end, uid:%d rewardId:%d selectId:%d', $this->uid, $rewardId, $select);
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */