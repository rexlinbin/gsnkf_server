<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldPassInnerUserObj.class.php 180319 2015-06-24 04:01:25Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/WorldPassInnerUserObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-24 04:01:25 +0000 (Wed, 24 Jun 2015) $
 * @version $Revision: 180319 $
 * @brief 
 *  
 **/
 
class WorldPassInnerUserObj
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
	 * @return WorldPassInnerUserObj
	 */
	public static function getInstance($serverId, $pid, $uid = 0)
	{
		$key = self::getKey($serverId, $pid);
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
	 * @parma int $uid 玩家uid
	 */
	private function __construct($serverId, $pid, $uid = 0)
	{
		$isMyserver = WorldPassUtil::isMyServer($serverId);
		$userInfo = $this->getInfo($serverId, $pid);
		if (empty($userInfo))
		{
			if ($isMyserver) 
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
	 * @return array
	 */
	public function getInfo($serverId, $pid)
	{	
		$arrCond = array
		(
				array(WorldPassInnerUserField::TBL_FIELD_SERVER_ID, '=', $serverId),
				array(WorldPassInnerUserField::TBL_FIELD_PID, '=', $pid),
		);
		$arrField = WorldPassInnerUserField::$ALL_FIELDS;
		
		return WorldPassDao::selectInnerUser($arrCond, $arrField);
	}
	
	/**
	 * 如果第一次进入闯关赛，需要插入初始化数据
	 * 
	 * @param int $serverId
	 * @param int $pid
	 * @parma int $uid
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
				WorldPassInnerUserField::TBL_FIELD_PID => $pid,
				WorldPassInnerUserField::TBL_FIELD_SERVER_ID => $serverId,
				WorldPassInnerUserField::TBL_FIELD_UID => $uid,
				WorldPassInnerUserField::TBL_FIELD_PASSED_STAGE => 0,
				WorldPassInnerUserField::TBL_FIELD_MAX_POINT => 0,
				WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME => 0,
				WorldPassInnerUserField::TBL_FIELD_CURR_POINT => 0,
				WorldPassInnerUserField::TBL_FIELD_HELL_POINT => 0,
				WorldPassInnerUserField::TBL_FIELD_ATK_NUM => intval(btstore_get()->WORLD_PASS_RULE['default_atk_num']),
				WorldPassInnerUserField::TBL_FIELD_BUY_ATK_NUM => 0,
				WorldPassInnerUserField::TBL_FIELD_REFRESH_NUM => 0,
				WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				WorldPassInnerUserField::TBL_FIELD_REWARD_TIME => 0,
				WorldPassInnerUserField::TBL_FIELD_VA_EXTRA => array(),
		);
		WorldPassDao::insertInnerUser($initInfo);
		
		return $initInfo;
	}
	
	/**
	 * 需要按周刷新
	 */
	public function refresh()
	{
		if (!WorldPassUtil::inSamePeriod($this->mObjModify[WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME])) 
		{
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_PASSED_STAGE] = 0;
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_MAX_POINT] = 0;
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME] = 0;
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_CURR_POINT] = 0;
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_ATK_NUM] = intval(btstore_get()->WORLD_PASS_RULE['default_atk_num']);
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_BUY_ATK_NUM] = 0;
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_REFRESH_NUM] = 0;
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA] = array();
		}
	}
	
	/**
	 * 获得serverId
	 * 
	 * @return int
	 */
	public function getServerId()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_SERVER_ID];
	}
	
	/**
	 * 获得pid
	 * 
	 * @return int
	 */
	public function getPid()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_PID];
	}
	
	/**
	 * 获得uid
	 * 
	 * @return int
	 */
	public function getUid()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_UID];
	}
	
	/**
	 * 获得最大通关数
	 * 
	 * @return int
	 */
	public function getPassedStage()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_PASSED_STAGE];
	}
	
	/**
	 * 增加已经通关的数量
	 */
	private function increPassedStage()
	{
		++$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_PASSED_STAGE];
	}
	
	/**
	 * 重新开始闯关
	 */
	public function resetRound()
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_PASSED_STAGE] = 0;
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_CURR_POINT] = 0;
		$this->setFormation(array());
	}
	
	/**
	 * 获取最高的一次积分
	 * 
	 * @return int
	 */
	public function getMaxPoint()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_MAX_POINT];
	}
	
	/**
	 * 获得最高积分的时间
	 * 
	 * @return int
	 */
	public function getMaxPointTime()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME];
	}
	
	/**
	 * 设置最高一次积分
	 * 
	 * @param int $num
	 */
	private function setMaxPoint($num)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_MAX_POINT] = $num;
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_MAX_POINT_TIME] = Util::getTime();
	}
	
	/**
	 * 获得本轮的当前积分
	 * 
	 * @return int
	 */
	public function getCurrPoint()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_CURR_POINT];
	}
	
	/**
	 * 增加本轮的积分数
	 * 
	 * @param int $point
	 */
	private function addCurrPoint($point)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_CURR_POINT] += $point;
	}
	
	/**
	 * 获得玩家炼狱积分
	 * 
	 * @return int
	 */
	public function getHellPoint()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT];
	}
	
	/**
	 * 增加玩家的炼狱积分
	 * 
	 * @param int $point
	 * @param boolean
	 */
	public function addHellPoint($point)
	{
		$point = intval($point);
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT] += $point;
		
		if ($this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT] > WorldPassConf::HELL_POINT_MAX) 
		{
			Logger::fatal('hell point[%d] reach max[%d]', $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT], WorldPassConf::HELL_POINT_MAX);
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT] = WorldPassConf::HELL_POINT_MAX;
		}
		
		if ($this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT] < 0) 
		{
			Logger::warning('invalid hell point. sub[%d], now[%d]', $point, $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_HELL_POINT]);
			return FALSE;
		}
		
		return TRUE;
	}
	
	/**
	 * 减少玩家的炼狱积分
	 * 
	 * @param int $point
	 * @return boolean
	 */
	public function subHellPoint($point)
	{
		return $this->addHellPoint(-$point);
	}
	
	/**
	 * 攻击完一次关卡处理的数据
	 * 
	 * @param int $teamId			攻打关卡时候所在的分组id
	 * @param array $arrFormation	攻打关卡时候的阵型
	 * @param int $point			攻打关卡获得的积分
	 * @return int 					如果是通过的最后一关，而且积分创了新高，获得的炼狱积分数量就是本次总积分和以前总积分的差
	 */
	public function afterAttack($teamId, $arrFormation, $point)
	{
		$hellPoint = 0;
		
		$this->increPassedStage();
		$this->setFormation($arrFormation);
		$this->refreshHero('sys');
		
		$this->addCurrPoint($point);
		if ($this->getPassedStage() == WorldPassConf::STAGE_COUNT)
		{
			$this->addPointInfo($this->getCurrPoint());
			$this->decreAtkNum();
			if ($this->getCurrPoint() > $this->getMaxPoint())
			{
				$hellPoint = intval(($this->getCurrPoint() - $this->getMaxPoint()) / intval(btstore_get()->WORLD_PASS_RULE['hell_point_coef']));
				$this->addHellPoint($hellPoint);
				$this->setMaxPoint($this->getCurrPoint());
				WorldPassDao::updateMaxPoint($teamId, $this->getServerId(), $this->getPid(), $this->getMaxPoint());
			}
		}
		
		return $hellPoint;
	}
	
	/**
	 * 闯关总次数
	 * 
	 * @return int
	 */
	public function getAtkNum()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_ATK_NUM];
	}
	
	/**
	 * 减少闯关总次数
	 */
	public function decreAtkNum()
	{
		--$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_ATK_NUM];
	}
	
	/**
	 * 增加闯关总次数
	 */
	private function increAtkNum()
	{
		++$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_ATK_NUM];
	}
	
	/**
	 * 获得购买的闯关次数
	 * 
	 * @return int
	 */
	public function getBuyAtkNum()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_BUY_ATK_NUM];
	}
	
	/**
	 * 增加购买的闯关次数
	 */
	private function increBuyAtkNum()
	{
		++$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_BUY_ATK_NUM];
	}
	
	/**
	 * 购买闯关次数
	 */
	public function buyAtkNum()
	{
		$this->increBuyAtkNum();
		$this->increAtkNum();
	}
	
	/**
	 * 获得刷新候选武将的次数
	 * 
	 * @return int
	 */
	public function getRefreshNum()
	{
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_REFRESH_NUM];
	}
	
	/**
	 * 增加刷新武将的次数
	 */
	private function increRefreshNum()
	{
		++$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_REFRESH_NUM];
	}
	
	/**
	 * 刷新候选武将列表
	 * 
	 * @param string $refreshType 刷新类型：'sys' 'gold' 'item'
	 */
	public function refreshHero($refreshType = 'gold')
	{
		$arrFormation = $this->getFormation();
		$newChoice = WorldPassUtil::getNewChoice($arrFormation);
		$this->setChoice($newChoice);
		
		if ($refreshType == 'gold') 
		{
			$this->increRefreshNum();
		}
	}
	
	/**
	 * 获得候选武将列表
	 */
	public function getChoice()
	{
		if (empty($this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_CHOICE])) 
		{
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_CHOICE] = array();
		}
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_CHOICE];
	}
	
	/**
	 * 设置候选武将列表
	 * 
	 * @param array $arrChoice
	 */
	private function setChoice($arrChoice)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_CHOICE] = $arrChoice;
	}
	
	/**
	 * 获得阵型上的武将
	 */
	public function getFormation()
	{
		if (empty($this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_FORMATION]))
		{
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_FORMATION] = array();
		}
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_FORMATION];
	}
	
	/**
	 * 设置阵型上的武将
	 * 
	 * @param array $arrFormation
	 */
	private function setFormation($arrFormation)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_FORMATION] = $arrFormation;
	}
	
	/**
	 * 获得本周的积分信息
	 */
	public function getPointInfo()
	{
		if (empty($this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_POINT]))
		{
			$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_POINT] = array();
		}
		return $this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_POINT];
	}
	
	/**
	 * 增加一次积分信息
	 * 
	 * @param int $point
	 */
	private function addPointInfo($point)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_VA_EXTRA][WorldPassInnerUserField::TBL_VA_EXTRA_POINT][] = $point;
	}
	
	/**
	 * 判断攻打关卡时候传入的阵型和武将候选列表是否正确
	 * 
	 * @param array $arrFormation
	 * @return boolean
	 */
	public function isFormationValid($arrFormation)
	{
		// 去重
		$arrFormation = array_unique($arrFormation);
		
		// 阵上人数是否合法
		$currStage = $this->getPassedStage() + 1;
		if (count($arrFormation) != intval(btstore_get()->WORLD_PASS_COPY[$currStage]['hero_num'])) 
		{
			Logger::warning('formation is invalid, valid formation size[%d], curr formation size[%d]', intval(btstore_get()->WORLD_PASS_COPY[$currStage]['hero_num']), count($arrFormation));
			return FALSE;
		}
		
		// 检测武将列表是否有不在范围内的武将
		$curArrFormation = $this->getFormation();
		$curArrChoice = $this->getChoice();
		$allHtid = array_merge($curArrFormation, $curArrChoice);
		$arrUnknownHtid = array_diff($arrFormation, $allHtid);
		if (!empty($arrUnknownHtid)) 
		{
			Logger::warning('formation is invalid, has unknown htid[%s], param formation[%s], cur formation[%s], cur choice[%s]', $arrUnknownHtid, $arrFormation, $curArrFormation, $curArrChoice);
			return FALSE;
		}
		
		// 检查是否有同样的base htid
		$allBaseHtid = array();
		foreach ($arrFormation as $aHtid)
		{
			$aBaseHtid = HeroUtil::getBaseHtid($aHtid);
			if (in_array($aBaseHtid, $allBaseHtid)) 
			{
				Logger::warning('formation is invalid, has duplicate base htid, cur htid[%d], cur base htid[%d], now all base htid[%s]', $aHtid, $aBaseHtid, $allBaseHtid);
				return FALSE;
			}
			else 
			{
				$allBaseHtid[] = $aBaseHtid;
			}
		}
		
		return TRUE;
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
		
		if (!isset($arrUpdate[WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrUpdate[WorldPassInnerUserField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
		
		$arrCond = array
		(
				array(WorldPassInnerUserField::TBL_FIELD_SERVER_ID, '=', $this->getServerId()),
				array(WorldPassInnerUserField::TBL_FIELD_PID, '=', $this->getPid()),
		);
		WorldPassDao::updateInnerUser($arrCond, $arrUpdate);
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	********************************************/
	
	public function setPassedStageForConsole($num)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_PASSED_STAGE] = $num;
	}
	
	public function setCurrPointForConsole($num)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_CURR_POINT] = $num;
	}
	
	public function setAtkNumForConsole($num)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_ATK_NUM] = $num;
	}
	
	public function setBuyAtkNumForConsole($num)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_BUY_ATK_NUM] = $num;
	}
	
	public function setRefreshNumForConsole($num)
	{
		$this->mObjModify[WorldPassInnerUserField::TBL_FIELD_REFRESH_NUM] = $num;
	}
	
	public function setFormationForConsole($arrFormation)
	{
		$this->setFormation($arrFormation);
	}
	
	public function setHellPointForConsole($num)
	{
		$this->addHellPoint($num - $this->getHellPoint());
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */