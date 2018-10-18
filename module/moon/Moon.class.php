<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Moon.class.php 245659 2016-06-06 09:29:05Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/Moon.class.php $
 * @author $Author: QingYao $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-06 09:29:05 +0000 (Mon, 06 Jun 2016) $
 * @version $Revision: 245659 $
 * @brief 
 *  
 **/
 
class Moon implements IMoon
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if (!EnSwitch::isSwitchOpen(SwitchDef::MOON)) 
		{
			throw new FakeException('switch not open!');
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::getMoonInfo()
	 */
	public function getMoonInfo()
	{
		return MoonLogic::getMoonInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::attackMonster()
	 */
	public function attackMonster($copyId, $gridId)
	{
		$allCopyConf = btstore_get()->MOON_COPY->toArray();
		if (!array_key_exists($copyId, $allCopyConf)) 
		{
			throw new InterException('no config of copy id[%d], all conf[%s]', $copyId, array_keys($allCopyConf));
		}
		
		if ($gridId <= 0 || $gridId > MoonConf::MAX_GRID_NUM) 
		{
			throw new InterException('invalid grid id[%d], max grid num[%d]', $gridId, MoonConf::MAX_GRID_NUM);
		}
		
		return MoonLogic::attackMonster($this->uid, $copyId, $gridId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::openBox()
	 */
	public function openBox($copyId, $gridId)
	{
		$allCopyConf = btstore_get()->MOON_COPY->toArray();
		if (!array_key_exists($copyId, $allCopyConf))
		{
			throw new InterException('no config of copy id[%d], all conf[%s]', $copyId, array_keys($allCopyConf));
		}
		
		if ($gridId <= 0 || $gridId > MoonConf::MAX_GRID_NUM)
		{
			throw new InterException('invalid grid id[%d], max grid num[%d]', $gridId, MoonConf::MAX_GRID_NUM);
		}
		
		return MoonLogic::openBox($this->uid, $copyId, $gridId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::attackBoss()
	 */
	public function attackBoss($copyId,$nightmare = MoonTypeDef::BOSS_NORMAL_TYPE)
	{
		$allCopyConf = btstore_get()->MOON_COPY->toArray();
		if (!array_key_exists($copyId, $allCopyConf))
		{
			throw new InterException('no config of copy id[%d], all conf[%s]', $copyId, array_keys($allCopyConf));
		}
		//梦魇模式功能节点检查
		if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE && false == EnSwitch::isSwitchOpen(SwitchDef::TALLY))
		{
			throw new InterException('NightmareCopy:%s can not attack.tally switch not open.', $copyId);
		}
		return MoonLogic::attackBoss($this->uid, $copyId,$nightmare);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::addAttackNum()
	 */
	public function addAttackNum($nightmare = MoonTypeDef::BOSS_NORMAL_TYPE)
	{
		//梦魇模式功能节点检查
		if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE && false == EnSwitch::isSwitchOpen(SwitchDef::TALLY))
		{
			throw new InterException('NightmareAttack num can not buy.tally switch not open.');
		}
		return MoonLogic::addAttackNum($this->uid,$nightmare);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::buyBox()
	 */
	public function buyBox()
	{
		return MoonLogic::buyBox($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::getShopInfo()
	 */
	public function getShopInfo()
	{
		return MoonLogic::getShopInfo($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::buyGoods()
	 */
	public function buyGoods($goodsId)
	{
		if (!isset(btstore_get()->MOON_GOODS[$goodsId])) 
		{
			throw new InterException('no config of goods[%d]', $goodsId);
		}
		
		return MoonLogic::buyGoods($this->uid, $goodsId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::refreshGoodsList()
	 */
	public function refreshGoodsList()
	{
		return MoonLogic::refreshGoodsList($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::buyBingfu()
	 */
	public function buyTally($goodsId, $num = 1)
	{
		if (!isset(btstore_get()->BINGFU_SHOP[$goodsId]))
		{
			throw new InterException('bingfushop no config of goods[%d]', $goodsId);
		}
	
		return MoonLogic::buyTally($this->uid, $goodsId, $num);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::getBingfuInfo()
	 */
	public function getTallyInfo()
	{
		return MoonLogic::getTallyInfo($this->uid);
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::refreshTallyGoodsList()
	 */
	public function refreshTallyGoodsList()
	{
		return MoonLogic::refreshTallyGoodsList($this->uid);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IMoon::sweep($nightmare)
	 */
	public function sweep($nightmare = MoonTypeDef::BOSS_NORMAL_TYPE)
	{
		return MoonLogic::sweep($this->uid, $nightmare);
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */