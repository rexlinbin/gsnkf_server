<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $itemId$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/Chariot.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-12 11:16:32 +0000 (Tue, 12 Jul 2016) $
 * @version $Revision: 251288 $
 * @brief 
 *  
 **/
class Chariot implements IChariot
{
	private $uid;
	public function __construct()
	{
		if (!ChariotUtil::isChariotOpen())
		{
			throw new FakeException('chariot not opem');
		}
		$this->uid=RPCContext::getInstance()->getUid();
	}
	
	/**
	 * 装备战车
	 * (non-PHPdoc)
	 * @see IChariot::equip()
	 */
	public function equip($pos,$itemId)
	{
		return ChariotLogic::equip($pos,$itemId, $this->uid);
	}
	
	public function unequip($pos,$itemId)
	{
		return ChariotLogic::unequip($pos,$itemId, $this->uid);
	}
	
	/**
	 * 强化战车
	 */
	public function enforce($itemId,$addLv=1)
	{
		return ChariotLogic::enforce($itemId,$addLv,$this->uid);
	}
	
	
	/**
	 * 分解战车
	 */
	public function resolve($itemArr)
	{
		return $this->doResolve($itemArr);
	}
	public function previewResolve($itemArr)
	{
		return $this->doResolve($itemArr,true);
	}
	private function doResolve($itemArr,$preview=false)
	{
		return ChariotLogic::resolve($itemArr,$this->uid,$preview);
	}
	
	/**
	 * 重生战车
	 */
	public function reborn($itemId)
	{
		return $this->doReborn($itemId);
	}
	public function previewReborn($itemId)
	{
		return $this->doReborn($itemId,true);
	}
	private function doReborn($itemId,$preview=false)
	{
		return ChariotLogic::reborn($itemId,$this->uid,$preview);
	}
	
	/**
	 * 战车进阶
	 * @see IChariot::develop()
	 */
	public function develop($itemId)
	{
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */