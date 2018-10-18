<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BowlObj.class.php 259726 2016-08-31 08:54:51Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/bowl/BowlObj.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-31 08:54:51 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259726 $
 * @brief 
 *  
 **/
 
class BowlObj
{
	private static $sArrInstance = array();
	
	private $mObj = array();
	private $mObjModify = array();

	/**
	 * getInstance 获取用户实例
	 *
	 * @param int $uid 用户id
	 * @throws
	 * @static
	 * @access public
	 * @return BowlObj
	*/
	public static function getInstance($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == NULL)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}

		if (!isset(self::$sArrInstance[$uid]))
		{
			self::$sArrInstance[$uid] = new self($uid);
		}

		return self::$sArrInstance[$uid];
	}

	public static function releaseInstance($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == NULL)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}

		if (isset(self::$sArrInstance[$uid]))
		{
			unset(self::$sArrInstance[$uid]);
		}
	}

	private function __construct($uid)
	{
		$this->mObj = $this->getInfo($uid);
		
		if (empty($this->mObj))
		{
			$this->mObj = $this->createInfo($uid);
		}
		else if ($this->mObj[BowlDef::TBL_FIELD_UPDATE_TIME] < BowlLogic::getActStartTime(ActivityName::BOWL))
		{
			$this->mObj = $this->resetInfo($uid);
		}
		
		$this->mObjModify = $this->mObj;
	}
	
	public function getInfo($uid)
	{
		$arrCond = array(array(BowlDef::TBL_FIELD_UID, '=', $uid));
		$arrBody = BowlDef::$BOWL_ALL_FIELDS;
		return BowlDao::select($arrCond, $arrBody);
	}
	
	public function createInfo($uid)
	{
		$arrRet = array 
		(
				BowlDef::TBL_FIELD_UID => $uid,
				BowlDef::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				BowlDef::TBL_FIELD_VA_EXTRA => array(),
		);
		BowlDao::insert($arrRet);
	
		return $arrRet;
	}
	
	public function resetInfo($uid)
	{
		$arrRet = array
		(
				BowlDef::TBL_FIELD_UID => $uid,
				BowlDef::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				BowlDef::TBL_FIELD_VA_EXTRA => array(),
		);
		$arrCond = array(array(BowlDef::TBL_FIELD_UID, '=', $uid));
		BowlDao::update($arrCond, $arrRet);
		
		return $arrRet;
	}
	
	public function getUid()
	{
		return $this->mObjModify[BowlDef::TBL_FIELD_UID];
	}
	
	public function getUpdateTime()
	{
		return $this->mObjModify[BowlDef::TBL_FIELD_UPDATE_TIME];
	}
	
	public function hasBuy($type)
	{
		if (!isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]))
		{
			return FALSE;
		}
	
		return TRUE;
	}
	
	public function buy($type)
	{
		$this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME] = Util::getTime();
		$this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward'] = array();
	}
	
	public function canReceive($type, $day)
	{
		if (!isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'])
				|| !isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]))
		{
			Logger::info('Has not bowled.');
			return FALSE;
		}
		
		if ( !isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME]) )
		{
			Logger::info('Bowled, but no bowlTime. Check funtion Obj->buy.');
			return false;
		}
		
		if ( !isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']) )
		{
			Logger::info('Bowled, but no rewardInfo. Check funtion Obj->buy.');
			return true;
		}
		
		$bowlTime = $this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME];
		$curDay = Util::getDaysBetween($bowlTime) + 1;
		if ( $day > $curDay )
		{
			throw new FakeException('now is %d, user wants to receive $day',$curDay,$day);
		}
		
		if ( in_array($day, $this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']) )
		{
			return false;
		}
		
		return TRUE;
	}
	
	public function changeRewardInfo($type)
	{
		if ( !isset( $this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME] ) )
		{
			Logger::trace('have not bowled.');
			return;
		}
		
		$bowlTime = $this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type][BowlDef::TBL_VA_EXTRA_FIELD_BOWLTIME];
		$now = Util::getTime();
		
		if ( $now < $bowlTime )
		{
			throw new InterException('bowlTime err. BowlTime: %d, now: %d.', $bowlTime, $now);
		}
		
		$curDay = Util::getDaysBetween($bowlTime) + 1;
		
		if ( 0 == $curDay )
		{
			throw new InterException('Act bowl.curDay is zero.');
		}
		
		$maxTypeDay = BowlLogic::getRewardDayNum($type);
		
		if ( $curDay > $maxTypeDay )
		{
			$curDay = $maxTypeDay;
		}
		
		$info4Front = array();
		
		for ( $i = 1; $i <= $curDay; $i++ )
		{
			if ( !in_array($i, $this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']) )
			{
				$info4Front[$i] = BowlDef::BOWL_REWARD_STATE_HAVE;
			}
			elseif ( in_array($i, $this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward'] ) )
			{
				$info4Front[$i] = BowlDef::BOWL_REWARD_STATE_RECEIVED;
			}
		}
		
		for ( $i = $curDay+1; $i <= $maxTypeDay; $i++ )
		{
			$info4Front[$i] = BowlDef::BOWL_REWARD_STATE_EMPTY;
		}
		
		return $info4Front;
	}
	
	public function receive($type, $day)
	{
		$this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward'][] = $day;
	}
	
	public function getRewardInfo($type)
	{
		if (!isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'])
				|| !isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type])
				|| !isset($this->mObjModify[BowlDef::TBL_FIELD_VA_EXTRA]['type'][$type]['reward']))
		{
			return array();
		}
		
		$info4Front = array();
		$info4Front = $this->changeRewardInfo($type);
	
		return $info4Front;
	}
	
	public function getBowlInfo()
	{
		$ret = array();
		$conf = EnActivity::getConfByName(ActivityName::BOWL);
		$startTime = $conf['start_time'];

		$uid = $this->getUid();
		$recharge = BowlLogic::getChargeDuringBowl($uid);
		
		foreach (BowlType::$ALL_TYPE as $aType)
		{
			$aTypeInfo = array();
			
			$need = intval($conf['data'][$aType][BowlDef::BOWL_BUY_NEED]);
			if ($recharge < $need)
			{
				$aTypeInfo['state'] = BowlState::CAN_NOT_BUY;
				$aTypeInfo['reward'] = array();
			}
			else
			{
				$aTypeInfo['state'] = $this->hasBuy($aType) ? BowlState::ALREADY_BUY : BowlState::CAN_BUY;
				$aTypeInfo['reward'] = $this->getRewardInfo($aType);
			}
			
			$ret[$aType] = $aTypeInfo;
		}
		
		return $ret;
	}
	
	public function update()
	{
		$arrField = array();
		foreach ($this->mObj as $key => $value)
		{
			if ($this->mObjModify[$key] != $value)
			{
				$arrField[$key] = $this->mObjModify[$key];
			}
		}
			
		if (empty($arrField))
		{
			Logger::debug('no change');
			return;
		}
	
		Logger::debug("update BowlObj uid:%d, changed field:%s", $this->getUid(), $arrField);
	
		$arrCond = array(array(BowlDef::TBL_FIELD_UID, '=', $this->getUid()));
		BowlDao::update($arrCond, $arrField);
	
		$this->mObj = $this->mObjModify;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */