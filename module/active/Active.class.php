<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Active.class.php 219059 2016-01-04 07:53:29Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/active/Active.class.php $
 * @author $Author: JiexinLin $(tianming@babeltime.com)
 * @date $Date: 2016-01-04 07:53:29 +0000 (Mon, 04 Jan 2016) $
 * @version $Revision: 219059 $
 * @brief 
 *  
 **/
class Active implements IActive
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
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ACTIVE) == false)
		{
			throw new FakeException('user:%d does not open the active system', $this->uid);
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see IActive::getActiveInfo()
	 */
	public function getActiveInfo()
	{
		Logger::trace('Active::getActiveInfo Start.');

		$ret = ActiveLogic::getActiveInfo($this->uid);

		Logger::trace('Active::getActiveInfo End.');
		
		return $ret;
	}

	public function getTaskPrize($taskId)
	{
		Logger::trace('Active::getTaskPrize Start.');
		
		if ($taskId <= 0)
		{
			throw new FakeException('Err para, taskId:%d', $taskId);
		}
		
		$ret = ActiveLogic::getTaskPrize($this->uid, $taskId);
		
		Logger::trace('Active::getTaskPrize End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActive::getPrize()
	 */
	public function getPrize($prizeId)
	{
		Logger::trace('Active::getPrize Start.');

		if ($prizeId <= 0)
		{
			throw new FakeException('Err para, prizeId:%d', $prizeId);
		}

		$ret = ActiveLogic::getPrize($this->uid, $prizeId);

		Logger::trace('Active::getPrize End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActive::upgrade()
	 */
	public function upgrade()
	{
		Logger::trace('Active::upgrade Start.');
		
		$ret = ActiveLogic::upgrade($this->uid);
		
		Logger::trace('Active::upgrade End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */