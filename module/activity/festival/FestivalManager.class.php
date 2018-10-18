<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalManager.class.php 153269 2015-01-19 02:09:56Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/festival/FestivalManager.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-19 02:09:56 +0000 (Mon, 19 Jan 2015) $
 * @version $Revision: 153269 $
 * @brief 
 *  
 **/
class FestivalManager
{
	private $uid = NULL;
	private $data = NULL;
	private $dataModify = NULL;

	private static $_instance = NULL;

	private function __construct($uid = 0)
	{
		if ( empty($uid) )
		{
			$uid = RPCContext::getInstance()->getUid();

			if ( empty($uid) )
			{
				throw new FakeException('uid in session is null.');
			}
		}

		$this->uid = $uid;
		$festivalInfo = FestivalDao::select($this->uid, FestivalDef::$ALL_TABLE_FIELD);
		
		if ( empty($festivalInfo) )
		{
			Logger::trace('User %d enter festival first time , need init.',$this->uid);
			
			$festivalInfo = array(
					FestivalDef::UID => $this->getUid(),
					FestivalDef::UPDATE_TIME => Util::getTime(),
					FestivalDef::VA_DATA => array('hasBuy' => array()),
			);
			
			FestivalDao::insert($festivalInfo);
		}
		
		$this->data = $festivalInfo;
		$this->dataModify = $festivalInfo;
		$this->rfrFestival();
	}
	
	public function getUid()
	{
		return $this->uid;
	}
	
	public static function getInstance($uid=0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}
	
		if ( (NULL == self::$_instance) || (self::$_instance->getUid() != $uid ) )
		{
			self::$_instance = new self($uid);
		}
	
		return self::$_instance;
	}
	
	public static function release()
	{
		if (self::$_instance != NULL)
		{
			self::$_instance = NULL;
		}
	}
	
	public function getFestivalInfo()
	{
		return $this->dataModify;
	}
	
	public function rfrFestival()
	{
		if ( $this->dataModify[FestivalDef::UPDATE_TIME] < FestivalLogic::getActStartTime() )
		{
			$this->dataModify = array(
					FestivalDef::UID => $this->getUid(),
					FestivalDef::UPDATE_TIME => Util::getTime(),
					FestivalDef::VA_DATA => array('hasBuy' => array()),
			);
		}
	}
	
	public function addTimes($fNumber, $num = 1)
	{
		if ( !isset($this->dataModify[FestivalDef::VA_DATA]['hasBuy']) || !isset($this->dataModify[FestivalDef::VA_DATA]['hasBuy'][$fNumber]) )
		{
			$this->dataModify[FestivalDef::VA_DATA]['hasBuy'][$fNumber] = 0;
		}
		
		$this->dataModify[FestivalDef::VA_DATA]['hasBuy'][$fNumber] += $num;
	}
	
	public function update()
	{
		if ( !empty($this->dataModify) && ( $this->data != $this->dataModify ))
		{
			FestivalDao::update($this->uid, $this->dataModify);
			$this->data = $this->dataModify;
		}
	}
	
	public function getHasBuyInfo($fNumber)
	{
		$hasBuyNum = 0;
		if ( isset( $this->dataModify[FestivalDef::VA_DATA]['hasBuy'][$fNumber] ) )
		{
			$hasBuyNum = $this->dataModify[FestivalDef::VA_DATA]['hasBuy'][$fNumber];
		}
		
		return $hasBuyNum;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */