<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Bowl.class.php 152191 2015-01-13 10:02:48Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/Bowl.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-13 10:02:48 +0000 (Tue, 13 Jan 2015) $
 * @version $Revision: 152191 $
 * @brief 
 *  
 **/
 
class Bowl implements IBowl
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/* (non-PHPdoc)
	 * @see IPass::getInfo()
	*/
	public function getBowlInfo()
	{
		return BowlLogic::getBowlInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IPass::buy()
	*/
	public function buy($type)
	{
		$type = intval($type);
		if ($type <= 0 || $type > BowlConf::BOWL_TYPE_NUM)
		{
			throw new FakeException('invalid param type:%d', $type);
		}
		
		return BowlLogic::buy($this->uid, $type);
	}
	
	/* (non-PHPdoc)
	 * @see IPass::receive()
	*/
	public function receive($type, $day)
	{
		$type = intval($type);
		if ($type <= 0 || $type > BowlConf::BOWL_TYPE_NUM) 
		{
			throw new FakeException('invalid param type:%d', $type);
		}
		
		$day = intval($day);
		$limitDay = BowlLogic::getRewardDayNum($type);
		
		if ($day <= 0 || $day > $limitDay) 
		{
			throw new FakeException('invalid param day:%d', $day);
		}
		
		return BowlLogic::receive($this->uid, $type, $day);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */