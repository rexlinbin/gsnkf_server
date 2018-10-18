<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldArenaInnerUserObj.class.php 207536 2015-11-05 08:53:59Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/WorldArenaInnerUserObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-11-05 08:53:59 +0000 (Thu, 05 Nov 2015) $
 * @version $Revision: 207536 $
 * @brief 
 *  
 **/

class WorldArenaInnerUserObj
{
	private static $sArrInstance = array();
	private $mObj = array();
	private $mObjModify = array();

	/**
	 * getInstance 获取实例
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @param int $uid 玩家uid
	 * @param boolean $init 
	 * @static
	 * @access public
	 * @return WorldArenaInnerUserObj
	*/
	public static function getInstance($serverId, $pid, $uid, $init = TRUE)
	{
		$key = self::getKey($serverId, $pid);
		if (!isset(self::$sArrInstance[$key]))
		{
			self::$sArrInstance[$key] = new self($serverId, $pid, $uid, $init);
		}

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
	 * @param int $pid 玩家pid
	 * @return string
	 */
	public static function getKey($serverId, $pid)
	{
		return $serverId . '_' . $pid;
	}

	/**
	 * 构造函数
	 *
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @param int $uid 玩家uid
	 * @param boolean $init
	 */
	private function __construct($serverId, $pid, $uid, $init = TRUE)
	{
		$isMyserver = WorldArenaUtil::isMyServer($serverId);
		
		$db = '';
		if (!$isMyserver) 
		{
			$db = WorldArenaUtil::getServerDbByServerId($serverId);
		}
		
		$userInfo = $this->getInfo($serverId, $pid, $db);
		if (empty($userInfo))
		{
			if ($isMyserver && $init)
			{
				$userInfo = $this->createInfo($serverId, $pid, $uid);
			}
			else
			{
				throw new FakeException("not my server, serverId[%d], pid[%d].", $serverId, $pid);
			}
		}

		$this->mObj = $userInfo;
		$this->mObjModify = $userInfo;
		$this->refresh();
	}

	/**
	 * 从db中获取数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @param string $db
	 * @return array
	 */
	public function getInfo($serverId, $pid, $db = '')
	{
		$arrCond = array
		(
				array(WorldArenaInnerUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldArenaInnerUserField::TBL_FIELD_PID, '=', $pid),
		);
		$arrField = WorldArenaInnerUserField::$ALL_FIELDS;

		return WorldArenaDao::selectInnerUser($arrCond, $arrField, $db);
	}

	/**
	 * 如果第一次进入，需要插入初始化数据
	 *
	 * @param int $serverId
	 * @param int $pid
	 * @param int $uid
	 * @return array
	 */
	public function createInfo($serverId, $pid, $uid)
	{
		$initInfo = $this->getInitInfo($serverId, $pid, $uid);
		WorldArenaDao::insertInnerUser($initInfo);

		return $initInfo;
	}
	
	/**
	 * 周期切换时候，需要刷新数据
	 */
	public function refresh()
	{
		$confObj = WorldArenaConfObj::getInstance();
		if ($this->getUpdateTime() < $confObj->getPeriodBgnTime())
		{	
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_ATKED_NUM] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_BUY_ATK_NUM] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SILVER_RESET_NUM] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_GOLD_RESET_NUM] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SIGNUP_TIME] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UPDATE_FMT_TIME] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_LAST_ATTACK_TIME] = 0;
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_FMT] = array();
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA] = array(WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT => array());
		}
	}
	
	/**
	 * db里没数据时候，生成一份初始化数据
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @param int $uid
	 * @return array
	 */
	public function getInitInfo($serverId, $pid, $uid)
	{
		$initInfo = array
		(
				WorldArenaInnerUserField::TBL_FIELD_PID => $pid,
				WorldArenaInnerUserField::TBL_FIELD_SERVER_ID => $serverId,
				WorldArenaInnerUserField::TBL_FIELD_UID => $uid,
				WorldArenaInnerUserField::TBL_FIELD_ATKED_NUM => 0,
				WorldArenaInnerUserField::TBL_FIELD_BUY_ATK_NUM => 0,
				WorldArenaInnerUserField::TBL_FIELD_SILVER_RESET_NUM => 0,
				WorldArenaInnerUserField::TBL_FIELD_GOLD_RESET_NUM => 0,
				WorldArenaInnerUserField::TBL_FIELD_SIGNUP_TIME => 0,
				WorldArenaInnerUserField::TBL_FIELD_UPDATE_FMT_TIME => 0,
				WorldArenaInnerUserField::TBL_FIELD_LAST_ATTACK_TIME => 0,
				WorldArenaInnerUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				WorldArenaInnerUserField::TBL_FIELD_VA_FMT => array(),
				WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA => array(WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT => array()),
		);
		
		return $initInfo;
	}
	
	/**
	 * 获得serverId
	 *
	 * @return int
	 */
	public function getServerId()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SERVER_ID];
	}
	
	/**
	 * 获得pid
	 *
	 * @return int
	 */
	public function getPid()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_PID];
	}
	
	/**
	 * 获得uid
	 *
	 * @return int
	 */
	public function getUid()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UID];
	}
	
	/**
	 * 获得玩家报名时间
	 * 
	 * @return int
	 */
	public function getSignupTime()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SIGNUP_TIME];
	}
	
	/**
	 * 获得玩家剩余的挑战次数
	 *  
	 * @return int
	 */
	public function getAtkNum()
	{
		$confObj = WorldArenaConfObj::getInstance();
		$freeAtkNum = $confObj->getFreeAtkNum();
		$buyAtkNum = $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_BUY_ATK_NUM];
		$atkedNum = $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_ATKED_NUM];
		
		return $freeAtkNum + $buyAtkNum - $atkedNum;
	}
	
	/**
	 * 获得玩家购买的挑战次数
	 * 
	 * @return int
	 */
	public function getBuyAtkNum()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_BUY_ATK_NUM];
	}
	
	/**
	 * 增加玩家购买的次数
	 */
	public function addBuyAtkNum($num)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_BUY_ATK_NUM] += $num;
	}
	
	/**
	 * 获得玩家银币重置次数
	 * 
	 * @return int
	 */
	public function getSilverResetNum()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SILVER_RESET_NUM];
	}
	
	/**
	 * 增加银币重置次数
	 */
	public function increSilverResetNum()
	{
		++$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SILVER_RESET_NUM];
	}
	
	/**
	 * 获得玩家金币重置次数
	 * 
	 * @return int
	 */
	public function getGoldResetNum()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_GOLD_RESET_NUM];
	}
	
	/**
	 * 增加金币重置次数
	 */
	public function increGoldResetNum()
	{
		++$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_GOLD_RESET_NUM];
	}
	
	/**
	 * 报名，设置报名时间
	 */
	public function signUp()
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SIGNUP_TIME] = Util::getTime();
	}
		
	/**
	 * 更新战斗信息，同时设置满血满怒
	 * 
	 * @param array $fmt
	 * @param boolean $needSetUpdateFmtTime
	 */
	public function updateFmt($fmt, $needSetUpdateFmtTime = TRUE)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_FMT] = $fmt;
		if ($needSetUpdateFmtTime) 
		{
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UPDATE_FMT_TIME] = Util::getTime();
		}
		if (!empty($this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT])) 
		{
			$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT] = array();
		}
	}
	
	/**
	 * 获得玩家保存的战斗数据
	 * 需要更新继承的血量和怒气
	 * 根据偏移量对uid,hid进行偏移
	 * 
	 * @param int $offset
	 * @throws InterException
	 * @return array
	 */
	public function getFmt($offset)
	{
		// 获取玩家的战斗数据
		$battleFmt = $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_FMT];
		if (empty($battleFmt))
		{
			throw new InterException('no battle fmt');
		}
		
		// 更新血量和怒气
		if (!empty($this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT]))
		{
			$arrInherit = $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT];
			foreach ($battleFmt['arrHero'] as $pos => $heroInfo)
			{
				if (empty($arrInherit[$heroInfo['hid']]))
				{
					unset($battleFmt['arrHero'][$pos]);
				}
				else
				{
					$battleFmt['arrHero'][$pos]['currHp'] = $arrInherit[$heroInfo['hid']][0];
					$battleFmt['arrHero'][$pos]['currRage'] = $arrInherit[$heroInfo['hid']][1];
				}
			}
		}
		
		// 修改偏移量
		$battleFmt['uid'] = $battleFmt['uid'] * 10 + $offset;
		foreach ($battleFmt['arrHero'] as $pos => $hero)
		{
			$battleFmt['arrHero'][$pos]['hid'] = $hero['hid'] * 10 + $offset;
		}
		
		return $battleFmt;
	}
	
	/**
	 * 获得玩家的血量百分比
	 * 
	 * @throws InterException
	 * @return int
	 */
	public function getHpPercent()
	{
		// 获取玩家的战斗数据
		$battleFmt = $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_FMT];
		if (empty($battleFmt))
		{
			throw new InterException('no battle fmt');
		}
		
		// 如果没有血量信息，认为满血
		if (empty($this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT]))
		{
			return UNIT_BASE;
		}
		$arrInherit = $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT];
		
		// 获得总血量和当前血量
		$totalHp = 0;
		foreach ($battleFmt['arrHero'] as $pos => $heroInfo)
		{
			$totalHp += $heroInfo[PropertyKey::MAX_HP];
		}
		$currHp = 0;
		foreach ($arrInherit as $hid => $info)
		{
			$currHp += $info[0];
		}
		
		return intval(ceil($currHp / $totalHp * UNIT_BASE));
	}
	
	/**
	 * 玩家赢啦，分主动赢和被动赢，根据offset可以区分
	 * 如果是主动攻击别人并且胜利，需要增加攻击次数
	 * 
	 * @param array $atkRet
	 * @param int $offset
	 */
	public function win($atkRet, $offset)
	{
		// 主动攻击的玩家，需要增加已经挑战的次数
		if ($offset == WorldArenaDef::OFFSET_ONE) 
		{
			++$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_ATKED_NUM];
		}
		
		// 更新剩余血量和怒气
		$arrInherit = array();
		foreach ($atkRet as $index => $heroInfo)
		{
			if ($heroInfo['hp'] == 0) 
			{
				continue;
			}
			
			$orginHid = WorldArenaUtil::reChangeID($heroInfo['hid']);
			$arrInherit[$orginHid] = array($heroInfo['hp'], $heroInfo['rage']);
		}
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT] = $arrInherit;
	}
	
	/**
	 * 玩家输啦
	 * 设置为满血满怒
	 * 如果是主动攻击别人并且失败，需要增加攻击次数
	 * 
	 * @param int $offset
	 */
	public function lose($offset)
	{		
		// 主动攻击的玩家，需要增加已经挑战的次数
		if ($offset == WorldArenaDef::OFFSET_ONE)
		{
			++$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_ATKED_NUM];
		}
		
		// 设置满血满怒
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT] = array();
	}
	
	/**
	 * 获得玩家更新战斗信息的时间
	 * 
	 * @return int
	 */
	public function getUpdateFmtTime()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UPDATE_FMT_TIME];
	}
	
	/**
	 * 获得玩家上次主动攻打别人的时间
	 * 
	 * @return int
	 */
	public function getLastAttackTime()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_LAST_ATTACK_TIME];
	}
	
	/**
	 * 设置玩家上次主动攻打别人的时间
	 * 
	 * @param int $time
	 */
	public function setLastAttackTime($time)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_LAST_ATTACK_TIME] = $time;
	}
	
	/**
	 * 获得最后更新时间
	 * 
	 * @return int
	 */
	public function getUpdateTime()
	{
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UPDATE_TIME];
	}
	
	/**
	 * 更新之
	 */
	public function update()
	{
		if ($this->mObjModify == $this->mObj)
		{
			return;
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
			return;
		}
		
		if (!isset($arrUpdate[WorldArenaInnerUserField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrUpdate[WorldArenaInnerUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
		
		$arrCond = array
		(
				array(WorldArenaInnerUserField::TBL_FIELD_SERVER_ID, '=', $this->getServerId()),
				array(WorldArenaInnerUserField::TBL_FIELD_PID, '=', $this->getPid()),
		);
		
		$isMyserver = WorldArenaUtil::isMyServer($this->getServerId());
		$db = '';
		if (!$isMyserver)
		{
			$db = WorldArenaUtil::getServerDbByServerId($this->getServerId());
		}
		
		WorldArenaDao::updateInnerUser($arrCond, $arrUpdate, $db);
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	
	public function setUpdateFmtTimeForConsole($time)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_UPDATE_FMT_TIME] = $time;
	}
	
	public function setBuyAtkNumForConsole($num)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_BUY_ATK_NUM] = $num;
	}
	
	public function setSilverResetNumForConsole($num)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_SILVER_RESET_NUM] = $num;
	}
	
	public function setGoldResetNumForConsole($num)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_GOLD_RESET_NUM] = $num;
	}
	
	public function setAtkedNumForConsole($num)
	{
		$this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_ATKED_NUM] = $num;
	}
	
	public function getHpInfoForConsole()
	{
		if (empty($this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT])) 
		{
			return array();
		}
		
		return $this->mObjModify[WorldArenaInnerUserField::TBL_FIELD_VA_EXTRA][WorldArenaInnerUserField::TBL_VA_EXTRA_INHERIT];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */