<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BonusManager.class.php 237823 2016-04-12 09:28:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/vipbonus/BonusManager.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-04-12 09:28:41 +0000 (Tue, 12 Apr 2016) $
 * @version $Revision: 237823 $
 * @brief 
 *  
 **/

class BonusManager
{
	private $uid;
	private $vipBonus;
	private $vipBonusBak;
	public static $Instance = NUll;

	public static function getInstance()
	{
		if(empty(self::$Instance))
		{
			self::$Instance = new self();
		}
		return self::$Instance;
	}

	public static function release()
	{
		self::$Instance = NULL;
	}
	
	private function __construct()
	{
		if(empty($this->uid))
		{
			$this->uid = RPCContext::getInstance()->getUid();
		}
		$data = VipBonusDao::select($this->uid);
		if(empty($data))
		{
			$data = $this->init();
		}
		$this->vipBonus = $this->vipBonusBak = $data;
		$this->refresh();
	}

	public function init()
	{
		return array(
				VipBonusDef::SQL_UID => $this->uid,
				VipBonusDef::SQL_BONUS_RECE_TIME => 0,
				VipBonusDef::SQL_VA_INFO => array(),
		);
	}
	
	public function refresh()
	{
		if (!Util::isSameDay($this->getBonusReceTime())) 
		{
			$this->setBonusReceTime(0);
		}
		foreach ($this->getWeekGift() as $vip => $time)
		{
			if (!Util::isSameWeek($time)) 
			{
				$this->delWeekGift($vip);
			}
		}
	}
	
	public function getBonusReceTime()
	{
		return $this->vipBonus[VipBonusDef::SQL_BONUS_RECE_TIME];
	}
	
	public function setBonusReceTime($time)
	{
		$this->vipBonus[VipBonusDef::SQL_BONUS_RECE_TIME] = $time;
	}
	
	public function getWeekGift()
	{
		if (!isset($this->vipBonus[VipBonusDef::SQL_VA_INFO][VipBonusDef::WEEK_GIFT])) 
		{
			return array();
		}
		return $this->vipBonus[VipBonusDef::SQL_VA_INFO][VipBonusDef::WEEK_GIFT];
	}
	
	public function addWeekGift($vip, $time)
	{
		$this->vipBonus[VipBonusDef::SQL_VA_INFO][VipBonusDef::WEEK_GIFT][$vip] = $time;
	}
	
	public function delWeekGift($vip)
	{
		unset($this->vipBonus[VipBonusDef::SQL_VA_INFO][VipBonusDef::WEEK_GIFT][$vip]);
	}

	public function update()
	{
		if($this->vipBonus != $this->vipBonusBak)
		{
			VipBonusDao::update($this->vipBonus);
			$this->vipBonusBak = $this->vipBonus;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */