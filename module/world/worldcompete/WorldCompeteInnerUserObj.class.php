<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldCompeteInnerUserObj.class.php 205453 2015-10-28 07:20:22Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcompete/WorldCompeteInnerUserObj.class.php $
 * @author $Author: MingTian $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-10-28 07:20:22 +0000 (Wed, 28 Oct 2015) $
 * @version $Revision: 205453 $
 * @brief 
 *  
 **/
 
class WorldCompeteInnerUserObj
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
	 * @static
	 * @access public
	 * @return WorldCompeteInnerUserObj
	 */
	public static function getInstance($serverId, $pid, $uid = 0)
	{
		$key = WorldCompeteUtil::getKey($serverId, $pid);
		if (!isset(self::$sArrInstance[$key]))
		{
			self::$sArrInstance[$key] = new self($serverId, $pid, $uid);
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
		$key = WorldCompeteUtil::getKey($serverId, $pid);
		if (isset(self::$sArrInstance[$key]))
		{
			unset(self::$sArrInstance[$key]);
		}
	}

	/**
	 * 构造函数
	 * 
	 * @param int $serverId 玩家所在服务器serverId
	 * @param int $pid 玩家pid
	 * @parma int $uid 玩家uid
	 */
	private function __construct($serverId, $pid, $uid = 0)
	{
		$isMyserver = WorldCompeteUtil::isMyServer($serverId);
		$info = $this->getInfo($serverId, $pid);
		if (empty($info))
		{
			if ($isMyserver) 
			{
				$info = $this->createInfo($serverId, $pid, $uid);
			}
			else 
			{
				throw new FakeException("not my server, serverId[%d], pid[%d].", $serverId, $pid);
			}
		}
		
		$this->mObj = $info;
		$this->mObjModify = $info;
		$this->refresh();
	}
	
	/**
	 * 从db中获取数据
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @return array
	 */
	public function getInfo($serverId, $pid)
	{	
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_SERVER_ID, '=', $serverId),
				array(WorldCompeteInnerUserField::FIELD_PID, '=', $pid),
		);
		$arrField = WorldCompeteInnerUserField::$ALL_FIELDS;
		
		return WorldCompeteDao::selectInnerUser($arrCond, $arrField);
	}
	
	/**
	 * 如果第一次进入跨服比武，需要插入初始化数据
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @param int $uid
	 * @return array
	 */
	public function createInfo($serverId, $pid, $uid = 0)
	{
		if (empty($uid)) 
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		$initInfo = array
		(
				WorldCompeteInnerUserField::FIELD_PID => $pid,
				WorldCompeteInnerUserField::FIELD_SERVER_ID => $serverId,
				WorldCompeteInnerUserField::FIELD_UID => $uid,
				WorldCompeteInnerUserField::FIELD_ATK_NUM => 0,
				WorldCompeteInnerUserField::FIELD_SUC_NUM => 0,
				WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM => 0,
				WorldCompeteInnerUserField::FIELD_REFRESH_NUM => 0,
				WorldCompeteInnerUserField::FIELD_WORSHIP_NUM => 0,
				WorldCompeteInnerUserField::FIELD_MAX_HONOR => 0,
				WorldCompeteInnerUserField::FIELD_CROSS_HONOR => 0,
				WorldCompeteInnerUserField::FIELD_HONOR_TIME => 0,
				WorldCompeteInnerUserField::FIELD_UPDATE_TIME => Util::getTime(),
				WorldCompeteInnerUserField::FIELD_REWARD_TIME => 0,
				WorldCompeteInnerUserField::FIELD_VA_EXTRA => array(),
		);
		WorldCompeteDao::insertInnerUser($initInfo);
		
		return $initInfo;
	}
	
	/**
	 * 需要按周刷新
	 */
	public function refresh()
	{
		//每日刷新：比武次数，胜利次数，购买比武次数，刷新对手次数，膜拜次数,已领取胜场奖励
		if (!Util::isSameDay($this->getUpdateTime())) 
		{
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_ATK_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_SUC_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_REFRESH_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_WORSHIP_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::PRIZE] = array();
		}
		//每周刷新：最大荣誉，最大荣誉时间
		if (!WorldCompeteUtil::inSamePeriod($this->getUpdateTime())) 
		{
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_ATK_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_SUC_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_REFRESH_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_WORSHIP_NUM] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_MAX_HONOR] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_HONOR_TIME] = 0;
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA] = array();
		}
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_UPDATE_TIME] = Util::getTime();
	}
	
	/**
	 * 获得serverId
	 * 
	 * @return int
	 */
	public function getServerId()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_SERVER_ID];
	}
	
	/**
	 * 获得pid
	 * 
	 * @return int
	 */
	public function getPid()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_PID];
	}
	
	/**
	 * 获得uid
	 * 
	 * @return int
	 */
	public function getUid()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_UID];
	}
	
	/**
	 * 获得atk_num
	 * 
	 * @return int
	 */
	public function getAtkNum()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_ATK_NUM];
	}
	
	/**
	 * 增加atk_num
	 * 
	 * @param int $num
	 */
	public function addAtkNum($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_ATK_NUM] += $num;
	}
	
	/**
	 * 获得suc_num
	 *
	 * @return int
	 */
	public function getSucNum()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_SUC_NUM];
	}
	
	/**
	 * 增加suc_num
	 *
	 * @param int $num
	 */
	public function addSucNum($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_SUC_NUM] += $num;
	}
	
	/**
	 * 获得buy_atk_num
	 *
	 * @return int
	 */
	public function getBuyAtkNum()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM];
	}
	
	/**
	 * 增加buy_atk_num
	 *
	 * @param int $num
	 */
	public function addBuyAtkNum($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM] += $num;
	}
	
	/**
	 * 获得refresh_num
	 *
	 * @return int
	 */
	public function getRefreshNum()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_REFRESH_NUM];
	}
	
	/**
	 * 增加refresh_num
	 *
	 * @param int $num
	 */
	public function addRefreshNum($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_REFRESH_NUM] += $num;
	}
	
	/**
	 * 获得worship_num
	 *
	 * @return int
	 */
	public function getWorshipNum()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_WORSHIP_NUM];
	}
	
	/**
	 * 增加worship_num
	 *
	 * @param int $num
	 */
	public function addWorshipNum($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_WORSHIP_NUM] += $num;
	}
	
	/**
	 * 获得max_honor
	 * 
	 * @return int
	 */
	public function getMaxHonor()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_MAX_HONOR];
	}
	
	/**
	 * 增加max_honor
	 * 同时增加cross_honor
	 *
	 * @param int $num
	 */
	public function addMaxHonor($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_MAX_HONOR] += $num;
		$this->addCrossHonor($num);
		$this->setHonorTime(Util::getTime());
	}
	
	/**
	 * 增加max_honor
	 *
	 * @param int $num
	 */
	public function addMaxHonorOnly($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_MAX_HONOR] += $num;
		$this->setHonorTime(Util::getTime());
	}
	
	/**
	 * 获得cross_honor
	 *
	 * @return int
	 */
	public function getCrossHonor()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR];
	}
	
	/**
	 * 加减cross_honor
	 * 
	 * @param int $num
	 * @return boolean
	 */
	public function addCrossHonor($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR] += $num;
		
		if ($this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR] > WorldCompeteConf::CROSS_HONOR_MAX) 
		{
			Logger::fatal('cross honor[%d] reach max[%d]', $this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR], WorldCompeteConf::CROSS_HONOR_MAX);
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR] = WorldCompeteConf::CROSS_HONOR_MAX;
		}
		
		if ($this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR] < 0) 
		{
			Logger::warning('invalid cross honor. sub[%d], now[%d]', $num, $this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR]);
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR] = 0;
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * 获得honor_time
	 * 
	 * @return int
	 */
	public function getHonorTime()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_HONOR_TIME];
	}
	
	/**
	 * 设置honor_time
	 * 
	 * @param int $time
	 */
	public function setHonorTime($time)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_HONOR_TIME] = $time;
	}
	
	/**
	 * 获得update_time
	 *
	 * @return int
	 */
	public function getUpdateTime()
	{
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_UPDATE_TIME];
	}
	
	/**
	 * 获得rival
	 * 
	 * @return array
	 */
	public function getRival()
	{
		if (!isset($this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::RIVAL])) 
		{
			return array();
		}
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::RIVAL];
	}
	
	/**
	 * 刷新rival
	 * 
	 * @param int $teamId
	 * @param int $honor
	 * @param bool $isSys 1系统刷新0用户刷新,默认0
	 * @return array
	 * {
	 * 		index => array
	 * 		{
	 * 			server_id
	 * 			server_name
	 * 			pid
	 * 			uname
	 * 			htid
	 * 			level
	 * 			vip
	 * 			fight_force
	 * 			dress
	 * 			status							status为0是失败,1是成功
	 * 		}		
	 * }
	 */
	public function refreshRival($teamId, $honor, $isSys = FALSE)
	{
		$arrRivalInfo = WorldCompeteUtil::refreshRival($teamId, $honor); 
		foreach ($arrRivalInfo as $key => $info)
		{
			$this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::RIVAL][$key] = array
			(
					'server_id' => $info['server_id'],
					'pid' => $info['pid'],
					'status' => $info['status'],
			);
		}                                                                                                                                           
		if (!$isSys)
		{
			$this->addRefreshNum(1);
		}
		return $arrRivalInfo;
	}
	
	/**
	 * 是否有这个对手
	 * 
	 * @param int $rivalServerId
	 * @param int $rivalPid
	 * @return boolean
	 */
	public function hasRival($rivalServerId, $rivalPid)
	{
		$rival = $this->getRival();
		foreach ($rival as $key => $value)
		{
			if ($value['server_id'] == $rivalServerId && $value['pid'] == $rivalPid)
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 更新对手信息
	 * 
	 * @param int $rivalServerId
	 * @param int $rivalPid
	 * @param bool $isSuc
	 */
	public function defeatRival($rivalServerId, $rivalPid, $isSuc)
	{
		$rival = $this->getRival();
		foreach ($rival as $key => $value)
		{
			if ($value['server_id'] == $rivalServerId && $value['pid'] == $rivalPid) 
			{
				$this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::RIVAL][$key]['status'] = intval($isSuc);
			}
		}
	}
	
	/**
	 * 是否战胜所有对手
	 * 
	 * @return boolean
	 */
	public function isDefeatAll()
	{
		$rival = $this->getRival();
		foreach ($rival as $key => $value)
		{
			if (!$value['status'])
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * 获得prize
	 * 
	 * @return array
	 */
	public function getPrize()
	{
		if (!isset($this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::PRIZE]))
		{
			return array();
		}
		return $this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::PRIZE];
	}
	
	/**
	 * 增加prize
	 * 
	 * @param int $num
	 */
	public function addPrize($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::PRIZE][] = $num;
	}
	
	/**
	 * 是否获得胜场奖励
	 * 
	 * @param int $num
	 * @return boolean
	 */
	public function hasPrize($num)
	{
		$prize = $this->getPrize();
		return in_array($num, $prize);
	}

	/**
	 * 挑战完处理的数据
	 * 更新跨服用户数据
	 * 
	 * @param int $rivalServerId	对手serverId
	 * @param int $rivalPid			对手pid
	 * @param int $num 				消耗挑战次数
	 * @param int $honor			用户获得的荣誉
	 * @param int $isSuc			胜利或失败
	 * @param bool 
	 */
	public function afterAttack($rivalServerId, $rivalPid, $num, $honor, $isSuc)
	{
		$this->addAtkNum($num);
		$this->addSucNum($isSuc);
		$this->addMaxHonor($honor);
		$this->defeatRival($rivalServerId, $rivalPid, $isSuc);
	}
	
	/**
	 * 获得玩家实际的排名，荣誉值>荣誉时间>uid
	 *  
	 * @return int
	 */
	public function getRank()
	{
		//大于用户荣誉值的人数
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				array(WorldCompeteInnerUserField::FIELD_MAX_HONOR, '>', $this->getMaxHonor()),
		);
		$rank = WorldCompeteDao::getInnerCount($arrCond);
			
		//等于用户荣誉值，小于用户时间的人数
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				array(WorldCompeteInnerUserField::FIELD_MAX_HONOR, '=', $this->getMaxHonor()),
				array(WorldCompeteInnerUserField::FIELD_HONOR_TIME, '<', $this->getHonorTime()),
		);
		$rank += WorldCompeteDao::getInnerCount($arrCond);
			
		//等于用户荣誉值，等于用户时间，uid小于用户的人数
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_UPDATE_TIME, '>=', WorldCompeteUtil::activityBeginTime()),
				array(WorldCompeteInnerUserField::FIELD_MAX_HONOR, '=', $this->getMaxHonor()),
				array(WorldCompeteInnerUserField::FIELD_HONOR_TIME, '=', $this->getHonorTime()),
				array(WorldCompeteInnerUserField::FIELD_UID, '<', $this->getUid()),
		);
		$rank += WorldCompeteDao::getInnerCount($arrCond) + 1;
		
		return $rank;
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
		
		if (!isset($arrUpdate[WorldCompeteInnerUserField::FIELD_UPDATE_TIME]))
		{
			$arrUpdate[WorldCompeteInnerUserField::FIELD_UPDATE_TIME] = Util::getTime();
		}
		
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_SERVER_ID, '=', $this->getServerId()),
				array(WorldCompeteInnerUserField::FIELD_PID, '=', $this->getPid()),
		);
		WorldCompeteDao::updateInnerUser($arrCond, $arrUpdate);
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	
	public function setAtkNumForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_ATK_NUM] = $num;
	}
	
	public function setSucNumForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_SUC_NUM] = $num;
	}
	
	public function setBuyAtkNumForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_BUY_ATK_NUM] = $num;
	}
	
	public function setRefreshNumForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_REFRESH_NUM] = $num;
	}
	
	public function setWorshipNumForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_WORSHIP_NUM] = $num;
	}
	
	public function setMaxHonorForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_MAX_HONOR] = $num;
		$this->setHonorTime(Util::getTime());
	}
	
	public function setCrossHonorForConsole($num)
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_CROSS_HONOR] = $num;
	}
	
	public function resetPrizeForConsole()
	{
		$this->mObjModify[WorldCompeteInnerUserField::FIELD_VA_EXTRA][WorldCompeteInnerUserField::PRIZE] = array();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */