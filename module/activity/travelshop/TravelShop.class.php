<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TravelShop.class.php 198501 2015-09-15 02:17:01Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/TravelShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-15 02:17:01 +0000 (Tue, 15 Sep 2015) $
 * @version $Revision: 198501 $
 * @brief 
 *  
 **/
class TravelShop implements ITravelShop
{
	/**
	 * 用户id
	 * @var $uid
	 */
	private $uid;
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if (!EnActivity::isOpen(ActivityName::TRAVELSHOP)) 
		{
			throw new FakeException('travel shop is not open');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ITravelShop::getInfo()
	 */
	public function getInfo()
	{
		Logger::trace('TravelShop::getInfo Start.');
		
		$ret = TravelShopLogic::getInfo($this->uid);
		
		Logger::trace('TravelShop::getInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ITravelShop::buy()
	 */
	public function buy($goodsId, $num)
	{
		Logger::trace('TravelShop::buy Start.');
		
		if (empty($goodsId) || $num <= 0) 
		{
			throw new FakeException('invalid goodsId:%d num:%d', $goodsId, $num);
		}
		$ret = TravelShopLogic::buy($this->uid, $goodsId, $num);
		
		Logger::trace('TravelShop::buy End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ITravelShop::getPayback()
	 */
	public function getPayback($id)
	{
		Logger::trace('TravelShop::getPayback Start.');
		
		if (empty($id))
		{
			throw new FakeException('invalid id:%d', $id);
		}
		$ret = TravelShopLogic::getPayback($this->uid, $id);
		
		Logger::trace('TravelShop::getPayback End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ITravelShop::getReward()
	 */
	public function getReward($id)
	{
		Logger::trace('TravelShop::getReward Start.');
		
		if (empty($id))
		{
			throw new FakeException('invalid id:%d', $id);
		}
		$ret = TravelShopLogic::getReward($this->uid, $id);
		
		Logger::trace('TravelShop::getReward End.');
		
		return $ret;
	}
	
	public function addTimer()
	{
		TravelShopLogic::addTimer();
	}
	
	public function reward()
	{
		RPCContext::getInstance()->asyncExecuteTask('travelshop.rewardUser', array());
	}
	
	public function rewardUser()
	{
		TravelShopLogic::rewardUser();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */