<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyUserObj.class.php 232256 2016-03-11 07:50:02Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopyUserObj.class.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-11 07:50:02 +0000 (Fri, 11 Mar 2016) $
 * @version $Revision: 232256 $
 * @brief 
 *  
 **/

class GuildCopyUserObj
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
	 * @return GuildCopyUserObj
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
			self::$sArrInstance[$uid] = new GuildCopyUserObj($uid);
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
		$this->mObj = $this->getGuildCopyUserInfo($uid);
		if (empty($this->mObj))
		{
			$this->mObj = $this->createGuildCopyUserInfo($uid);
		}
		$this->mObjModify = $this->mObj;
		$this->refresh();
	}
	
	/**
	 * 这里刷新的时候要注意，当update_time时昨天的时候，要记录atk_damage到atk_damage_last上
	 * 用于发昨天的全服排名奖励
	 */
	public function refresh()
	{
		if (!Util::isSameDay($this->mObjModify[GuildCopyUserField::TBL_FIELD_UPDATE_TIME])) 
		{
			if (Util::getDaysBetween($this->mObjModify[GuildCopyUserField::TBL_FIELD_UPDATE_TIME]) == 1) 
			{
				$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST] = $this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE];
				
				Logger::trace('GuildCopyUserObj refresh: update time[%s], curr time[%s], set atk_damage_last with atk_damage[%d]', 
								strftime('%Y%m%d %H%M%S', $this->mObjModify[GuildCopyUserField::TBL_FIELD_UPDATE_TIME]),
								strftime('%Y%m%d %H%M%S', Util::getTime()),
								$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE]);
			}
			else 
			{
				$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST] = 0;
				
				Logger::trace('GuildCopyUserObj refresh: update time[%s], curr time[%s], set atk_damage_last with 0', 
								strftime('%Y%m%d %H%M%S', $this->mObjModify[GuildCopyUserField::TBL_FIELD_UPDATE_TIME]),
								strftime('%Y%m%d %H%M%S', Util::getTime()));
			}
			
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE] = 0;
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM] = intval(btstore_get()->GUILD_COPY_RULE['default_atk_num']);
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_NUM] = 0;
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA] = array();
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
			
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_BOSS_NUM] = 0;
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_BOSS_NUM] = 0;
		}
	}
	
	public function getGuildCopyUserInfo($uid)
	{
		$arrCond = array
		(
				array(GuildCopyUserField::TBL_FIELD_UID, '=', $uid),
		);
		$arrBody = GuildCopyUserField::$GUILD_COPY_USER_ALL_FIELDS;
	
		return GuildCopyDao::selectUser($arrCond, $arrBody);
	}
	
	public function createGuildCopyUserInfo($uid)
	{
		$arrRet = array
		(
				GuildCopyUserField::TBL_FIELD_UID => $uid,
				GuildCopyUserField::TBL_FIELD_ATK_DAMAGE => 0,
				GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST => 0,
				GuildCopyUserField::TBL_FIELD_ATK_NUM => intval(btstore_get()->GUILD_COPY_RULE['default_atk_num']),
				GuildCopyUserField::TBL_FIELD_BUY_NUM => 0,
				GuildCopyUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME => 0,
				GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME => 0,
				GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME => 0,
				GuildCopyUserField::TBL_FIELD_REFRESH_TIME => 0,
				GuildCopyUserField::TBL_FIELD_VA_EXTRA => array(),
				
				GuildCopyUserField::TBL_FIELD_ATK_BOSS_NUM => 0,
				GuildCopyUserField::TBL_FIELD_BUY_BOSS_NUM => 0,
		);
		GuildCopyDao::insertUser($arrRet);
	
		return $arrRet;
	}
	
	public function getUid()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_UID];
	}
	
	/**
	 * 获得玩家今天的总伤害
	 * 
	 * @return int
	 */
	public function getAtkDamage()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE];
	}
	
	/**
	 * 增加玩家今天的总伤害
	 * 
	 * @param int $num
	 */
	public function addAtkDamage($num)
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE] += $num;
	}
	
	/**
	 * 获得玩家今天对某一个据点的总伤害
	 * 
	 * @param int $baseIndex
	 * @return number
	 */
	public function getBaseDamage($baseIndex)
	{
		if (!isset($this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA][GuildCopyUserField::TBL_VA_EXTRA_SUBFIELD_DAMAGE][$baseIndex])) 
		{
			return 0;
		}
		
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA][GuildCopyUserField::TBL_VA_EXTRA_SUBFIELD_DAMAGE][$baseIndex];
	}
	
	/**
	 * 增加玩家今天对某一个据点的总伤害
	 * 
	 * @param int $baseIndex
	 * @param int $damage
	 */
	public function addBaseDamage($baseIndex, $damage)
	{
		if (!isset($this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA][GuildCopyUserField::TBL_VA_EXTRA_SUBFIELD_DAMAGE][$baseIndex]))
		{
			$this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA][GuildCopyUserField::TBL_VA_EXTRA_SUBFIELD_DAMAGE][$baseIndex] = 0;
		}
		
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA][GuildCopyUserField::TBL_VA_EXTRA_SUBFIELD_DAMAGE][$baseIndex] += $damage;
	}
	
	/**
	 * 获得玩家今天可以攻击的次数
	 * 
	 * @return int
	 */
	public function getAtkNum()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM];
	}
	
	/**
	 * 增加玩家今天攻击的次数
	 * 
	 * @param int $num
	 */
	public function addAtkNum($num)
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM] += $num;
	}
	
	/**
	 * 减少玩家攻击的次数，每次最多能减少1次次数
	 * 
	 * @return boolean
	 */
	public function decreAtkNum()
	{
		if ($this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM] <= 0) 
		{
			return FALSE;
		}
		
		--$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM];
		return TRUE;
	}
	
	/**
	 * 今天购买的次数
	 * 
	 * @return int
	 */
	public function getBuyNum()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_NUM];
	}
	
	/**
	 * 购买一次攻击次数，atk_num和buy_num都要增加，buy_num用于计算玩家购买的花费
	 */
	public function buy()
	{
		++$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM];
		++$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_NUM];
	}
	
	/**
	 * 领取通关阳光普照奖励的时间，这个时间refresh不会刷掉，仅代表这个时间所在当天领取奖励的时间
	 * @return int
	 */
	public function getRecvPassRewardTime()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME];
	}
	
	/**
	 * 玩家今天是否领取了阳光普照奖
	 * 
	 * @return bool
	 */
	public function isRecvPassReward()
	{
		return Util::isSameDay($this->getRecvPassRewardTime());
	}
	
	/**
	 * 设置领取阳光普照奖时间
	 */
	public function recvPassReward()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME] = Util::getTime();
	}
	
	/**
	 * 领取通关宝箱奖励的时间，这个时间refresh不会刷掉，仅代表这个时间所在当天领取奖励的时间
	 * 
	 * @return int
	 */
	public function getRecvBoxRewardTime()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME];
	}
	
	/**
	 * 玩家今天是否领取了宝箱奖
	 * 
	 * @return boolean
	 */
	public function isRecvBoxReward()
	{
		return Util::isSameDay($this->getRecvBoxRewardTime());
	}
	
	/**
	 * 设置玩家领取宝箱奖励的时间
	 */
	public function recvBoxReward()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME] = Util::getTime();
	}
	
	/**
	 * 玩家使用“全团突击”的时间，这个时间refresh不会刷掉，仅代表这个时间所在当天"全团突击"的时间
	 * 
	 * @return multitype:
	 */
	public function getRefreshTime()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_REFRESH_TIME];
	}
	
	/**
	 * 玩家今天是否使用过“全团突击”
	 * 
	 * @return boolean
	 */
	public function isRefresh()
	{
		return Util::isSameDay($this->getRefreshTime());
	}
	
	/**
	 * 设置玩家"全团突击"的时间
	 * 
	 * @param number $time
	 */
	public function setRefreshTime($time = 0)
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_REFRESH_TIME] = ($time == 0 ? Util::getTime() : $time);
	}
	
	/**
	 * 领取全服排名奖励的时间
	 * 注意！！！这个时间代表的是上一天全服排行奖励的领取时间
	 * 
	 * @return int
	 */
	public function getRecvRankRewardTime()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME];
	}
	
	/**
	 * 是否领取了昨天全服排行奖励
	 * 
	 * @return boolean
	 */
	public function isRecvRankReward()
	{
		return Util::isSameDay($this->getRecvRankRewardTime());
	}
	
	/**
	 * 设置领取昨天全服排行奖励的时间
	 */
	public function recvRankReward()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME] = Util::getTime();
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
			Logger::debug('update GuildCopyUserObj : no change');
			return;
		}
		
		if (!isset($arrField[GuildCopyUserField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrField[GuildCopyUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
	
		Logger::debug("update GuildCopyUserObj uid:%d changed field:%s", $this->getUid(), $arrField);
	
		$arrCond = array
		(
				array(GuildCopyUserField::TBL_FIELD_UID, '=', $this->getUid()),
		);
		GuildCopyDao::updateUser($arrCond, $arrField);
	
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	public function resetForTest()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_NUM] = intval(btstore_get()->GUILD_COPY_RULE['default_atk_num']);
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_NUM] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_REFRESH_TIME] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_VA_EXTRA] = array();
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_BOSS_NUM] = 0;
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_BOSS_NUM] = 0;
	}
	
	public function setBuyNumForTest($num)
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_NUM] = $num;
	}

	public function resetRefreshTimeForTest()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_REFRESH_TIME] = 0;
	}
	
	public function resetRecvPassRewardTimeForTest()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME] = 0;
	}
	
	public function resetRecvBoxRewardTimeForTest()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME] = 0;
	}
	
	public function resetRecvRankRewardTimeForTest()
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME] = 0;
	}
	
	public function getBossAtkNum()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_BOSS_NUM];
	}
	
	public function addBossAtkNum($Num)
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_ATK_BOSS_NUM] += $Num;
	}
	
	public function getBuyBossNum()
	{
		return $this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_BOSS_NUM];
	}
	
	public function addBuyBossNum($Num)
	{
		$this->mObjModify[GuildCopyUserField::TBL_FIELD_BUY_BOSS_NUM] += $Num;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */