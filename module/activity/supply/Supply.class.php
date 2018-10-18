<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Supply.class.php 66906 2013-09-27 09:56:15Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/supply/Supply.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-27 09:56:15 +0000 (Fri, 27 Sep 2013) $
 * @version $Revision: 66906 $
 * @brief 
 *  
 **/
class Supply implements ISupply
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
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISupply::getSupplyInfo()
	 */
	public function getSupplyInfo()
	{
		Logger::trace('Supply::getSupplyInfo Start.');
		
		$ret = SupplyLogic::getSupplyInfo($this->uid);
		
		Logger::trace('Supply::getSupplyInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ISupply::supplyExecution()
	 */
	public function supplyExecution()
	{
		Logger::trace('Supply::supplyExecution Start.');
		
		$ret = SupplyLogic::supplyExecution($this->uid);
		
		Logger::trace('Supply::supplyExecution End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */