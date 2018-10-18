<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Forge.class.php 259922 2016-09-01 08:52:51Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/forge/Forge.class.php $
 * @author $Author: BaoguoMeng $(tianming@babeltime.com)
 * @date $Date: 2016-09-01 08:52:51 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259922 $
 * @brief 
 *  
 **/

class Forge implements IForge
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
	 * @see IForge::reinforce()
	 */
	public function reinforce($itemId, $level = 1)
	{
		Logger::trace('Forge::reinforce Start.');
		
		// 参数检查
		if(empty($itemId) || $level <= 0)
		{
			throw new FakeException('Err para itemId:%d, level:%d!', $itemId, $level);
		}
		
		$ret = ForgeLogic::reinforce($this->uid, $itemId, $level);
		
		Logger::trace('Forge::reinforce End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::autoReinforce()
	 */
	public function autoReinforce($itemId)
	{
		Logger::trace('Forge::autoReinforce Start.');
		
		// 参数检查
		if(empty($itemId))
		{
			throw new FakeException('Err para itemId:%d', $itemId);
		}
		
		$ret = ForgeLogic::autoReinforce($this->uid, $itemId);
		
		Logger::trace('Forge::autoReinforce End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::developArm()
	 */
	public function developArm($itemId, $itemIds)
	{
		Logger::trace('Forge::developArm Start.');
		
		// 参数检查
		$itemId = intval($itemId);
		foreach ($itemIds as $key => $value)
		{
			$itemIds[$key] = intval($value);
		}
		if($itemId <= 0 || empty($itemIds)
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
		
		$ret = ForgeLogic::developArm($this->uid, $itemId, $itemIds);
		
		Logger::trace('Forge::developArm End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::upgrade()
	 */
	public function upgrade($itemId, $itemIds, $arrNum = array())
	{
		Logger::trace('Forge::upgrade Start.');
		
		// 参数检查
		if($itemId <= 0 || empty($itemIds) 
		|| !is_array($itemIds) || in_array($itemId, $itemIds) 
		|| count($itemIds) > TreasureDef::TREASURE_UPGRADE_USE_LIMIT
		|| !empty($arrNum) && count($itemIds) != count($arrNum))
		{
			throw new FakeException('Err para itemId:%d, itemIds:%s, arrNum:%s!', $itemId, $itemIds, $arrNum);
		}
		
		$ret = ForgeLogic::upgrade($this->uid, $itemId, $itemIds, $arrNum);
		
		Logger::trace('Forge::upgrade End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::evolve()
	 */
	public function evolve($itemId, $itemIds)
	{
		Logger::trace('Forge::evolve Start.');
		
		// 参数检查
		if($itemId <= 0 || empty($itemIds) 
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
		
		$ret = ForgeLogic::evolve($this->uid, $itemId, $itemIds);
		
		Logger::trace('Forge::evolve End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::develop()
	 */
	public function develop($itemId, $itemIds)
	{
		Logger::trace('Forge::develop Start.');
	
		// 参数检查
		if($itemId <= 0 || empty($itemIds)
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
	
		$ret = ForgeLogic::develop($this->uid, $itemId, $itemIds);
	
		Logger::trace('Forge::develop End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::promote()
	 */
	public function promote($itemId, $itemIds)
	{
		Logger::trace('Forge::promote Start.');
		
		// 参数检查
		if($itemId <= 0
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
		
		if (empty($itemIds))
		{
			Logger::warning('promote itemIds is empty.');
		}
		
		$ret = ForgeLogic::promote($this->uid, $itemId, $itemIds);
		
		Logger::trace('Forge::promote End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::promoteByExp()
	 */
	public function promoteByExp($itemId, $addLevel)
	{
		Logger::trace('Forge::promoteByExp Start.');
	
		// 参数检查
		$itemId = intval($itemId);
		if($itemId <= 0 || $addLevel <= 0 || $addLevel > 5)
		{
			throw new FakeException('Err para itemId:%d, addLevel:%d!', $itemId, $addLevel);
		}
	
		$ret = ForgeLogic::promoteByExp($this->uid, $itemId, $addLevel);
	
		Logger::trace('Forge::promoteByExp End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::fightSoulDevelop()
	 */
	public function fightSoulDevelop($itemId, $itemIds)
	{
		Logger::trace('Forge::fightSoulDevelop Start.');
		
		// 参数检查
		if($itemId <= 0 || empty($itemIds)
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
		
		$ret = ForgeLogic::fightSoulDevelop($this->uid, $itemId, $itemIds);
		
		Logger::trace('Forge::fightSoulDevelop End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::fightSoulEvolve()
	 */
	public function fightSoulEvolve($itemId, $itemIds)
	{
		Logger::trace('Forge::fightSoulEvolve Start.');
		
		// 参数检查
		if($itemId <= 0 || empty($itemIds)
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
		
		$ret = ForgeLogic::fightSoulEvolve($this->uid, $itemId, $itemIds);
		
		Logger::trace('Forge::fightSoulEvolve End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::upgradeDress()
	 */
	public function upgradeDress($itemId)
	{
		Logger::trace('Forge::upgradeDress Start.');
	
		// 参数检查
		if($itemId <= 0)
		{
			throw new FakeException('Err para itemId:%d!', $itemId);
		}
	
		$ret = ForgeLogic::upgradeDress($this->uid, $itemId);
	
		Logger::trace('Forge::upgradeDress End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::upgradePocket()
	 */
	public function upgradePocket($itemId, $itemIds)
	{
		Logger::trace('Forge::upgradePocket Start.');
		
		// 参数检查
		if($itemId <= 0
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
		
		if (empty($itemIds))
		{
			Logger::warning('upgradePocket itemIds is empty.');
		}
		
		$ret = ForgeLogic::upgradePocket($this->uid, $itemId, $itemIds);
		
		Logger::trace('Forge::upgradePocket End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::upgradeTally()
	 */
	public function upgradeTally($itemId, $itemIds, $arrNum = array())
	{
		Logger::trace('Forge::upgradeTally Start.');
	
		// 参数检查
		if($itemId <= 0
		|| !is_array($itemIds) || in_array($itemId, $itemIds)
		|| !empty($arrNum) && count($itemIds) != count($arrNum))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s, arrNum:%s!', $itemId, $itemIds, $arrNum);
		}
	
		if (empty($itemIds))
		{
			Logger::warning('upgradeTally itemIds is empty.');
		}
	
		$ret = ForgeLogic::upgradeTally($this->uid, $itemId, $itemIds, $arrNum);
	
		Logger::trace('Forge::upgradeTally End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::developTally()
	 */
	public function developTally($itemId, $itemIds)
	{
		Logger::trace('Forge::developTally Start.');
	
		// 参数检查
		if($itemId <= 0
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
	
		if (empty($itemIds))
		{
			Logger::warning('developTally itemIds is empty.');
		}
	
		$ret = ForgeLogic::developTally($this->uid, $itemId, $itemIds);
	
		Logger::trace('Forge::developTally End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::evolveTally()
	 */
	public function evolveTally($itemId, $itemIds)
	{
		Logger::trace('Forge::evolveTally Start.');
	
		// 参数检查
		if($itemId <= 0
		|| !is_array($itemIds) || in_array($itemId, $itemIds))
		{
			throw new FakeException('Err para itemId:%d, $itemIds:%s!', $itemId, $itemIds);
		}
	
		if (empty($itemIds))
		{
			Logger::warning('evolveTally itemIds is empty.');
		}
	
		$ret = ForgeLogic::evolveTally($this->uid, $itemId, $itemIds);
	
		Logger::trace('Forge::evolveTally End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::randRefresh()
	 */
	public function randRefresh($itemId, $special)
	{
		Logger::trace('Forge::randRefresh Start.');
		
		// 参数检查
		if(empty($itemId))
		{
			throw new FakeException('Err para itemId:%d!', $itemId);
		}
		
		$ret = ForgeLogic::randRefresh($this->uid, $itemId, $special);
		
		Logger::trace('Forge::randRefresh End.');
		
		return $ret;	
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::randRefreshAffirm()
	 */
	public function randRefreshAffirm($itemId)
	{
		Logger::trace('Forge::randRefreshAffirm Start.');
		
		// 参数检查
		if(empty($itemId))
		{
			throw new FakeException('Err para itemId:%d!', $itemId);
		}
		
		$ret = ForgeLogic::randRefreshAffirm($this->uid, $itemId);
		
		Logger::trace('Forge::randRefreshAffirm End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::fixedRefresh()
	 */
	public function fixedRefresh($itemId, $type, $num = 1)
	{
		Logger::trace('Forge::fixedRefresh Start.');
		
		// 参数检查
		if($itemId <= 0 || !in_array($type, ForgeDef::$VALID_POTENCE_REFRESH_TYPES) 
		|| $num <= 0 || $num > 10)
		{
			throw new FakeException('Err para itemId:%d, type:%d, num:%d!', $itemId, $type, $num);
		}
		$ret = ForgeLogic::fixedRefresh($this->uid, $itemId, $type, $num);
		
		Logger::trace('Forge::fixedRefresh End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::fixedRefreshAffirm()
	 */
	public function fixedRefreshAffirm($itemId)
	{
		Logger::trace('Forge::fixedRefreshAffirm Start.');
	
		// 参数检查
		if($itemId <= 0)
		{
			throw new FakeException('Err para itemId:%d!', $itemId);
		}
	
		$ret = ForgeLogic::fixedRefreshAffirm($this->uid, $itemId);
	
		Logger::trace('Forge::fixedRefreshAffirm End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::getPotenceTransferInfo()
	 */
	public function getPotenceTransferInfo()
	{
		Logger::trace('Forge::getPotenceTransferInfo Start.');
		
		$ret = ForgeLogic::getPotenceTransferInfo($this->uid);
		
		Logger::trace('Forge::getPotenceTransferInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::potenceTransfer()
	 */
	public function potenceTransfer($srcItemId, $desItemId, $type)
	{
		Logger::trace('Forge::potenceTransfer Start.');
		
		// 参数检查
		if(empty($srcItemId) || empty($desItemId) || empty($type))
		{
			throw new FakeException('Err para srcItemId:%d, $desItemId:%d, type:%d!', $srcItemId, $desItemId, $type);
		}
		$ret = ForgeLogic::potenceTransfer($this->uid, $srcItemId, $desItemId, $type);
		
		Logger::trace('Forge::potenceTransfer End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::compose()
	 */
	public function compose($method, $itemId)
	{
		Logger::trace('Forge::compose Start.');
		
		// 参数检查
		if($method <= 0 || empty($itemId))
		{
			throw new FakeException('Err para method:%d, $itemId:%d!', $method, $itemId);
		}
		$ret = ForgeLogic::compose($this->uid, $method, $itemId);
		
		Logger::trace('Forge::compose End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::composeRune()
	 */
	public function composeRune($method, $arrItemId)
	{
		Logger::trace('Forge::composeRune Start.');
		
		foreach ($arrItemId as $key => $value)
		{
			$arrItemId[$key] = intval($value);
		}
		
		// 参数检查
		if($method <= 0 || empty($arrItemId))
		{
			throw new FakeException('Err para method:%d, arrItemId:%s!', $method, $arrItemId);
		}
		$ret = ForgeLogic::composeRune($this->uid, $method, $arrItemId);
		
		Logger::trace('Forge::composeRune End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::inlay()
	 */
	public function inlay($treasItemId, $runeItemId, $index, $resItemId = 0)
	{
		Logger::trace('Forge::inlay Start.');
		
		// 参数检查
		if(empty($treasItemId) || empty($runeItemId) || $index < 0 || $resItemId < 0)
		{
			throw new FakeException('Err para treasItemId:%d, runeItemId:%d, index:%d, resItemId!', $treasItemId, $runeItemId, $index, $resItemId);
		}
		$ret = ForgeLogic::inlay($this->uid, $treasItemId, $runeItemId, $index, $resItemId);
		
		Logger::trace('Forge::inlay End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::outlay()
	 */
	public function outlay($itemId, $index)
	{
		Logger::trace('Forge::outlay Start.');
		
		// 参数检查
		if(empty($itemId) || $index < 0)
		{
			throw new FakeException('Err para itemId:%d, index:%d!', $itemId, $index);
		}
		$ret = ForgeLogic::outlay($this->uid, $itemId, $index);
		
		Logger::trace('Forge::outlay End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::lock()
	 */
	public function lock($itemId)
	{
		Logger::trace('Forge::lock Start.');
		
		// 参数检查
		$itemId = intval($itemId);
		if($itemId <= 0)
		{
			throw new FakeException('Err para $itemId:%d!', $itemId);
		}
		$ret = ForgeLogic::lock($this->uid, $itemId);
		
		Logger::trace('Forge::lock End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::unlock()
	 */
	public function unlock($itemId)
	{
		Logger::trace('Forge::unlock Start.');
		
		// 参数检查
		$itemId = intval($itemId);
		if($itemId <= 0)
		{
			throw new FakeException('Err para $itemId:%d!', $itemId);
		}
		$ret = ForgeLogic::unlock($this->uid, $itemId);
		
		Logger::trace('Forge::unlock End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::transferTreasure()
	 */
	public function transferTreasure($itemId, $itemTplId)
	{
		Logger::trace('Forge::transferTreasure Start.');
		
		if (empty($itemId) || empty($itemTplId))
		{
			throw new FakeException("error param itemId:%d, itemTplId:%d", $itemId, $itemTplId);
		}
		
		$itemId = intval($itemId);
		$itemTplId = intval($itemTplId);
		
		return ForgeLogic::transferTreasure($this->uid, $itemId, $itemTplId);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IForge::transferTally()
	 */
	public function transferTally($itemId, $itemTplId)
	{
		Logger::trace('Forge::transferTally Start.');
		
		if (empty($itemId) || empty($itemTplId))
		{
			throw new FakeException("error param itemId:%d, itemTplId:%d", $itemId, $itemTplId);
		}
		
		$itemId = intval($itemId);
		$itemTplId = intval($itemTplId);
		
		return ForgeLogic::transferTally($this->uid, $itemId, $itemTplId);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */