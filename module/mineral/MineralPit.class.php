<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MineralPit.class.php 111565 2014-05-27 14:04:24Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-13-106/module/mineral/MineralPit.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-05-27 22:04:24 +0800 (二, 2014-05-27) $
 * @version $Revision: 111565 $
 * @brief 矿坑类
 *  
 **/
class MineralPit
{
    /**
     * {'guards'=>{array($guid=>gtime),...}}
     * @var array
     */
    private $pitInfo = array(); //资源矿数据
	private $buffer = array();  //资源矿数据 缓冲数据 用来和$pitInfo对比, 判断是否改变
	private static $domainId;  //资源区id
	private static $pitId;  //矿坑id
	private static $_instance = NULL;   //本类单例
	
	private function __construct()
	{
		if(empty(self::$domainId) || empty(self::$pitId))
		{
			throw new InterException('domainId or pitId is not set.');
		}
		$pitInfo		=	MineralDAO::getPitById(self::$domainId,self::$pitId);
		if(empty($pitInfo))
		{
			throw new FakeException('no such pit with domainid:%s,pitid:%s.',self::$domainId,self::$pitId);
		}
		$this->pitInfo	=	$pitInfo;
		$this->buffer = $pitInfo;
	}

    /**
     * 赋值$domainId 和 $pitId
     *
     * @param $domainId 资源区id
     * @param $pitId    矿坑id
     */
    public static function setPit($domainId,$pitId)
	{
	    if(self::$domainId!=$domainId || (self::$pitId!=$pitId))
	    {
	        self::release();
	    }
	    self::$domainId=$domainId;
	    self::$pitId=$pitId;
	}
	
	/**
	 * 获取本类唯一实例
	 *
	 * @return MineralPit
	 */
	public static function getInstance()
	{
		if (!self::$_instance instanceof self)
		{
			self::$_instance = new self();  //自身类实例化，这种用法不常见
		}
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
	
	/**
	 * 判断是否在保护时间内
	 */
	public function isInProtectTime()
	{
	    if($this->isOccupied() == FALSE)
	    {
	        return FALSE;
	    }
		//矿坑的保护时间
		$protectTime = btstore_get()->MINERAL[self::$domainId]['pits'][self::$pitId][PitArr::PROTECTTIME];
		//占领矿坑的时间
		$captureTime = $this->pitInfo[TblMineralField::OCCUPYTIME];
		if($captureTime+$protectTime > Util::getTime())
		{
			return TRUE;
		}
		return FALSE;
	}
	/**
     * 获取占领者
	 * @return int 用户id
	 */
	public function getCapturer()
	{
		return $this->pitInfo[TblMineralField::UID];
	}

    /**
     * 判断该资源矿 是否被占领
     * @return bool
     */
    public function isOccupied()
	{
		if($this->pitInfo[TblMineralField::UID] > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

    /**
     * 获取资源矿守卫军
     *
     * @return array
     */
    public function getGuards()
    {
        return MineralDAO::getGuards(self::$domainId, self::$pitId);
    }

    /**
     * 根据uid查询某个玩家的协助军信息
     * @param $uid
     * @return array
     */
    public function getGuardInfoByUid($uid)
    {
        return MineralDAO::getGuardInfoByUid($uid);
    }

    /**
     * @return mixed
     */
    public function getGuardCount()
    {
        return MineralDAO::getGuardCount(self::$domainId, self::$pitId);
    }

    /**
     * 增加守卫军
     * @param $fields
     */
    public function addGuards($fields)
    {
        MineralDAO::insertGuards($fields);
    }

    /**
     * 更新资源矿守卫军
     */
    public function updateGuards($arrConf, $arrField)
    {
        MineralDAO::updateGuards($arrConf, $arrField);
    }

    /**
     * 获取资源矿守卫军守卫总时间
     *
     * @return string
     */
    public function getTotalGuardsTime()
    {
        return $this->pitInfo[TblMineralField::TOTALGUARDSTIME];
    }

    /**
     * 重置资源矿守卫军守卫总时间
     *
     * @param int $totalGuardTime 资源矿守卫军守卫总时间
     */
    public function resetTotalGuardsTime($totalGuardTime)
    {
        $this->pitInfo[TblMineralField::TOTALGUARDSTIME] = $totalGuardTime;
    }

    /**
     * 保存资源矿数据->DB
     * @throws FakeException
     */
    public function save()
	{
		if(!empty($this->pitInfo) && ($this->buffer != $this->pitInfo))
		{
			Logger::trace('save pit %s into DB,pitinfo :%s.',self::$pitId,$this->pitInfo);
			MineralDAO::savePitInfo($this->pitInfo);
			$this->buffer = $this->pitInfo;
		}
		else if(empty($this->pitInfo))
		{
			throw new FakeException('the pitInfo is empty.');	
		}
	}

    /**
     * 到期时间
     * @return null
     */
    public function getDueTimer()
	{
		if(empty($this->pitInfo[TblMineralField::DUETIMER]))
		{
			return NULL;
		}
		return $this->pitInfo[TblMineralField::DUETIMER];
	}
	
	public function getOccupyTime()
	{
	    return $this->pitInfo[TblMineralField::OCCUPYTIME];
	}
	/**
	 * 获取矿坑占有者到当前的收益
	 * @return array
	 * {
	 * 	'time'=>占领时间
	 * 	'silver'=>收益
	 * }
	 * 占领者资源矿游戏币收益=int【 资源矿游戏币基础值*（占领时间+协助军协助时间总和*单个协助军收入增益/100）*资源矿系数*(玩家等级+资源矿玩家等级修正)】
                            资源矿系数 =5*10^-5
                            玩家等级=max（30，玩家实际等级）
                            int【】内向上取整
                            玩家等级以资源矿收获的时候的等级为准
	 */
	public function getAcquireToNow()
	{
		//占领时间
		$time =	Util::getTime()	-	$this->pitInfo[TblMineralField::OCCUPYTIME];
		$uid = $this->pitInfo[TblMineralField::UID];  //占矿者uid
		if(empty($uid))
		{
		    return array('time'=>0,'silver'=>0);
		}
        $oneHelpArmyEnhance = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ONEHELPARMY_ENHANCE];    //单个协助军收入增益
        $resPlayerLv = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESPLAYER_LV];  //玩家等级修正
		$level    =    EnUser::getUserObj($uid)->getLevel();
		$level    =    max(array(30, $level)) + $resPlayerLv;
		$silverBase    =  $this->getSilverBaseOfPit();  //资源矿游戏币基础值
        $totalGuardTime = $this->getTotalGuardsTime();  //协助军协助时间总和
	    $arrGuardInfo = MineralDAO::getGuardInfoById(self::$domainId, array(self::$pitId));
        foreach($arrGuardInfo as $index => $guardInfo)
        {
            $totalGuardTime += (Util::getTime() - $guardInfo[TblMineralGuards::GUARDTIME]);
        }
		$silver    =    ceil($silverBase * ($time + 
		        $totalGuardTime * $oneHelpArmyEnhance / 100) * 
		        MineralDef::MINERAL_OUTPUT_RATIO * $level * 
		        (1+EnCityWar::getCityEffect($uid, CityWarDef::MINERAL)/10000));
		$silverGot    =    intval($silver);
		if($silverGot < MineralDef::MINERAL_ACQUIRE_MIN_SILVER)
		{
		    $silverGot    =    MineralDef::MINERAL_ACQUIRE_MIN_SILVER;
		}
		
		$addition = MineralLogic::getMineralSilverAddition();
		Logger::trace('silverBase %s.occupyTime %s. totalGuardTime %d.oneHelpArmyEnhance %d.silverGot %s.addition %s after addition is %d',
		        $silverBase,$time,$totalGuardTime,$oneHelpArmyEnhance,$silverGot,$addition,intval($silverGot * $addition));
		$silverGot = intval($silverGot * $addition);
		return array(
		        'time'=>$time,
		        'silver'=>$silverGot
		        );
	}

	public function getBaseOccupyTime()
	{
	    $baseTime = intval(btstore_get()->MINERAL[self::$domainId]['pits'][self::$pitId][PitArr::HARVESTTIME]);
	    return $baseTime;
	}


    /**
     * 计算 资源矿游戏币基础值
     * @return mixed
     */
    public function getSilverBaseOfPit()
	{
	    return MineralLogic::getSilverBaseOfPit(self::$domainId, self::$pitId);
	}

	public function getKeyofPit()
	{
		return array(self::$domainId,self::$pitId);
	}
	/**
	 * 被抢夺之后重置矿坑的信息
	 */
	public function resetOnGrabed($uid,$timerId)
	{
		$this->pitInfo[TblMineralField::DUETIMER]	=	$timerId;
		$this->pitInfo[TblMineralField::OCCUPYTIME]	=	Util::getTime();
		$this->pitInfo[TblMineralField::UID]		=	$uid;
		$this->pitInfo[TblMineralField::DELAYTIMES] = 0;
		$this->pitInfo[TblMineralField::TOTALGUARDSTIME] = 0;
	}

    /**
     * 放弃之后重置矿坑的信息
     */
    public function resetOnGiveUp()
	{
		$this->pitInfo[TblMineralField::DUETIMER]	=	0;
		$this->pitInfo[TblMineralField::OCCUPYTIME] =	0;
		$this->pitInfo[TblMineralField::UID]		=	0;
		$this->pitInfo[TblMineralField::DELAYTIMES] = 0;
		$this->pitInfo[TblMineralField::TOTALGUARDSTIME] = 0;
	}

    /**
     * 延期之后重置矿坑信息
     */
    public function resetOnDelayPit($uid,$timerId,$delayTimes)
    {
        $this->pitInfo[TblMineralField::DUETIMER] = $timerId;
        $this->pitInfo[TblMineralField::UID] = $uid;
        $this->pitInfo[TblMineralField::DELAYTIMES] = $delayTimes;
    }

	public function getPitInfo()
	{
		return $this->pitInfo;
	}

    public function getDelayTimes()
    {
        return $this->pitInfo[TblMineralField::DELAYTIMES];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */