<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Union.class.php 182847 2015-07-08 07:09:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/Union.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-07-08 07:09:39 +0000 (Wed, 08 Jul 2015) $
 * @version $Revision: 182847 $
 * @brief 
 *  
 **/
class Union implements IUnion
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
		
		if (EnSwitch::isSwitchOpen(SwitchDef::UNION) == false)
		{
			throw new FakeException('user:%d does not open the union system', $this->uid);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUnion::getInfoByLogin()
	 */
	public function getInfoByLogin()
	{
		return UnionLogic::getInfoByLogin($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUnion::getInfo()
	 */
	public function getInfo()
	{
		return UnionLogic::getInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IUnion::fill()
	 */
	public function fill($id, $aimId, $isHero = 1, $type = 0)
	{
		if (!isset(UnionDef::$TYPE_TO_CONFNAME[$type]))
		{
			throw new FakeException('invalid para, type:%d', $type);
		}
		$confname = UnionDef::$TYPE_TO_CONFNAME[$type];
		$conf = btstore_get()->$confname;
		if (empty($conf[$id]))
		{
			throw new FakeException('invalid para, id:%d', $id);
		}
		return UnionLogic::fill($this->uid, $id, $aimId, $isHero, $type);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */