<?php
/***************************************************************************
 *
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: HCopy.class.php 111593 2014-05-27 18:49:45Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/HCopy.class.php $
 * @author $Author: wuqilin $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-27 18:49:45 +0000 (Tue, 27 May 2014) $
 * @version $Revision: 111593 $
 * @brief
 *
 **/

class HCopy implements IHCopy
{
	private $uid;
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}

	public function getUid()
	{
		return $this->uid;
	}

	private static $managers = array();
	public static function makeKey($uid, $copyId, $level)
	{
		return "$uid.$copyId.$level";
	} 
	
	public static function getManager($uid, $copyId, $level)
	{
		$key = self::makeKey($uid, $copyId, $level);
		if(isset(self::$managers[$key]))
		{
		 	return self::$managers[$key];
		}
		else 
		{
			$man = new HCopyManager($uid, $copyId, $level);
			self::$managers[$key] = $man;
			return $man;
		}
	}


	public function getMyManager($copyId, $level)
	{
		return self::getManager($this->getUid(), $copyId, $level);
	}

	function getAllCopyInfos()
	{
		return HCopyLogic::getAllCopyInfos($this->getUid());
	}
	
	public function getCopyInfo($copyid, $level)
	{
		if(!EnSwitch::isSwitchOpen(SwitchDef::HEROCOPY, $this->getUid()))
			throw new FakeException("uid:%d switchcopy hasn't open!", $this->getUid() );
		return $this->getMyManager(intval($copyid), intval($level))->getCopyInfo();
	}

	public function enterBaseLevel($copyId, $baseId, $baseLv)
	{
		return $this->getMyManager(intval($copyId), intval($baseLv))->enterBaseLevel($baseId);
	}

	public function doBattle($copyId, $baseId, $level, $armyId, $fmt=array(), $herolist=null)
	{
		if(!EnSwitch::isSwitchOpen(SwitchDef::HEROCOPY, $this->getUid()))
			throw new FakeException("uid:$this->getUid() switchcopy hasn't open!");
		$man = $this->getMyManager(intval($copyId), intval($level));
		return $man->doBattle($baseId, $armyId, $fmt, $herolist);
	}

	public function leaveBaseLevel($copyId, $baseId, $level)
	{
		$man = $this->getMyManager(intval($copyId), intval($level));
		return $man->leaveBaseLevel($baseId);
	}

	public function reviveCard($baseId,$baseLv,$cardId)
	{
		return HCopyLogic::reviveCard($this->getUid(), intval($baseId), intval($baseLv), intval($cardId));
	}

}
