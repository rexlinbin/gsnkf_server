<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Stylish.class.php 242914 2016-05-16 08:08:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/Stylish.class.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-16 08:08:39 +0000 (Mon, 16 May 2016) $
 * @version $Revision: 242914 $
 * @brief 
 *  
 **/
class Stylish implements IStylish
{
	private $uid;

	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		if (EnSwitch::isSwitchOpen(SwitchDef::STYLISH) == false)
		{
			throw new FakeException('user:%d does not open the stylish system', $this->uid);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStylish::getStylishInfo()
	 */
	public function getStylishInfo()
	{
		Logger::trace('Stylish::getStylishInfo start.');
		
		return StylishLogic::getStylishInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStylish::activeTitle()
	 */
	public function activeTitle($id, $itemId, $itemNum)
	{
		Logger::trace('Stylish::activeTitle start. id:%d, itemId:%d, itemNum:%d', $id, $itemId, $itemNum);
		
		if ($id <= 0 || $itemId <= 0 || $itemNum <= 0)
		{
			throw new FakeException('Err para, id:%d, itemId:%d, itemNum:%d', $id, $itemId, $itemNum);
		}
		
		return StylishLogic::activeTitle($this->uid, $id, $itemId, $itemNum);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStylish::setTitle()
	 */
	public function setTitle($id)
	{
		Logger::trace('Stylish::setTitle start. id:%d.', $id);
		
		if ($id <= 0) 
		{
			throw new FakeException('Err para, id:%d', $id);
		}
		
		return StylishLogic::setTitle($this->uid, $id);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */