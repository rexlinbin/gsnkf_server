<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRobUserObj.class.php 259118 2016-08-29 09:38:59Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/GuildRobUserObj.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-29 09:38:59 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259118 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRobUserObj
* Description : 军团抢粮战之用户数据管理类
* Inherit     :
**********************************************************************************************************************/
class GuildRobUserObj
{
	private static $sArrInstance = array();
	private $mObj = array();
	private $mObjModify = array();
	
	/**
	 * getInstance 获取用户实例
	 *
	 * @param int $uid 用户id
	 * @static
	 * @access public
	 * @return GuildRobUserObj
	 */
	public static function getInstance($uid)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}
	
		if (!isset(self::$sArrInstance[$uid]))
		{
			self::$sArrInstance[$uid] = new GuildRobUserObj($uid);
		}
	
		return self::$sArrInstance[$uid];
	}
	
	public static function releaseInstance($uid)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
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
		$this->mObj = $this->getGuildRobUserInfo($uid);
		if (empty($this->mObj)) 
		{
			$this->mObj = $this->createGuildRobUserInfo($uid);
		}
		$this->mObjModify = $this->mObj;
	}
	
	public function getGuildRobUserInfo($uid)
	{
		$arrCond = array
		(
				array(GuildRobUserField::TBL_FIELD_UID, '=', $uid),
		);
		$arrBody = GuildRobUserField::$GUILD_ROB_USER_ALL_FIELDS;
		
		return GuildRobDao::selectUser($arrCond, $arrBody);
	}
	
	public function createGuildRobUserInfo($uid)
	{
		$arrRet = array
		(
				GuildRobUserField::TBL_FIELD_UID => $uid,
				GuildRobUserField::TBL_FIELD_ROB_ID => 0,
				GuildRobUserField::TBL_FIELD_GUILD_ID => 0,
				GuildRobUserField::TBL_FIELD_UNAME => '',
				GuildRobUserField::TBL_FIELD_REMOVE_CD_NUM => 0,
				GuildRobUserField::TBL_FIELD_SPEEDUP_NUM => 0,
				GuildRobUserField::TBL_FIELD_KILL_NUM => 0,
				GuildRobUserField::TBL_FIELD_USER_GRAIN_NUM => 0,
				GuildRobUserField::TBL_FIELD_GUILD_GRAIN_NUM => 0,
				GuildRobUserField::TBL_FIELD_MERIT_NUM => 0,
				GuildRobUserField::TBL_FIELD_CONTR_NUM => 0,
				GuildRobUserField::TBL_FIELD_REWARD_TIME => 0,
				GuildRobUserField::TBL_FIELD_JOIN_TIME => 0,
				GuildRobUserField::TBL_FIELD_KILL_TIME => 0,
		        GuildRobUserField::TBL_FIELD_OFFLINE_TIME => 0,
		);
		GuildRobDao::insertUser($arrRet);
		
		return $arrRet;
	}
	
	public function start($robId, $guildId)
	{
		$this->resetAllField();
		
		$this->mObjModify[GuildRobUserField::TBL_FIELD_ROB_ID] = $robId;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_UNAME] = EnUser::getUserObj($this->getUid())->getUname();
		$this->mObjModify[GuildRobUserField::TBL_FIELD_GUILD_ID] = $guildId;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_JOIN_TIME] = Util::getTime();
	}
	
	private function resetAllField()
	{
		$this->mObjModify[GuildRobUserField::TBL_FIELD_ROB_ID] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_GUILD_ID] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_UNAME] = '';
		$this->mObjModify[GuildRobUserField::TBL_FIELD_REMOVE_CD_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_SPEEDUP_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_KILL_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_MERIT_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_CONTR_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_USER_GRAIN_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_GUILD_GRAIN_NUM] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_REWARD_TIME] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_JOIN_TIME] = 0;
		$this->mObjModify[GuildRobUserField::TBL_FIELD_KILL_TIME] = 0;
	}
	
	public function getUid()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_UID];
	}
	
	public function getRobId()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_ROB_ID];
	}
	
	public function getGuildId()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_GUILD_ID];
	}
	
	public function getUname()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_UNAME];
	}
	
	public function getKillNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_KILL_NUM];
	}
	
	public function increKillNum($killTime)
	{
		++$this->mObjModify[GuildRobUserField::TBL_FIELD_KILL_NUM];
		$this->mObjModify[GuildRobUserField::TBL_FIELD_KILL_TIME] = $killTime;
	}
	
	public function getMeritNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_MERIT_NUM];
	}
	
	public function addMeritNum($num)
	{
		$this->mObjModify[GuildRobUserField::TBL_FIELD_MERIT_NUM] += $num;
	}
	
	public function getContrNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_CONTR_NUM];
	}
	
	public function addContrNum($num)
	{
		$this->mObjModify[GuildRobUserField::TBL_FIELD_CONTR_NUM] += $num;
	}
	
	public function getUserGrainNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_USER_GRAIN_NUM];
	}
	
	public function addUserGrainNum($num)
	{
		$this->mObjModify[GuildRobUserField::TBL_FIELD_USER_GRAIN_NUM] += $num;
	}
	
	public function getGuildGrainNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_GUILD_GRAIN_NUM];
	}
	
	public function addGuildGrainNum($num)
	{
		$this->mObjModify[GuildRobUserField::TBL_FIELD_GUILD_GRAIN_NUM] += $num;
	}
	
	public function getRemoveCdNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_REMOVE_CD_NUM];
	}
	
	public function increRemoveCdNum()
	{
		++$this->mObjModify[GuildRobUserField::TBL_FIELD_REMOVE_CD_NUM];
	}
	
	public function getSpeedUpNum()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_SPEEDUP_NUM];
	}
	
	public function increSpeedUpNum()
	{
		++$this->mObjModify[GuildRobUserField::TBL_FIELD_SPEEDUP_NUM];
	}
	
	public function getRewardTime()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_REWARD_TIME];
	}
	
	public function getJoinTime()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_JOIN_TIME];
	}
	
	public function getKillTime()
	{
		return $this->mObjModify[GuildRobUserField::TBL_FIELD_KILL_TIME];
	}
	
	public function getOfflineTime()
	{
	    return $this->mObjModify[GuildRobUserField::TBL_FIELD_OFFLINE_TIME];
	}
	
	public function setOffline($time)
	{
	    return $this->mObjModify[GuildRobUserField::TBL_FIELD_OFFLINE_TIME] = $time;
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
			Logger::debug('update GuildRobUserObj : no change');
			return;
		}
		
		Logger::debug("update GuildRobUserObj uid:%d robid:%d, changed field:%s", $this->getUid(), $this->getRobId(), $arrField);
		
		$arrCond = array
		(
				array(GuildRobUserField::TBL_FIELD_UID, '=', $this->getUid()),
		);
		GuildRobDao::updateUser($arrCond, $arrField);
		
		$this->mObj = $this->mObjModify;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */