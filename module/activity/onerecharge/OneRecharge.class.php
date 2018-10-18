<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OneRecharge.class.php 248900 2016-06-30 02:11:06Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/OneRecharge.class.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-06-30 02:11:06 +0000 (Thu, 30 Jun 2016) $
 * @version $Revision: 248900 $
 * @brief 单充回馈入口类
 *
 **/
class OneRecharge implements IOneRecharge
{
	public function __construct()
	{
		if (!EnActivity::isOpen(ActivityName::ONERECHARGE))
		{
			throw new FakeException('activity oneRecharge is not opened');
		}
	}

	public function getInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (empty($uid))
		{
			throw new FakeException('the uid in session is null');
		}
		Logger::trace('OneRecharge getInfo start, uid:%d', $uid);
		$ret = OneRechargeLogic::getInfo($uid);
		Logger::trace('OneRecharge getInfo end, uid:%d', $uid);
		return $ret;
	}

	public function gainReward($rewardId, $select=0)
	{
	    $rewardId = intval($rewardId);
	    $select = intval($select);
		$uid = RPCContext::getInstance()->getUid();
		if (empty($uid))
		{
			throw new FakeException('the uid in session is null');
		}
		Logger::trace('OneRecharge gainReward start, uid:%d', $uid);
		$ret = OneRechargeLogic::gainReward($uid, $rewardId, $select);
		Logger::trace('OneRecharge gainReward end, uid:%d', $uid);
		return $ret;
	}

	public function rewardToCenter()
	{
		Util::asyncExecute('onerecharge.doReward', array());
	}

	public function doReward()
	{
		OneRechargeLogic::doReward();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */