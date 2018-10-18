<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FsReborn.class.php 200753 2015-09-28 06:20:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/fsreborn/FsReborn.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-28 06:20:54 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200753 $
 * @brief 
 *  
 **/
class FsReborn implements IFsReborn
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
		if (!EnActivity::isOpen(ActivityName::FSREBORN)) 
		{
			throw new FakeException('fsreborn is not open');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFsReborn::getInfo()
	 */
	public function getInfo()
	{
		Logger::trace('FsReborn::getInfo Start.');
		
		$ret = FsRebornLogic::getInfo($this->uid);
		
		Logger::trace('FsReborn::getInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFsReborn::reborn()
	 */
	public function reborn($itemId)
	{
		Logger::trace('FsReborn::reborn Start.');
		
		$itemId = intval($itemId);
		if ($itemId <= 0)
		{
			throw new FakeException('invalid itemId:%d', $itemId);
		}
		$ret = FsRebornLogic::reborn($this->uid, $itemId);
		
		Logger::trace('FsReborn::reborn End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */