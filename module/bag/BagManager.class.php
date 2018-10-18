<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BagManager.class.php 75344 2013-11-18 07:31:13Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/BagManager.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-11-18 07:31:13 +0000 (Mon, 18 Nov 2013) $
 * @version $Revision: 75344 $
 * @brief
 *
 **/

class BagManager
{
	/**
	 *
	 * ItemManager实例
	 * @var ItemManager
	 */
	private static $m_instance;

	/**
	 *
	 * 维护的m_bag的缓存
	 * @var array(m_bag)
	 */
	private $m_bag = array();

	/**
	 *
	 * 私有构造函数
	 */
	private function __construct(){}

	/**
	 *
	 *  得到ItemManager实例
	 *
	 *  @return BagManager
	 */
	public static function getInstance()
    {
		if(self::$m_instance == null)
		{
			self::$m_instance = new BagManager();
		}
		return self::$m_instance;
	}

	/**
	 *
	 * 得到bag对象
	 * @param int $uid
	 *
	 * @return Bag
	 */
	public function getBag($uid = 0)
	{
		if ( $uid == 0 || $uid == RPCContext::getInstance()->getUid() )
		{
			$uid = RPCContext::getInstance()->getUid();
			if ( $uid == 0 )
			{
				Logger::FATAL('invalid user!uid=0');
				throw new Exception('fake');
			}

			if ( !isset($this->m_bag[$uid]) )
			{
				$this->m_bag[$uid] = new Bag();
			}
		}

		if ( !isset($this->m_bag[$uid]) )
		{
			$this->m_bag[$uid] = new BagOther($uid);
		}

		return $this->m_bag[$uid];
	}
	
	public function release($uid)
	{
		if( isset($this->m_bag[$uid]) )
		{
			unset($this->m_bag[$uid]);
			return;
		}
	}
	
	public function setBag($uid, $bagObj)
	{		
		if( isset($this->m_bag[$uid]) )
		{
			Logger::debug('bag of uid:%d already in manager', $uid);
			return;
		}
		Logger::debug('setBag. uid:%d', $uid);
		$this->m_bag[$uid] = $bagObj;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */