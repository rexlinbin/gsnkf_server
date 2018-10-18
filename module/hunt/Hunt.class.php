<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Hunt.class.php 218124 2015-12-28 07:59:38Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hunt/Hunt.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-12-28 07:59:38 +0000 (Mon, 28 Dec 2015) $
 * @version $Revision: 218124 $
 * @brief 
 *  
 **/
class Hunt implements IHunt
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
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == false)
		{
			throw new FakeException('user:%d does not open the hunt system', $this->uid);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHunt::getHuntInfo()
	 */
	public function getHuntInfo()
	{
		Logger::trace('Hunt::getHuntInfo Start.');
		
		$ret = HuntLogic::getHuntInfo($this->uid);
		
		Logger::trace('Hunt::getHuntInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHunt::skip()
	 */
	public function skip($type = 0)
	{
		Logger::trace('Hunt::skip Start.');
		
		if (!in_array($type, HuntDef::$VALID_SKIP_TYPES)) 
		{
			throw new FakeException('Err para, type:%d', $type);
		}
		
		$ret = HuntLogic::skip($this->uid, $type);
		
		Logger::trace('Hunt::skip End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHunt::skipHunt()
	 */
	public function skipHunt($num = 10)
	{
		Logger::trace('Hunt::skipHunt Start.');
		
		if ($num <= 0 || $num > 10)
		{
			throw new FakeException('Err para, num:%d', $num);
		}
		
		$ret = HuntLogic::skipHunt($this->uid, $num);
		
		Logger::trace('Hunt::skipHunt End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHunt::huntSoul()
	 */
	public function huntSoul($num = 1)
	{
		Logger::trace('Hunt::huntSoul Start.');
		
		if ($num <= 0)
		{
			throw new FakeException('Err para, num:%d', $num);
		}
		
		$ret = HuntLogic::huntSoul($this->uid, $num);
		
		Logger::trace('Hunt::huntSoul End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHunt::rapidHunt()
	 */
	public function rapidHunt($type, $arrQuality = array())
	{
		Logger::trace('Hunt::rapidHunt Start.');
		
		if ($type <= 0 || !is_array($arrQuality))
		{
			throw new FakeException('Err para, type:%d quality:%d', $type, $arrQuality);
		}
		
		$ret = HuntLogic::rapidHunt($this->uid, $type, $arrQuality);
		
		Logger::trace('Hunt::rapidHunt End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */