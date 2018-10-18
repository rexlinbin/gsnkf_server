<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPass.class.php 178710 2015-06-13 05:18:43Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/WorldPass.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-13 05:18:43 +0000 (Sat, 13 Jun 2015) $
 * @version $Revision: 178710 $
 * @brief 
 *  
 **/
 
class WorldPass implements IWorldPass
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		if (!EnSwitch::isSwitchOpen(SwitchDef::WORLDPASS)) 
		{
			throw new FakeException('switch not open!');
		}
		
		if (!WorldPassConf::$MY_SWITCH) 
		{
			throw new FakeException('my switch not open.');
		}
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::getWorldPassInfo()
	 */
	public function getWorldPassInfo()
	{
		return WorldPassLogic::getWorldPassInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::attack()
	*/
	public function attack($stage, $arrFormation)
	{
		if ($stage <= 0 || $stage > WorldPassConf::STAGE_COUNT) 
		{
			throw new FakeException('invalid stage[%d], stage count[%d]', $stage, WorldPassConf::STAGE_COUNT);
		}
		
		if (count($arrFormation) <= 0 || count($arrFormation) > FormationDef::FORMATION_SIZD) 
		{
			throw new FakeException('invalid formation[%s]', $arrFormation);
		}
		
		foreach ($arrFormation as $index => $aHtid)
		{
			if ($aHtid == 0) 
			{
				unset($arrFormation[$index]);
			}
			else 
			{
				$arrFormation[$index] = intval($aHtid);
			}
		}
		
		return WorldPassLogic::attack($this->uid, $stage, $arrFormation);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::reset()
	 */
	public function reset()
	{
		return WorldPassLogic::reset($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::addAtkNum()
	*/
	public function addAtkNum()
	{
		return WorldPassLogic::addAtkNum($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::getMyTeamInfo()
	*/
	public function getMyTeamInfo()
	{
		return WorldPassLogic::getMyTeamInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::getRankList()
	*/
	public function getRankList()
	{
		return WorldPassLogic::getRankList($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::refreshHeros()
	*/
	public function refreshHeros()
	{
		return WorldPassLogic::refreshHeros($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::getShopInfo()
	*/
	public function getShopInfo()
	{
		return WorldPassLogic::getShopInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IWorldPass::buyGoods()
	*/
	public function buyGoods($goodsId, $num)
	{
		if (!isset(btstore_get()->WORLD_PASS_GOODS[$goodsId]))
		{
			throw new InterException('no config of goods[%d]', $goodsId);
		}
		
		return WorldPassLogic::buyGoods($this->uid, $goodsId, $num);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */