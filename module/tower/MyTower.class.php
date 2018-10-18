<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyTower.class.php 265724 2016-10-09 10:26:45Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/MyTower.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-10-09 10:26:45 +0000 (Sun, 09 Oct 2016) $
 * @version $Revision: 265724 $
 * @brief 
 *  
 **/
class MyTower 
{
    private $buffer    =    array();
	private $tower = array();
	private static $uid;
	private static $_instance = NULL;
	
	
	
	public function __construct($uid)
	{
	    self::$uid = $uid;
	    $tower = array();
	    if(self::$uid == RPCContext::getInstance()->getUid())
	    {
	        $tower = RPCContext::getInstance()->getSession(TowerConf::$SESSION_TOWER_INFO);
	    }
		if(empty($tower))
		{
			if(empty($uid))
			{
				throw new InterException('no uid in session.can not fetch tower info.');
			}
			$tower = TowerDAO::getTowerInfo($uid,TOWERTBL_FIELD::$TBL_TOWER_ALL_FIELD);
		}
		$this->tower = $tower;
		$this->buffer= $tower;
	    if(empty($tower))
		{
		    $this->tower = $this->getInitTowerInfo();
		}
		$this->checkOldHellData();
		$this->refreshDefeatNum();
		$this->checkOpenNewLevel();
		$this->checkOpenNewHellLevel();
	}
	
	
	private function checkOpenNewLevel()
	{
	    $maxLv = $this->getMaxLevel();
	    if(empty($maxLv))
	    {
	       return; 
	    }
	    $nextLv = btstore_get()->TOWERLEVEL[$maxLv]['pass_open_lv'];
	    if(empty($nextLv))
	    {
	        return;
	    }
	    if($this->getCurLevel() == $maxLv  
	            && ($this->getTowerStatus() == TowerDef::CUR_LEVEL_STATUS_PASS))
	    {
	        $this->setCurLv($nextLv, TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    }
	}
	
	private function getInitTowerInfo()
	{
	    $tower = array(
	            TOWERTBL_FIELD::UID => self::$uid,
	            TOWERTBL_FIELD::MAX_LEVEL =>0,
	            TOWERTBL_FIELD::MAX_LEVEL_TIME=>Util::getTime(),
	            TOWERTBL_FIELD::CURRENT_LEVEL => TowerDef::FIRST_TOWER_LEVEL_ID,
	            TOWERTBL_FIELD::CAN_FAIL_NUM => TowerLogic::getDailyFailNum(),
	            TOWERTBL_FIELD::RESET_NUM => TowerLogic::getDailyResetNum(),
	            TOWERTBL_FIELD::LAST_REFRESH_TIME => Util::getTime(),
	            TOWERTBL_FIELD::GOLD_BUY_NUM => 0,
	            TOWERTBL_FIELD::BUY_ATK_NUM => 0,
	            TOWERTBL_FIELD::BUY_SPECIAL_NUM => 0,
	            TOWERTBL_FIELD::MAX_HELL => 0,
	            TOWERTBL_FIELD::CUR_HELL => HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID,
	            TOWERTBL_FIELD::RESET_HELL => TowerLogic::getDailyHellResetNum(),
	            TOWERTBL_FIELD::CAN_FAIL_HELL => TowerLogic::getDailyHellFailNum(),
	            TOWERTBL_FIELD::GOLD_BUY_HELL => 0,
	            TOWERTBL_FIELD::BUY_HELL_NUM => 0,
	            TOWERTBL_FIELD::VA_TOWER_INFO => array(
	                    TOWERTBL_FIELD::VA_TOWER_SWEEPINFO => array(),
	                    TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER => array(
	                            TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST => array(),
	                            ),
	                    ), 
	            );
	    return $tower;
	}
	/**
	 * 获取本类唯一实例
	 *
	 * @return MyTower
	 */
	public static function getInstance($uid = 0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
		if (self::$_instance instanceof self && (self::$uid == $uid))
		{
		    return self::$_instance;
		}
		self::$_instance = new self($uid);
		return self::$_instance;
	}
	
	/**
	 * 毁掉单例，单元测试对应
	 */
	public static function release()
	{
		if (self::$_instance != null)
		{
			self::$_instance = null;
		}
	}
		
	public function getTowerInfo()
	{
		if(empty($this->tower))
		{
			return array();
		}
		return $this->tower;
	}
	
	public function getSweepInfo()
	{
	    $sweepInfo = $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SWEEPINFO];
	    return $sweepInfo;
	}
	
	public function endSweep($endLv,$levelNum)
	{
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SWEEPINFO] = array();
	    if($levelNum == 0)
	    {
	        return;
	    }
	    $this->passLevel($endLv);
	}
	
	public function setCurLv($curLv,$curLvStatus)
	{
	    if($curLv > ($this->getMaxLevel() + 1))
	    {
	        Logger::fatal('curlv %d is maxer than maxlevel %d',$curLv,$this->getMaxLevel());
	        return FALSE;
	    }
	    $this->tower[TOWERTBL_FIELD::CURRENT_LEVEL] = $curLv;
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_CURSTATUS] = $curLvStatus;
	    return TRUE;
	}
	
	public function getMaxLevel()
	{
	    return $this->tower[TOWERTBL_FIELD::MAX_LEVEL];
	}
	
	public function getMaxLevelTime()
	{
	    return $this->tower[TOWERTBL_FIELD::MAX_LEVEL_TIME];
	}
	
	public function startSweep($startLv,$endLv)
	{
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SWEEPINFO] = array(
	            TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL => $endLv,
	            TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL=>$startLv,
	            TOWERTBL_FIELD::SWEEP_INFO_START_TIME=>Util::getTime(),
	            );
	}
	
	public function resetTower()
	{
	    if($this->tower[TOWERTBL_FIELD::RESET_NUM] < 1)
	    {
	        return FALSE;
	    }
	    $this->setCurLv(TowerDef::FIRST_TOWER_LEVEL_ID,TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    $this->tower[TOWERTBL_FIELD::CURRENT_LEVEL] = TowerDef::FIRST_TOWER_LEVEL_ID;
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] = TowerLogic::getDailyFailNum();
	    $this->tower[TOWERTBL_FIELD::RESET_NUM] -= 1;
	    // 通知重置塔层的打点函数
	    TowerLogic::informResetTowerTask($this->tower[TOWERTBL_FIELD::RESET_NUM]);
	    return TRUE;
	}
	
	public function getResetNum()
	{
	    return $this->tower[TOWERTBL_FIELD::RESET_NUM];
	}
	
	public function resetTowerByGold()
	{
	    $this->setCurLv(TowerDef::FIRST_TOWER_LEVEL_ID,TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    $this->tower[TOWERTBL_FIELD::CURRENT_LEVEL] = TowerDef::FIRST_TOWER_LEVEL_ID;
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] = TowerLogic::getDailyFailNum();
	    return TRUE;
	}
	
	public function passLevel($level)
	{
		if($level > $this->tower[TOWERTBL_FIELD::MAX_LEVEL])
		{
		    $this->tower[TOWERTBL_FIELD::MAX_LEVEL] = $level;
		    $this->tower[TOWERTBL_FIELD::MAX_LEVEL_TIME] = Util::getTime();
		    EnAchieve::updateTower(self::$uid, $level);
		}
		$nextLevel = intval(btstore_get()->TOWERLEVEL[$level]['pass_open_lv']); 
        if(!empty($nextLevel))
        {
            $this->setCurLv($nextLevel,TowerDef::CUR_LEVEL_STATUS_ATTAK);
        }
        else
        {
            $this->setCurLv($level,TowerDef::CUR_LEVEL_STATUS_PASS);
        }
		Logger::trace('passLevel %d tower is %s',$level,$this->tower);
		return $this->tower;
	}
	
	public function getUid()
	{
	    return $this->tower[TOWERTBL_FIELD::UID];
	}
	
	public function getLevelStatus($level)
	{
		if($level <= $this->getMaxLevel())
		{
		    return TowerLevelStatus::PASS;
		}
		else if($level == $this->getCurLevel())
		{
		    return TowerLevelStatus::ATTACK;
		}
		return TowerLevelStatus::NOTOPEN;
	}
	
	private function refreshDefeatNum()
	{
		if(util::isSameDay($this->tower[TOWERTBL_FIELD::LAST_REFRESH_TIME]) == false)
		{
			$this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] = TowerLogic::getDailyFailNum();
			$this->tower[TOWERTBL_FIELD::RESET_NUM] = TowerLogic::getDailyResetNum();
			$this->tower[TOWERTBL_FIELD::LAST_REFRESH_TIME] = Util::getTime();
			$this->tower[TOWERTBL_FIELD::GOLD_BUY_NUM] = 0;
			$this->tower[TOWERTBL_FIELD::BUY_ATK_NUM] = 0;
			$this->tower[TOWERTBL_FIELD::BUY_SPECIAL_NUM] = 0;
			$this->tower[TOWERTBL_FIELD::RESET_HELL] = TowerLogic::getDailyHellResetNum();
			$this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] = TowerLogic::getDailyHellFailNum();
			$this->tower[TOWERTBL_FIELD::GOLD_BUY_HELL] = 0;
			$this->tower[TOWERTBL_FIELD::BUY_HELL_NUM] = 0;
		}
		if(!isset($this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]))
		{
		    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]  = array(
	                            TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST => array(),
		            );
		}
		$this->rfrSpecailTower();
	}
	
	private function rfrSpecailTower()
	{
	    $towerList =  $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST];
	    $newTowerList = array();
	    foreach($towerList as $towerLvId => $towerLvInfo)
	    {
	        $towerTmpId = $towerLvInfo[TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERID];
	        $duration = TowerLogic::getSpecailTowerDuration();
	        if($towerLvInfo[TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERSTARTTIME] + $duration
	                < Util::getTime())
	        {
	            //过期了
	            continue;
	        }
	        if($towerLvInfo[TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERDEFEATNUM] >= 
	                TowerLogic::getSpecailTowerAtkNum())
	        {
	            //没有攻击次数了
	            continue;
	        }
	        $newTowerList[$towerLvId] = $towerLvInfo;
	    }
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	                [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST] = $newTowerList;
	}
	
	public function addSpecailTowerLv($towerTmpId)
	{
	    $actualLvId = 0;
	    $towerList = $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST];
        foreach($towerList as $towerLvId => $towerLvInfo)
        {
            if($towerLvId > $actualLvId)
            {
                $actualLvId = $towerLvId;
            }
        }
	    $actualLvId += 1;
	    $towerList[$actualLvId] = array(
	            TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERID => $towerTmpId,
	            TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERSTARTTIME => Util::getTime(),
	            TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERDEFEATNUM => 0,
	            );
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	                [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST] = $towerList;
	}
	
	public function getSpecailTowerList()
	{
	    return $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	    [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST];
	}
	
	public function addSpecailTowerAtkNum($towerLvId)
	{
	    $towerList = $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	                [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST];
	    if(!isset($towerList[$towerLvId]))
	    {
	        throw new FakeException('no such towerlvid %d in specail tower list %s.',$towerLvId,$towerList);
	    }
	    $towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERDEFEATNUM] ++;
	    if($towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERDEFEATNUM] >= 
	            TowerLogic::getSpecailTowerAtkNum())
	    {
	        unset($towerList[$towerLvId]);
	    }
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	        [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST] = $towerList;
	}
	
	public function passSpecialTower($towerLvId)
	{
	    $towerList = $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	    [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST];
	    if(!isset($towerList[$towerLvId]))
	    {
	        throw new FakeException('no such towerlvid %d in specail tower list %s.',$towerLvId,$towerList);
	    }
	    $towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERDEFEATNUM] ++;
	    unset($towerList[$towerLvId]);
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	        [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST] = $towerList;
	}
	
	public function getSpecailTowerAtkNum($towerLvId)
	{
	    $towerList = $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	        [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST];
	    if(!isset($towerList[$towerLvId]))
	    {
	        throw new FakeException('no such towerlvid %d in specail tower list %s.',$towerLvId,$towerList);
	    }
	    return $towerList[$towerLvId][TOWERTBL_FIELD::VA_TOWER_SPECAIL_TOWERDEFEATNUM];
	}
	
	public function subCanFailNum()
	{
	    if($this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] <= 0)
	    {
	        return FALSE;
	    }
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] -= 1;
	    return TRUE;
	}
	
	public function hasFailNum()
	{
		if($this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] > 0)
		{
			return TRUE;
		}
		return FALSE;
	}
	
	public function getCanFailNum()
	{
	    return $this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM];
	}
	
	public function addFailNum($num)
	{
	    if($num <= 0)
	    {
	        Logger::warning('addFailnum %d.',$num);
	        return;
	    }
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_NUM] += $num;
	}
	
	public function addGoldBuyNum($num)
	{
	    if($num <= 0)
	    {
	        Logger::warning('addGoldBuyNum %d.',$num);
	        return;
	    }
	    $this->tower[TOWERTBL_FIELD::GOLD_BUY_NUM] += $num;
	}
	
	public function getGoldBuyNum()
	{
	    return $this->tower[TOWERTBL_FIELD::GOLD_BUY_NUM];
	}
	
	public function getBuyAtkNum()
	{
	    return $this->tower[TOWERTBL_FIELD::BUY_ATK_NUM];
	}
	
	public function addBuyAtkNum($num)
	{
	    return $this->tower[TOWERTBL_FIELD::BUY_ATK_NUM] += $num;
	}
	
	public function getBuySpecialNum()
	{
		return $this->tower[TOWERTBL_FIELD::BUY_SPECIAL_NUM];
	}
	
	public function addBuySpecialNum($num)
	{
		return $this->tower[TOWERTBL_FIELD::BUY_SPECIAL_NUM] += $num;
	}
	
	public function getCurLevel()
	{
	    return $this->tower[TOWERTBL_FIELD::CURRENT_LEVEL];
	}
	
	public function getTowerStatus()
	{
	    if(!isset($this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_CURSTATUS]))
	    {
	        $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_CURSTATUS] = TowerDef::CUR_LEVEL_STATUS_ATTAK;
	    }
	    return $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_CURSTATUS];
	}
	
	public function save()
	{
		if(!empty($this->tower) && ($this->buffer != $this->tower))
		{
			TowerDAO::save(self::$uid, $this->tower);
			$this->buffer    =    $this->tower;
			if(self::$uid == RPCContext::getInstance()->getUid())
			{
			    RPCContext::getInstance()->setSession(TowerConf::$SESSION_TOWER_INFO, $this->tower);
			}
		}		
	}
	
	public function getHellSweepInfo()
	{
	    if ( !isset( $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_SWEEPINFO] ) )
	    {
	        $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_SWEEPINFO] = array();
	    }
	    
	    return $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_SWEEPINFO];
	}
	
	public function endHellSweep($endLv,$levelNum)
	{
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_SWEEPINFO] = array();
	    if($levelNum == 0)
	    {
	        return;
	    }
	    $this->passHellLevel($endLv);
	}
	
	public function hasHellFailNum()
	{
	    if($this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] > 0)
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	public function subHellCanFailNum()
	{
	    if($this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] <= 0)
	    {
	        return FALSE;
	    }
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] -= 1;
	    return TRUE;
	}
	
	public function getCurHellLevel()
	{
	    if ( empty( $this->tower[TOWERTBL_FIELD::CUR_HELL] ) )
	    {
	        $this->tower[TOWERTBL_FIELD::CUR_HELL] = HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID;
	    }
	    
	    return $this->tower[TOWERTBL_FIELD::CUR_HELL];
	}
	
	public function getHellTowerStatus()
	{
	    if(!isset($this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_STATUS]))
	    {
	        $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_STATUS] = TowerDef::CUR_LEVEL_STATUS_ATTAK;
	    }
	    return $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_STATUS];
	}
	
	public function getHellMaxLevel()
	{
	    return $this->tower[TOWERTBL_FIELD::MAX_HELL];
	}
	
	public function getHellLevelStatus($level)
	{
	    if($level <= $this->getHellMaxLevel())
	    {
	        return TowerLevelStatus::PASS;
	    }
	    else if($level == $this->getCurHellLevel())
	    {
	        return TowerLevelStatus::ATTACK;
	    }
	    return TowerLevelStatus::NOTOPEN;
	}
	
	public function resetHellTower()
	{
	    if($this->tower[TOWERTBL_FIELD::RESET_HELL] < 1)
	    {
	        return FALSE;
	    }
	    $this->setCurHellLv(HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID,TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    $this->tower[TOWERTBL_FIELD::CUR_HELL] = HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID;
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] = TowerLogic::getDailyHellFailNum();
	    $this->tower[TOWERTBL_FIELD::RESET_HELL] -= 1;
	    return TRUE;
	}
	
	public function setCurHellLv($curLv,$curLvStatus)
	{
	    if($curLv > ($this->getHellMaxLevel() + 1))
	    {
	        Logger::fatal('curlv %d is maxer than maxlevel %d',$curLv,$this->getHellMaxLevel());
	        return FALSE;
	    }
	    $this->tower[TOWERTBL_FIELD::CUR_HELL] = $curLv;
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_STATUS] = $curLvStatus;
	    return TRUE;
	}
	
	public function getHellCanFailNum()
	{
	    return $this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL];
	}
	
	public function startHellSweep($startLv,$endLv)
	{
	    $this->tower[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_SWEEPINFO] = array(
	        TOWERTBL_FIELD::SWEEP_INFO_END_LEVEL => $endLv,
	        TOWERTBL_FIELD::SWEEP_INFO_START_LEVEL => $startLv,
	        TOWERTBL_FIELD::SWEEP_INFO_START_TIME => Util::getTime(),
	    );
	}
	
	public function getHellBuyAtkNum()
	{
	    return $this->tower[TOWERTBL_FIELD::BUY_HELL_NUM];
	}
	
	public function addHellBuyAtkNum($num)
	{
	    return $this->tower[TOWERTBL_FIELD::BUY_HELL_NUM] += $num;
	}
	
	public function resetHellTowerByGold()
	{
	    $this->setCurHellLv(HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID,TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    $this->tower[TOWERTBL_FIELD::CUR_HELL] = HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID;
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] = TowerLogic::getDailyHellFailNum();
	    return TRUE;
	}
	
	public function getHellGoldBuyNum()
	{
	    return $this->tower[TOWERTBL_FIELD::GOLD_BUY_HELL];
	}
	
	public function passHellLevel($level)
	{
	    if($level > $this->tower[TOWERTBL_FIELD::MAX_HELL])
	    {
	        $this->tower[TOWERTBL_FIELD::MAX_HELL] = $level;
	    }
	    $nextLevel = intval(btstore_get()->HELL_TOWER_LEVEL[$level][HellTowerLevelDef::PASS_OPEN]);
	    if(!empty($nextLevel))
	    {
	        $this->setCurHellLv($nextLevel,TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    }
	    else
	    {
	        $this->setCurHellLv($level,TowerDef::CUR_LEVEL_STATUS_PASS);
	    }
	    Logger::trace('passLevel %d tower is %s',$level,$this->tower);
	    return $this->tower;
	}
	
	public function checkOldHellData()
	{
	    if ( empty( $this->tower[TOWERTBL_FIELD::CUR_HELL]  ) )
	    {
	        $this->tower[TOWERTBL_FIELD::CUR_HELL] = HellTowerDef::FIRST_HELL_TOWER_LEVEL_ID;
	    }
	}
	
	private function checkOpenNewHellLevel()
	{
	    $maxLv = $this->getHellMaxLevel();
	    if(empty($maxLv))
	    {
	        return;
	    }
	    $nextLv = btstore_get()->HELL_TOWER_LEVEL[$maxLv][HellTowerLevelDef::PASS_OPEN];
	    if(empty($nextLv))
	    {
	        return;
	    }
	    if($this->getCurHellLevel() == $maxLv
	        && ($this->getHellTowerStatus() == TowerDef::CUR_LEVEL_STATUS_PASS))
	    {
	        $this->setCurHellLv($nextLv, TowerDef::CUR_LEVEL_STATUS_ATTAK);
	    }
	}
	
	public function addGoldBuyHell($num)
	{
	    if($num <= 0)
	    {
	        Logger::warning('addGoldBuyNum %d.',$num);
	        return;
	    }
	    $this->tower[TOWERTBL_FIELD::GOLD_BUY_HELL] += $num;
	}
	
	public function addHellFailNum($num)
	{
	    if($num <= 0)
	    {
	        Logger::warning('addFailnum %d.',$num);
	        return;
	    }
	    $this->tower[TOWERTBL_FIELD::CAN_FAIL_HELL] += $num;
	}
	
	public function getHellResetNum()
	{
	    return $this->tower[TOWERTBL_FIELD::RESET_HELL];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */