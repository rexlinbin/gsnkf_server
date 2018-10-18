<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonObj.class.php 222370 2016-01-15 06:44:12Z NanaPeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/MoonObj.class.php $
 * @author $Author: NanaPeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-01-15 06:44:12 +0000 (Fri, 15 Jan 2016) $
 * @version $Revision: 222370 $
 * @brief 
 *  
 * 注意，普通boss记录的是剩余攻打次数，梦魇boss记录的是已经攻打次数
 **/
 
class MoonObj
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
	 * @return MoonObj
	*/
	public static function getInstance($uid = 0)
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
			self::$sArrInstance[$uid] = new MoonObj($uid);
		}

		return self::$sArrInstance[$uid];
	}

	/**
	 * 释放实例
	 * 
	 * @param int $uid
	 * @throws FakeException
	 */
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
	
	/**
	 * 获取某个副本初始化的格子信息
	 * 
	 * @param int $copyId
	 * @throws InterException
	 */
	public static function initGridInfo($copyId)
	{
		if (!isset(btstore_get()->MOON_COPY[$copyId])) 
		{
			throw new InterException('no config of copy id[%d]', $copyId);
		}
		
		$ret = array();
		$defaultOpenGrid = intval(btstore_get()->MOON_COPY[$copyId]['default_open_grid']);
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			$ret[$i] = ($i == $defaultOpenGrid ? MoonGridStatus::UNLOCK : MoonGridStatus::LOCK);
		}
		
		return $ret;
	}

	/**
	 * 构造函数
	 * 
	 * @param int $uid
	 */
	private function __construct($uid)
	{
		$this->mObj = $this->getMoonInfo($uid);
		if (empty($this->mObj))
		{
			$this->mObj = $this->createMoonInfo($uid);
		}
		$this->mObjModify = $this->mObj;
		$this->refresh();
	}

	/**
	 * 根据update_time刷新
	 */
	public function refresh()
	{
		if (!Util::isSameDay($this->mObjModify[MoonField::TBL_FIELD_UPDATE_TIME]))
		{
			$this->mObjModify[MoonField::TBL_FIELD_ATK_NUM] = intval(btstore_get()->MOON_RULE['default_atk_num']);
			$this->mObjModify[MoonField::TBL_FIELD_BUY_NUM] = 0;
			$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM] = 0;
			$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM] = 0;
			$this->mObjModify[MoonField::TBL_FIELD_BOX_NUM] = 0;
			$this->mObjModify[MoonField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
	}

	/**
	 * 从db获取这个玩家的信息
	 * 
	 * @param int $uid
	 * @return array
	 */
	public function getMoonInfo($uid)
	{
		$arrCond = array
		(
				array(MoonField::TBL_FIELD_UID, '=', $uid),
		);
		$arrBody = MoonField::$ALL_FIELDS;

		return MoonDao::selectUser($arrCond, $arrBody);
	}

	/**
	 * 初始化玩家信息
	 * 
	 * @param unknown $uid
	 * @return array
	 */
	public function createMoonInfo($uid)
	{
		$arrRet = array
		(
				MoonField::TBL_FIELD_UID => $uid,
				MoonField::TBL_FIELD_ATK_NUM => intval(btstore_get()->MOON_RULE['default_atk_num']),
				MoonField::TBL_FIELD_BUY_NUM => 0,
				MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM => 0,
				MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM => 0,
				MoonField::TBL_FIELD_BOX_NUM => 0,
				MoonField::TBL_FIELD_MAX_PASS_COPY => 0,
				MoonField::TBL_FIELD_MAX_NIGHTMARE_PASS_COPY => 0,
				MoonField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				MoonField::TBL_FIELD_VA_EXTRA => array(MoonField::TBL_VA_EXTRA_SUBFIELD_GRID => self::initGridInfo(1)),
		);
		MoonDao::insertUser($arrRet);

		return $arrRet;
	}

	/**
	 * 获得uid
	 * 
	 * @return int
	 */
	public function getUid()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_UID];
	}
	
	/**
	 * 获得攻击次数
	 * 
	 * @return int
	 */
	public function getAtkNum()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_ATK_NUM];
	}
	/**
	 * 获得梦魇boss剩余可攻击次数！注意与普通boss计数方式相反
	 *
	 * @return int
	 */
	public function getNightmareAtkNum()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM];
	}
	/**
	 * 减少攻击次数
	 */
	public function decreAtkNum()
	{
		--$this->mObjModify[MoonField::TBL_FIELD_ATK_NUM];
	}
	/**
	 * 增加梦魇攻击次数
	 * 注意，普通boss记录的是剩余攻打次数，梦魇boss记录的是已经攻打次数
	 */
	public function addNightmareAtkNum()
	{
		++$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM];
	}
	/**
	 * 获得今天已经购买的次数，用于计算购买攻击次数的消耗金币
	 * 
	 * @return int
	 */
	public function getBuyNum()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_BUY_NUM];
	}
	/**
	 * 获得今天已经购买的梦魇次数，用于计算购买攻击次数的消耗金币
	 *
	 * @return int
	 */
	public function getNightmareBuyNum()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM];
	}
	/**
	 * 获得今天已经购买的宝箱次数，用于计算购买宝箱的消耗金币
	 * 
	 * @return int
	 */
	public function getBoxNum()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_BOX_NUM];
	}
	
	/**
	 * 增加天工阁购买宝箱的次数
	 */
	public function increBoxNum()
	{
		++$this->mObjModify[MoonField::TBL_FIELD_BOX_NUM];
	}
	
	/**
	 * 购买一次攻击次数，攻击次数和购买次数都要加1
	 */
	public function buyAtkNum()
	{
		++$this->mObjModify[MoonField::TBL_FIELD_ATK_NUM];
		++$this->mObjModify[MoonField::TBL_FIELD_BUY_NUM];
	}
	/**
	 * 购买一次梦魇攻击次数，攻击次数-1，购买次数+1
	 */
	public function buyNightmareAtkNum()
	{
		//--$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM];
		++$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM];
	}
	/**
	 * 获取梦魇模式剩余可攻打次数（加上了购买次数）
	 * 
	 * */
	public function getNightmareCanAtkNum()
	{
		if(false == EnSwitch::isSwitchOpen(SwitchDef::TALLY))
		{
			return 0;//功能节点未开启，可攻击次数为0
		}
		$num = $this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM] + intval(btstore_get()->MOON_RULE['nightmare_num']);
		$atkNum = $this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM];
		if($num >= $atkNum)
		{
			return $num - $atkNum;
		}
		else 
		{
			throw new FakeException('atknum:%s > can atk num:%s.', $atkNum, $num);
		} 
	}
	/**
	 * 获得已经通关的最大副本id
	 * 
	 * @return int
	 */
	public function getMaxPassCopy()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_MAX_PASS_COPY];
	}
	/**
	 * 获得已经通关的最大梦魇boss副本id
	 *
	 * @return int
	 */
	public function getMaxNightmarePassCopy()
	{
		return $this->mObjModify[MoonField::TBL_FIELD_MAX_NIGHTMARE_PASS_COPY];
	}
	/**
	 * 判断是否是已经通关的副本
	 * 
	 * @param int $copyId
	 * @return boolean
	 */
	public function isCopyPass($copyId)
	{
		return $copyId > 0 && $copyId <= $this->getMaxPassCopy();
	}
	/**
	 * 判断是否是已经通关的梦魇副本
	 *
	 * @param int $copyId
	 * @return boolean
	 */
	public function isCopyNightmarePass($copyId)
	{
		return $copyId > 0 && $copyId <= $this->getMaxNightmarePassCopy();
	}
	/**
	 * 判断攻打梦魇副本顺序是否正确
	 *
	 * @param int $copyId
	 * @return boolean
	 */
	public function isNightmareOrderOK($copyId)
	{
		//如果攻打第一个梦魇boss，则判断是否解锁对应副本格子
		if($copyId == 1)
		{
			return !($this->isCopyLock($copyId));
		}
		return $this->isCopyNightmarePass($copyId - 1);//前一梦魇关卡通关	
	}
	
	/**
	 * 判断是否是还在锁定的副本
	 * 
	 * @param int $copyId
	 * @return boolean
	 */
	public function isCopyLock($copyId)
	{
		return $copyId > $this->getMaxPassCopy() + 1;
	}
	
	/**
	 * 判断是否是当前正在攻打的副本
	 * 
	 * @param int $copyId
	 * @return boolean
	 */
	public function isCurrCopy($copyId)
	{
		return $copyId == $this->getMaxPassCopy() + 1;
	}
	
	/**
	 * 获得当前攻打的副本Id
	 * 
	 * @return int
	 */
	public function getCurrCopy()
	{
		if (isset(btstore_get()->MOON_COPY[$this->getMaxPassCopy() + 1]))
		{
			return $this->getMaxPassCopy() + 1;
		}
		else 
		{
			return $this->getMaxPassCopy();
		}
	}
	
	/**
	 * 获得当前攻打的梦魇副本Id
	 *
	 * @return int
	 
	public function getCurrNightmareCopy()
	{
		if (isset(btstore_get()->MOON_COPY[$this->getMaxNightmarePassCopy() + 1]))
		{
			return $this->getMaxNightmarePassCopy() + 1;
		}
		else
		{
			return $this->getMaxNightmarePassCopy();
		}
	}*/
	
	
	/**
	 * 将当前攻打的副本置为通关，如果不是最后一个副本，还需要同时初始化下个副本的信息
	 */
	public function passCurrCopy()
	{
		// 检查是否所有的格子都已经处理过啦
		if (!$this->allGridDone()) 
		{
			throw new InterException('not all grid done, but want to pass. grid info[%s]', $this->getGridInfo());
		}
		
		// 增加最大通关副本id
		++$this->mObjModify[MoonField::TBL_FIELD_MAX_PASS_COPY];
		
		// 如果不是最后一个副本，则需要初始化下个副本信息
		if (isset(btstore_get()->MOON_COPY[$this->getMaxPassCopy() + 1])) 
		{
			$this->setGridInfo(self::initGridInfo($this->getMaxPassCopy() + 1));
		}
		else // 如果通关的是最后一个副本，将格子信息设置为空数组
		{
			$this->setGridInfo(array());
		}
	}
	
	/**
	 * 梦魇通关副本增加
	 */
	public function passNightmareCopy()
	{
		// 增加最大通关副本id
		++$this->mObjModify[MoonField::TBL_FIELD_MAX_NIGHTMARE_PASS_COPY];
	}
	/**
	 * 获得格子的状态信息
	 */
	public function getGridInfo()
	{
		// 玩家第一次进入水月之境
		if (!isset($this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID])) 
		{
			$this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID] = self::initGridInfo($this->getMaxPassCopy() + 1);
		}
		// 玩家通关了最后一个副本的话，会将grid设置为空数组
		else if (is_array($this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID])
				&& empty($this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID])) 
		{
			// 如果新加了副本，格子需要初始化为新副本的格子信息
			if (isset(btstore_get()->MOON_COPY[$this->getMaxPassCopy() + 1]))
			{
				$this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID] = self::initGridInfo($this->getMaxPassCopy() + 1);
			}
		}
		
		return $this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID];
	}
	
	/**
	 * 设置格子信息
	 * 
	 * @param array $gridInfo
	 */
	private function setGridInfo($gridInfo)
	{
		$this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA][MoonField::TBL_VA_EXTRA_SUBFIELD_GRID] = $gridInfo;
	}
	
	/**
	 * 获得一个格子的状态信息
	 * 
	 * @param int $gridId
	 * @throws InterException
	 * @return int
	 */
	public function gridStatus($gridId)
	{
		if ($gridId <= 0 || $gridId > MoonConf::MAX_GRID_NUM)
		{
			throw new InterException('invalid grid id[%d], grid max num[%d]', $gridId, MoonConf::MAX_GRID_NUM);
		}
		
		$arrGridInfo = $this->getGridInfo();
		return $arrGridInfo[$gridId];
	}
	
	/**
	 * 判断某个格子是否已经处理完啦
	 * 
	 * @param int $gridId
	 * @return boolean
	 */
	public function isGridDone($gridId)
	{
		return $this->gridStatus($gridId) == MoonGridStatus::DONE;
	}
	
	/**
	 * 判断某个格子是否处于锁定状态
	 *
	 * @param int $gridId
	 * @return boolean
	 */
	public function isGridLock($gridId)
	{
		return $this->gridStatus($gridId) == MoonGridStatus::LOCK;
	}
	
	/**
	 * 判断某个格子是否处于解锁状态
	 *
	 * @param int $gridId
	 * @return boolean
	 */
	public function isGridUnlock($gridId)
	{
		return $this->gridStatus($gridId) == MoonGridStatus::UNLOCK;
	}
	
	/**
	 * 判断当前副本的所有的格子是否已经都处理过啦
	 * 
	 * @return boolean
	 */
	public function allGridDone()
	{
		for ($i = 1; $i <= MoonConf::MAX_GRID_NUM; ++$i)
		{
			if (!$this->isGridDone($i)) 
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * 设置某个格子的状态
	 * 
	 * @param int $gridId
	 * @param int $status
	 * @throws InterException
	 */
	private function setGridStatus($gridId, $status)
	{
		if ($gridId <= 0 || $gridId > MoonConf::MAX_GRID_NUM)
		{
			throw new InterException('invalid grid id[%d], grid max num[%d]', $gridId, MoonConf::MAX_GRID_NUM);
		}
		
		$gridInfo = $this->getGridInfo();
		$gridInfo[$gridId] = $status;
		$this->setGridInfo($gridInfo);
	}
	
	/**
	 * 将某个格子的状态置为done状态
	 * 
	 * @param int $gridId
	 * @throws InterException
	 */
	public function doneGrid($gridId)
	{
		$curStatus = $this->gridStatus($gridId);
		
		if ($curStatus == MoonGridStatus::DONE) 
		{
			Logger::warning('grid[%d] already done.', $gridId);
			return;
		}
		if ($curStatus == MoonGridStatus::LOCK) 
		{
			throw new InterException('grid[%d] is lock now, but want to done grid.', $gridId);
		}
		
		$this->setGridStatus($gridId, MoonGridStatus::DONE);
	}
	
	/**
	 * 解锁一组grid
	 * 
	 * @param array $arrUnlockGrid
	 * @return array 返回这个开启的新的格子id
	 */
	public function unlockGrid($arrUnlockGrid)
	{
		$arrRealUnlockGrid = array();
		
		foreach ($arrUnlockGrid as $gridId)
		{
			$curStatus = $this->gridStatus($gridId);
			if ($curStatus == MoonGridStatus::LOCK)
			{
				$this->setGridStatus($gridId, MoonGridStatus::UNLOCK);
				$arrRealUnlockGrid[] = $gridId;
			}
		}
		
		return $arrRealUnlockGrid;
	}
	
	/**
	 * update之
	 */
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
			Logger::debug('update MoonObj : no change');
			return;
		}
	
		if (!isset($arrField[MoonField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrField[MoonField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
	
		Logger::debug("update MoonObj uid:%d changed field:%s", $this->getUid(), $arrField);
	
		$arrCond = array
		(
				array(MoonField::TBL_FIELD_UID, '=', $this->getUid()),
		);
		MoonDao::updateUser($arrCond, $arrField);
	
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	
	public function setMaxPassCopyForConsole($num)
	{
		$this->mObjModify[MoonField::TBL_FIELD_MAX_PASS_COPY] = $num;
	}
	
	public function setGridInfoForConsole($gridInfo)
	{
		$this->setGridInfo($gridInfo);
	}
	
	public function setAtkNumForConsole($num)
	{
		$this->mObjModify[MoonField::TBL_FIELD_ATK_NUM] = $num;
	}
	
	public function setBuyNumForConsole($num)
	{
		$this->mObjModify[MoonField::TBL_FIELD_BUY_NUM] = $num;
	}
	
	public function setBuyBoxNumForConsole($num)
	{
		$this->mObjModify[MoonField::TBL_FIELD_BOX_NUM] = $num;
	}
	
	public function setGridStatusForConsole($gridId, $status)
	{
		$this->setGridStatus($gridId, $status);
	}
	
	public function setUpdateTimeForConsole($time)
	{
		$this->mObjModify[MoonField::TBL_FIELD_UPDATE_TIME] = $time;
	}
	
	public function resetForConsole()
	{
		$this->mObjModify[MoonField::TBL_FIELD_ATK_NUM] = intval(btstore_get()->MOON_RULE['default_atk_num']);
		$this->mObjModify[MoonField::TBL_FIELD_BUY_NUM] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_BOX_NUM] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_MAX_PASS_COPY] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_MAX_NIGHTMARE_PASS_COPY] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		$this->mObjModify[MoonField::TBL_FIELD_VA_EXTRA] = array(MoonField::TBL_VA_EXTRA_SUBFIELD_GRID => self::initGridInfo(1));
	}
	public function resetNighMareForConsole()
	{
		$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_ATK_NUM] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_NIGHTMARE_BUY_NUM] = 0;
		$this->mObjModify[MoonField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */