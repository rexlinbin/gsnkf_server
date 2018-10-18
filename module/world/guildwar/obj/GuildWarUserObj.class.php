<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWarUserObj.class.php 160528 2015-03-09 01:47:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/obj/GuildWarUserObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-03-09 01:47:39 +0000 (Mon, 09 Mar 2015) $
 * @version $Revision: 160528 $
 * @brief 
 *  
 **/
 
class GuildWarUserObj
{
	/**
	 * 静态缓存
	 * @var array
	 */
	private static $sArrInstance = array();
	
	/**
	 * 服务器Id
	 * @var int
	 */
	private $mServerId;
	
	/**
	 * 玩家uid
	 * @var unknown
	 */
	private $mUid;

	/**
	 * 原始数据
	 * @var array
	 */
	private $mObj = array();

	/**
	 * 数据
	 * @var array
	 */
	private $mObjModify = array();

	/**
	 * getInstance 获取实例
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $uid 玩家uid
	 * @param bool $init 如果数据为空，是否初始化
	 * @static
	 * @access public
	 * @return GuildWarUserObj
	 */
	public static function getInstance($serverId, $uid, $init = FALSE)
	{
		$key = self::getKey($serverId, $uid);
		if (!isset(self::$sArrInstance[$key]))
		{
			self::$sArrInstance[$key] = new self($serverId, $uid, $init);
		}

		return self::$sArrInstance[$key];
	}
	
	/**
	 * 
	 * @param int $serverId
	 * @param array $info
	 * @return GuildWarUserObj
	 */
	public static function getInstanceFromInfo($serverId, $info)
	{
		foreach (GuildWarUserField::$ALL_FIELDS as $aField)
		{
			if (!isset($info[$aField]))
			{
				throw new InterException('GuildWarUserObj::getInstanceFromInfo failed, info[%s] do not have field[%s]', $info, $aField);
			}
		}
		
		$uid = $info[GuildWarUserField::TBL_FIELD_UID];
		$key = self::getKey($serverId, $uid);
		
		self::$sArrInstance[$key] = new self($serverId, $uid, FALSE, $info);
		return self::$sArrInstance[$key];
	}

	/**
	 * 释放实例
	 * 
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 */
	public static function releaseInstance($serverId, $pid)
	{
		$key = self::getKey($serverId, $pid);
		if (isset(self::$sArrInstance[$key]))
		{
			unset(self::$sArrInstance[$key]);
		}
	}
	
	/**
	 * 获得key
	 * 
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $uid 玩家uid
	 * @return string
	 */
	public static function getKey($serverId, $uid)
	{
		return $serverId . '_' . $uid;
	}

	/**
	 * 构造函数
	 * 
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $uid 玩家uid
	 * @param bool $init 如果数据位空，是否初始化
	 * @param array $userInfo
	 */
	private function __construct($serverId, $uid, $init, $userInfo = array())
	{
		$isMyserver = GuildWarUtil::isMyServer($serverId);
		$this->mServerId = $serverId;
		$this->mUid = $uid;
		
		if (empty($userInfo)) 
		{
			$userInfo = $this->getInfo($serverId, $uid);
			if (empty($userInfo))
			{
				if ($isMyserver || $init) 
				{
					$userInfo = $this->createInfo($serverId, $uid);
				}
				else 
				{
					throw new FakeException("Not found guild war user obj, serverId[%d], uid[%d].", $serverId, $uid);
				}
			}
		}
		
		$this->mObj = $userInfo;
		$this->mObjModify = $userInfo;
		$this->refresh();
	}
	
	public function refresh()
	{
		$confObj = GuildWarConfObj::getInstance();
		if ($this->getLastJoinTime() < $confObj->getActivityStartTime()) 
		{
			$this->mObjModify = $this->getInitInfo();
		}		
	}
	
	public function getInitInfo()
	{		
		return array
		(
				GuildWarUserField::TBL_FIELD_UID => $this->mUid,
				GuildWarUserField::TBL_FIELD_UNAME => '',
				GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID => 0,
				GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID => 0,
				GuildWarUserField::TBL_FIELD_CHEER_ROUND => 0,
				GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM => 0,
				GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME => 0,
				GuildWarUserField::TBL_FIELD_WORSHIP_TIME => 0,
				GuildWarUserField::TBL_FIELD_FIGHT_FORCE => 0,
				GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME => 0,
				GuildWarUserField::TBL_FIELD_SEND_PRIZE_TIME => 0,
				GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME => Util::getTime(),
				GuildWarUserField::TBL_FIELD_VA_EXTRA => array
										(
												GuildWarUserField::TBL_VA_EXTRA_CHEER => array(), 
												GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT => array(),
										),
		);
	}
	
	public function getCheerInfo($round)
	{
		if (!isset($this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER][$round])) 
		{
			return array();
		}
		
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER][$round];
	}
	
	private function setCheerInfo($round, $cheerInfo)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER][$round] = $cheerInfo;
	}
	
	public function setCheerRewardTime($round, $time = 0)
	{
		if ($time == 0) 
		{
			$time = Util::getTime();
		}
		
		if (!isset($this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER][$round])) 
		{
			throw new InterException('GuildWarUserObj.setCheerRewardTime, no cheer info of round[%d], but set reward time.', $round);
		}
		
		$this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER][$round][GuildWarUserField::TBL_VA_EXTRA_REWARD_TIME] = $time;
	}
	
	public function receiveRankReward()
	{
		$this->setRankRewardTime(Util::getTime());
	}
	
	public function isReceiveRankReward()
	{
		return $this->getRankRewardTime() > 0;
	}
	
	public function getRankRewardTime()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_SEND_PRIZE_TIME];
	}
	
	private function setRankRewardTime($time = 0)
	{
		if ($time == 0)
		{
			$time = Util::getTime();
		}
		
		$this->mObjModify[GuildWarUserField::TBL_FIELD_SEND_PRIZE_TIME] = $time;
	}

	public function getInfo($serverId, $uid)
	{
		$db = NULL;
		if(!GuildWarUtil::isMyServer($serverId))
		{
			$db = GuildWarUtil::getServerDbByServerId($serverId);
		}
		
		$arrField = GuildWarUserField::$ALL_FIELDS;
		$arrCond = array
		(
				array(GuildWarUserField::TBL_FIELD_UID, '=', $uid),
		);
		
		return GuildWarDao::selectGuildWarUser($arrCond, $arrField, $db);
	}
	
	public function createInfo($serverId, $pid)
	{
		$db = NULL;
		if(!GuildWarUtil::isMyServer($serverId))
		{
			$db = GuildWarUtil::getServerDbByServerId($serverId);
		}
		
		$initInfo = $this->getInitInfo();
		GuildWarDao::insertGuildWarUser($initInfo, $db);
		
		return $initInfo;
	}
	
	public function getUpdateFmtTime()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME];
	}
	
	public function getLastJoinTime()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME];
	}
	
	public function clearUpdateFmtTimeCd()
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME] = 0;
	}
	
	public function getBuyMaxWinNum($round, $lastFightTime)
	{
		Logger::trace('GuildWarUserObj.getBuyMaxWinNum param[serverId:%d, uid:%d, round:%d, lastFightTime:%s] begin...', $this->mServerId, $this->mUid, $round, strftime('%Y%m%d-%H%M%S', $lastFightTime));
				
		if ($round <= GuildWarRound::AUDITION)
		{
			return $this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM];
		}
		
		if ($lastFightTime == 0) 
		{
			return $this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM];
		}
		
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			throw new InterException('GuildWarUserObj::getBuyMaxWinNum, not in any session');
		}
		
		$lastFightRound = $confObj->getRound($lastFightTime);
		$lastFightRoundEndTime = $confObj->getRoundEndTime($lastFightRound);
		$buyMaxWinTime = $this->getBuyMaxWinTime();
		if ($buyMaxWinTime < $lastFightRoundEndTime)
		{
			Logger::trace('GuildWarUserObj.getBuyMaxWinNum lastFightRound[%d], lastFightRoundEndTime[%s], buyMaxWinTime[%s], reset buyMaxWinNum[%d] to 0', 
						$lastFightRound, strftime('%Y%m%d-%H%M%S', $lastFightRoundEndTime), strftime('%Y%m%d-%H%M%S', $buyMaxWinTime), $this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM]);
			$this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM] = 0;
		}

		Logger::trace('GuildWarUserObj.getBuyMaxWinNum param[serverId:%d, uid:%d, round:%d, lastFightTime:%s] ret[%d] end...', $this->mServerId, $this->mUid, $round, strftime('%Y%m%d-%H%M%S', $lastFightTime), $this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM]);
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM];
	}
	
	public function getBuyMaxWinTime()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME];
	}
	
	public function getMaxWinNum($round, $lastFightTime)
	{
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			throw new InterException('GuildWarUserObj::getMaxWinNum, not in any session');
		}
		
		return $confObj->getDefaultMaxWinTimes() + $this->getBuyMaxWinNum($round, $lastFightTime);
	}
	
	public function increBuyMaxWinNum()
	{
		++$this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM];
		$this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME] = Util::getTime();
	}
	
	public function getFightForce()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_FIGHT_FORCE];
	}
	
	private function setFightForce($fightForce)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_FIGHT_FORCE] = $fightForce;
	}
	
	public function getAllCheerInfo()
	{
		if (!isset($this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER])) 
		{
			return array();
		}
		
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_CHEER];
	}
	
	public function cheer($round, $guildId, $guildName, $serverId, $serverName)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID] = $guildId;
		$this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID] = $serverId;
		$this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_ROUND] = $round;
		
		$cheerInfo = array
		(
				GuildWarUserField::TBL_VA_EXTRA_GUILD_ID => $guildId,
				GuildWarUserField::TBL_VA_EXTRA_GUILD_NAME => $guildName,
				GuildWarUserField::TBL_VA_EXTRA_SERVER_ID => $serverId,
				GuildWarUserField::TBL_VA_EXTRA_SERVER_NAME => $serverName,
				GuildWarUserField::TBL_VA_EXTRA_REWARD_TIME => 0,
		);
		$this->setCheerInfo($round, $cheerInfo);
	}
	
	public function getWorshipTime()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_WORSHIP_TIME];
	}
	
	public function worship()
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_WORSHIP_TIME] = Util::getTime();
	}
	
	public function getUserInfo()
	{
		return $this->mObjModify;
	}
	
	public function getUname()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_UNAME];
	}
	
	public function setUname($name)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_UNAME] = $name;
	}
	
	public function getCheerGuildId()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID];
	}
	
	public function getCheerGuildServerId()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID];
	}
	
	public function getCheerRound()
	{
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_ROUND];
	}
	
	public function cheerRewardEnd()
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_GUILD_ID] = 0;
		$this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_GUILD_SERVER_ID] = 0;
		$this->mObjModify[GuildWarUserField::TBL_FIELD_CHEER_ROUND] = 0;
	}
	
	public function isArmed()
	{
		$battleFmt = $this->getBattleFmt();
		return !empty($battleFmt);
	}
	
	public function arm($battleFmt)
	{	
		$this->setBattleFmt($battleFmt);
	}
	
	private function setBattleFmt($battleFmt)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_FIGHT_FORCE] = $battleFmt['fightForce'];
		
		/**** 20150306 add 将战斗数据中的名字更新为报名时候的名字，防止战报中显示的是玩家改过的名字，而其余地方显示的是报名的名字 *****/
		$orginName = $this->getUname();
		if (!empty($orginName) && $orginName != $battleFmt['name']) 
		{
			$battleFmt['name'] = $orginName;
		}
		
		$this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT] = $battleFmt;
	}
	
	public function getBattleFmt()
	{
		if (!isset($this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT])) 
		{
			return array();
		}
		
		return $this->mObjModify[GuildWarUserField::TBL_FIELD_VA_EXTRA][GuildWarUserField::TBL_VA_EXTRA_BATTLE_FMT];
	}
	
	public function inCd($cdTime)
	{
		$curTime = Util::getTime();
		if ($curTime < (self::getUpdateFmtTime() + $cdTime))
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function updateBattleFmt($battleFmt)
	{
		$this->setBattleFmt($battleFmt);
		$this->mObjModify[GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME] = Util::getTime();
	}
	
	public function setWorshipTimeForConsole($time)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_WORSHIP_TIME] = $time;
	}
	
	public function setUpdateFmtTimeForConsole($time)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_UPDATE_FMT_TIME] = $time;
	}
	
	public function setBuyMaxWinTimeForConsole($time)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_TIME] = $time;
	}
	
	public function setBuyMaxWinNumForConsole($num)
	{
		$this->mObjModify[GuildWarUserField::TBL_FIELD_BUY_MAX_WIN_NUM] = $num;
	}

	public function update()
	{
		if ($this->mObjModify == $this->mObj)
		{
			Logger::trace('GuildWarUserObj.update, nothing change, no need update, mObjModify[%s]', $this->mObjModify);
			return;
		}
		
		$db = NULL;
		if(!GuildWarUtil::isMyServer($this->mServerId))
		{
			$db = GuildWarUtil::getServerDbByServerId($this->mServerId);
		}
		
		$arrUpdate = array();
		foreach ($this->mObjModify as $key => $info)
		{
			if($info != $this->mObj[$key])
			{
				$arrUpdate[$key] = $info;
			}
		}
		
		if (empty($arrUpdate))
		{
			Logger::fatal('GuildWarUserObj.update, nothing change? mObjModify[%s]', $this->mObjModify);
			return;
		}
		
		if (!isset($arrUpdate[GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME]))
		{
			$arrUpdate[GuildWarUserField::TBL_FIELD_LAST_JOIN_TIME] = Util::getTime();
		}
		
		$arrCond = array
		(
				array(GuildWarUserField::TBL_FIELD_UID, '=', $this->mUid),
		);
		GuildWarDao::updateGuildWarUser($arrCond, $arrUpdate, $db);
		$this->mObj = $this->mObjModify;
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */