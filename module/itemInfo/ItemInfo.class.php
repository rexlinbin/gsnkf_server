<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemInfo.class.php 250248 2016-07-06 09:32:12Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/itemInfo/ItemInfo.class.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief
 *
 **/

class ItemInfo implements IItemInfo
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
	 * @see IItemInfo::getArmBook()
	 */
	public function getArmBook($uid = 0)
	{
		Logger::trace('ItemInfo::getArmBook Start.');
		
		if (empty($uid)) 
		{
			$uid = $this->uid;
		}
		$ret = ItemInfoLogic::getArmBook($uid);
		
		Logger::trace('ItemInfo::getArmBook End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IItemInfo::getTreasBook()
	 */
	public function getTreasBook($uid = 0)
	{
		Logger::trace('ItemInfo::getTreasBook Start.');
	
		if (empty($uid))
		{
			$uid = $this->uid;
		}
		$ret = ItemInfoLogic::getTreasBook($uid);
	
		Logger::trace('ItemInfo::getTreasBook End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IItemInfo::getGodWeaponBook()
	 */
	public function getGodWeaponBook($uid = 0)
	{
		Logger::trace('ItemInfo::getGodWeaponBook Start.');
		
		if (empty($uid))
		{
			$uid = $this->uid;
		}
		$ret = ItemInfoLogic::getGodWeaponBook($uid);
		
		Logger::trace('ItemInfo::getGodWeaponBook End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IItemInfo::getTallyBook()
	 */
	public function getTallyBook($uid = 0)
	{
		Logger::trace('ItemInfo::getTallyBook Start.');
	
		if (empty($uid))
		{
			$uid = $this->uid;
		}
		$ret = ItemInfoLogic::getTallyBook($uid);
	
		Logger::trace('ItemInfo::getTallyBook End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IItemInfo::getChariotBook()
	 */
	public function getChariotBook($uid=0)
	{
		Logger::trace('ItemInfo::getChariotBook Start.');
		
		if (empty($uid))
		{
			$uid = $this->uid;
		}
		$ret = ItemInfoLogic::getChariotBook($uid);
		
		Logger::trace('ItemInfo::getChariotBook End.');
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */