<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChariotTest.test.php 251359 2016-07-13 03:46:21Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/test/ChariotTest.test.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-13 03:46:21 +0000 (Wed, 13 Jul 2016) $
 * @version $Revision: 251359 $
 * @brief 
 *  
 **/
class ChariotTest extends PHPUnit_Framework_TestCase
{
	private $uid = 0;
	private $inst=NULL;
	private $itemIdArr=array();
	protected function setUp()
	{
		$this->uid = 20002;
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		$this->openChariotSwitch();
		$console = new Console();
		$console->gold(10000);
		$console->silver(10000000);
		$this->inst=new Chariot();
		$bag=BagManager::getInstance()->getBag($this->uid);
		$bag->addItemByTemplateID(920101, 3);
		$bag->addItemByTemplateID(920201, 1);
		$bag->addItemByTemplateID(60002, 100);
		$this->itemIdArr=$bag->getItemIdsByTemplateID(920101);
		parent::setUp ();
	}
	
	
	/**
	 * @group getEquipChariotInfo
	 */
	public function test_getEquipChariotInfo()
	{
		var_dump($this->inst->getEquipChariotInfo());
	}
	
	
	/**
	 * @group equip
	 */
	public function test_equip()
	{
		foreach ($this->itemIdArr as $itemId)
		{
			var_dump($this->inst->equip(1,$itemId));
		}
		
	}

	/**
	 * @group unEquip
	 */
	public function test_unEquip()
	{
		foreach ($this->itemIdArr as $itemId)
		{
			var_dump($this->inst->unEquip(1,$itemId));
		}
	}
	
	/**
	 * @group enforce
	 */
	public function test_enforce()
	{
		foreach ($this->itemIdArr as $itemId)
		{
			var_dump($this->inst->enforce($itemId));
		}
	}
	
	/**
	 * @group resolve
	 */
	public function test_resolve()
	{
		var_dump($this->inst->resolve($this->itemIdArr));
	}
	
	/**
	 * @group reborn
	 */
	public function test_reborn()
	{
		foreach ($this->itemIdArr as $itemId)
		{
			var_dump($this->inst->reborn($itemId));
		}
	}
	/**
	 * @group addAttr
	 */
	public function test_addAttr()
	{
		var_dump(EnChariot::getAddAttrByChariot($this->uid));
	}
	/**
	 * @group addSkill
	 */
	public function test_addSkill()
	{
		var_dump(EnChariot::getChariotSkill($this->uid));
	}
	
	public function openChariotSwitch()
	{
		$console = new Console();
		$switchId=SwitchDef::CHARIOT;
		$openConf = btstore_get()->SWITCH[$switchId];
		$needLv    =    $openConf['openLv'];
		$needBase  =    $openConf['openNeedBase'];
		if(!empty($needLv))
		{
			$console->level($needLv);
		}
		if(!empty($needBase))
		{
			$baseId    =    intval($needBase);
			$copyId = btstore_get()->BASE[$baseId]['copyid'];
			$copyId = intval($copyId);
			$console->passNCopies($copyId,BaseLevel::SIMPLE);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */