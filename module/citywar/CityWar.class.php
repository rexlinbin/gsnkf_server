<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CityWar.class.php 139616 2014-11-11 12:38:27Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/citywar/CityWar.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-11 12:38:27 +0000 (Tue, 11 Nov 2014) $
 * @version $Revision: 139616 $
 * @brief 
 *  
 **/
class CityWar implements ICityWar
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
	 * @see ICityWar::getGuildSignupList()
	 */
	public function getGuildSignupList($guildId)
	{
		Logger::trace('CityWar::getGuildSignupList Start.');
		
		if ($guildId <= 0)
		{
			throw new FakeException('Err para, guildId:%d', $guildId);
		}
		
		$ret = CityWarLogic::getGuildSignupList($this->uid, $guildId);
		
		Logger::trace('CityWar::getGuildSignupList End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::getCitySignupList()
	 */
	public function getCitySignupList($cityId, $guildId)
	{
		Logger::trace('CityWar::getCitySignupList Start.');
		
		if ($cityId <= 0 || $guildId <= 0)
		{
			throw new FakeException('Err para, cityId:%d guildId:%d', $cityId, $guildId);
		}
		
		$ret = CityWarLogic::getCitySignupList($this->uid, $cityId, $guildId);
		
		Logger::trace('CityWar::getCitySignupList End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::getCityAttackList()
	 */
	public function getCityAttackList($cityId)
	{
		Logger::trace('CityWar::getCityAttackList Start.');
		
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
		
		$ret = CityWarLogic::getCityAttackList($this->uid, $cityId);
		
		Logger::trace('CityWar::getCityAttackList End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::getCityInfo()
	 */
	public function getCityInfo($cityId)
	{
		Logger::trace('CityWar::getCityInfo Start.');
		
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
		
		$ret = CityWarLogic::getCityInfo($this->uid, $cityId);
		
		Logger::trace('CityWar::getCityInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::getCityId()
	 */
	public function getCityId()
	{
		Logger::trace('CityWar::getCityId Start.');
		
		$ret = CityWarLogic::getCityId($this->uid);
		
		Logger::trace('CityWar::getCityId End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::offlineEnter()
	 */
	public function offlineEnter($cityId, $roundId)
	{
		Logger::trace('CityWar::offlineEnter Start.');
		
		if ($cityId <= 0 || $roundId < 0)
		{
			throw new FakeException('Err para, cityId:%d roundId:%d', $cityId, $roundId);
		}
		
		$ret = CityWarLogic::offlineEnter($this->uid, $cityId, $roundId);
		
		Logger::trace('CityWar::offlineEnter End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::cancelOfflineEnter()
	 */
	public function cancelOfflineEnter($cityId, $roundId)
	{
		Logger::trace('CityWar::cancelOfflineEnter Start.');
		
		if ($cityId <= 0 || $roundId < 0)
		{
			throw new FakeException('Err para, cityId:%d roundId:%d', $cityId, $roundId);
		}
		
		$ret = CityWarLogic::cancelOfflineEnter($this->uid, $cityId, $roundId);
		
		Logger::trace('CityWar::cancelOfflineEnter End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::enter()
	 */
	public function enter($cityId)
	{
		Logger::trace('CityWar::enter Start.');
	
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
	
		$ret = CityWarLogic::enter($this->uid, $cityId);
	
		Logger::trace('CityWar::enter End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::leave()
	 */
	public function leave($cityId)
	{
		Logger::trace('CityWar::leave Start.');
	
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
	
		$ret = CityWarLogic::leave($this->uid, $cityId);
	
		Logger::trace('CityWar::leave End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::inspire()
	 */
	public function inspire($cityId, $type = 0)
	{
		Logger::trace('CityWar::inspire Start.');
		
		if ($cityId <= 0 || $type != 0 && $type != 1)
		{
			throw new FakeException('Err para, cityId:%d type:%d', $cityId, $type);
		}
		
		$ret = CityWarLogic::inspire($this->uid, $cityId, $type);
		
		Logger::trace('CityWar::inspire End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::buyWin()
	 */
	public function buyWin($cityId)
	{
		Logger::trace('CityWar::buyWin Start.');
		
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
		
		$ret = CityWarLogic::buyWin($this->uid, $cityId);
		
		Logger::trace('CityWar::buyWin End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::getReward()
	 */
	public function getReward($cityId)
	{
		Logger::trace('CityWar::getReward Start.');
		
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
		
		$ret = CityWarLogic::getReward($this->uid, $cityId);
		
		Logger::trace('CityWar::getReward End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::signup()
	 */
	public function signup($cityId)
	{
		Logger::trace('CityWar::signup Start.');
	
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
	
		$ret = CityWarLogic::signup($this->uid, $cityId);
	
		Logger::trace('CityWar::signup End.');
	
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::ruinCity()
	 */
	public function ruinCity($cityId)
	{
		Logger::trace('CityWar::ruinCity Start.');
		
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
		
		$ret = CityWarLogic::ruinCity($this->uid, $cityId);
		
		Logger::trace('CityWar::ruinCity End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::mendCity()
	 */
	public function mendCity($cityId)
	{
		Logger::trace('CityWar::mendCity Start.');
		
		if ($cityId <= 0)
		{
			throw new FakeException('Err para, cityId:%d', $cityId);
		}
		
		$ret = CityWarLogic::mendCity($this->uid, $cityId);
		
		Logger::trace('CityWar::mendCity End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see ICityWar::clearCd()
	 */
	public function clearCd($type = 0)
	{
		Logger::trace('CityWar::clearCd Start.');
		
		$type = intval($type);
		if (!key_exists($type, CityWarDef::$CLEARCD_VALID_TYPES))
		{
			throw new FakeException('Err para, type:%d', $type);
		}
		
		$ret = CityWarLogic::clearCd($this->uid, $type);
		
		Logger::trace('CityWar::clearCd End.');
		
		return $ret;
	}
	
	/**
	 * 报名结束(由timer调用执行,timer由此次城池战报名signup的第一个军团添加)
	 */
	public function signupEnd($cityId)
	{
		Logger::trace('CityWar::signupEnd Start.');
		
		CityWarLogic::signupEnd($cityId);
		
		Logger::trace('CityWar::signupEnd End.');
	}
	
	/**
	 * 长连接，战斗开始(由timer调用执行,timer在此次城池战报名结束时signupEnd添加)
	 */
	public function attackStart($cityId, $signupId, $attackGid, $endTime)
	{
		Logger::trace('CityWar::attackStart Start.');
	
		CityWarLogic::attackStart($cityId, $signupId, $attackGid, $endTime);
	
		Logger::trace('CityWar::attackStart End.');
	}
	
	/**
	 * 城池战结束(由timer调用执行,timer在此次城池战报名结束时signupEnd添加)
	 */
	public function battleEnd($cityId)
	{
		Logger::trace('CityWar::battleEnd Start.');
	
		CityWarLogic::battleEnd($cityId);
	
		Logger::trace('CityWar::battleEnd End.');
	}
	
	/**
	 * 给所有没有战斗的城池加上battleEnd的timer(由timer调用执行,timer在此次城池战结束时battleEnd添加,在下一轮的signupEnd之后执行)
	 */
	public function checkAttack()
	{
		Logger::trace('CityWar::doAttack Start.');
		
		CityWarLogic::checkAttack();
		
		Logger::trace('CityWar::doAttack End.');
	}
	
	/**
	 * 长连接，实现战斗(由attackStart转出)
	 * 
	 * @param int $cityId
	 * @param array $attackInfo
	 * @param array $defendInfo
	 * @param array $arrayExtra
	 */
	public function doAttack($cityId, $attackInfo, $defendInfo, $arrayExtra)
	{
		Logger::trace('CityWar::doAttack Start.');
		
		CityWarLogic::doAttack($cityId, $attackInfo, $defendInfo, $arrayExtra);
		
		Logger::trace('CityWar::doAttack End.');
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */