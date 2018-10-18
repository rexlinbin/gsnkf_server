<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FestivalManager.class.php 153269 2015-01-19 02:09:56Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/activity/festival/FestivalManager.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-01-19 10:09:56 +0800 (星期一, 19 一月 2015) $
 * @version $Revision: 153269 $
 * @brief 
 *  
 **/
class FestivalActManager
{
	private $uid = NULL;
	private $data = NULL;
	private $dataModify = NULL;

	private static $_instance = NULL;

	private function __construct($uid = 0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();

			if (empty($uid))
			{
				throw new FakeException('uid in session is null.');
			}
		}

		$this->uid = $uid;
		$info = FestivalActDao::select($this->uid, FestivalActDef::$ALL_TABLE_FIELD);

		if (empty($info))
		{
			Logger::trace('User %d enter festivalact first time , need init.', $this->uid);
			
			$info = $this->initData();

			FestivalActDao::insert($info);
		}

		$this->data = $info;
		$this->dataModify = $info;
		$this->rfrFestivalAct();
	}

	public function getUid()
	{
		return $this->uid;
	}

	public function initData()
	{
	    return array(
	        FestivalActDef::UID => $this->uid,
	        FestivalActDef::UPDATE_TIME => Util::getTime(),
	        FestivalActDef::VA_DATA => array(),
	    );
	}

	// 得到VA信息
	public function getVa()
	{
	    return $this->dataModify[FestivalActDef::VA_DATA];
	}

	/**
	 * 得到当季某大类某任务的上一次更新时间
	 */
	public function getPeriodBigTypeMisIDUpdateTime($period, $bigType, $misID)
	{
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID]))
	    {
	        return 0;
	    }
	    else
	    {
	        if ($bigType == FestivalActDef::ACT_TYPE_TASK)
	        {
	            return $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][3];
	        }
	        else
	        {
	            return $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1];
	        }
	    }
	}

	/**
	 * 得到兑换类某ID的数据
	 */
	public function getExchangeMisIDNum($misID)
	{
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID]))
	    {
	        return 0;
	    }
	    else
	    {
	        return $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID][0];
	    }
	}


	/**
	 * 得到当季的某大类的数据信息
	 */
	public function getPeriodBigTypeMisIDNum($period, $bigType, $misID)
	{
	     if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID]))
	     {
	         return 0;
	     }
	     else
	     {
	         if ($bigType == FestivalActDef::ACT_TYPE_TASK)
	         {
	             $num = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][0]
	             + $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1];
	             return $num;
	         }
	         else
	         {
	             return $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][0];
	         }
	     }
	}

	/**
	 * 在改数据前要确认存在，没有的话初始化一下
	 */
	public function checkData($period, $bigType, $misID)
	{
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID]))
	    {
	        // 任务的数据是[今天之前的数量，今天的数量，0未完成|1完成|2领过奖, 上一次操作时间]
	        if ($bigType == FestivalActDef::ACT_TYPE_TASK)
	        {
	            $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID] = array(0, 0, 0, 0);
	        }
	        // 任务的数据是[记录的数量, 上一次操作时间]
	        else
	        {
	            $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID] = array(0, 0);
	        }
	    }
	}

	/**
	 * 得到兑换类ID的完成情况
	 */
	public function getExchangeMisIDStatus($misID)
	{
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID]))
	    {
	        return 0;
	    }
	    else
	    {
	        $num = $this->getExchangeMisIDNum($misID);
	        $confNum = FestivalActConf::getInstance()->getExchangeMisKey($misID, FestivalActDef::NUM);
	        $status = 0;
	        if ($num >= $confNum)
	        {
	           $status = 2;
	        }
	        return $status;
	    }
	}

	/**
	 * 得到当季的某大类的状态信息
	 */
	public function getPeriodBigTypeMisIDStatus($period, $bigType, $misID)
	{
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID]))
	    {
	        return 0;
	    }
	    else
	    {
	        if ($bigType == FestivalActDef::ACT_TYPE_TASK)
	        {
	            return $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][2];
	        }
	        if ($bigType == FestivalActDef::ACT_TYPE_DISCOUNT
	            || $bigType == FestivalActDef::ACT_TYPE_EXCHARGE)
	        {
	            $num = $this->getPeriodBigTypeMisIDNum($period, $bigType, $misID);
	            $confNum = FestivalActConf::getInstance()->getPeriodMisKey($period, $misID, FestivalActDef::NUM);
	            $status = 0;
	            if ($num >= $confNum)
	            {
	                $status = 2;
	            }
	            return $status;
	        }
	    }
	}


	// 任务直接领奖
	public function taskReward($big_type, $period, $misID)
	{
	    // 在改数据前要确认存在，没有的话初始化一下
	    $this->checkData($period, $big_type, $misID);
	    if ($big_type == FestivalActDef::ACT_TYPE_TASK)
	    {
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$big_type][$misID][2] = 2;
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$big_type][$misID][3] = Util::getTime();
	    }
	    else if ($big_type == FestivalActDef::ACT_TYPE_CHARGE)
	    {
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$big_type][$misID][0]++;
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$big_type][$misID][1] = Util::getTime();
	    }
	    else
	    {
	        throw new FakeException('taskReward big_type:%s is err.', $big_type);
	    }
	}

	// 兑换记录过程
	public function exchange($misID, $num)
	{
	    // 在改数据前要确认存在，没有的话初始化一下
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID]))
	    {
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID] = array(0, 0);
	    }
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID][0] += $num;
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_EXCHANGE][$misID][1] = Util::getTime();
	}

	// 记录买的过程
	public function buy($periodID, $misID, $num)
	{
	    // 在改数据前要确认存在，没有的话初始化一下
	    $bigType = FestivalActDef::ACT_TYPE_DISCOUNT;
	    $this->checkData($periodID, $bigType, $misID);
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$periodID][$bigType][$misID][0] += $num;
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$periodID][$bigType][$misID][1] = Util::getTime();
	}

	// 补签记录
	public function signReward($period, $misID)
	{
	    // 在改数据前要确认存在，没有的话初始化一下
	    $bigType = FestivalActDef::ACT_TYPE_TASK;
	    $this->checkData($period, $bigType, $misID);
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1] = 1;
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][2] = 2;
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][3] = Util::getTime();
	}

	// 检查任务是否处于可领奖状态
	public function canRewardByTask($periodID, $big_type, $misID, $buqian)
	{
	    Logger::debug('canRewardByTask start, periodID:%s, big_type:%s, misID:%s, buqian:%s', $periodID, $big_type, $misID, $buqian);
	    // 充值的话走充值检查
	    if ($big_type == FestivalActDef::ACT_TYPE_CHARGE)
	    {
	        return $this->canRewardByCharge($periodID, $misID);
	    }
	    // 登陆任务的话走登陆检查
	    $type_id = FestivalActConf::getInstance()->getPeriodMisKey($periodID, $misID, FestivalActDef::TYPE_ID);
	    if ($type_id == FestivalActDef::TASK_LOGIN)
	    {
	        return $this->canRewardByLoginTask($periodID, $misID, $buqian);
	    }
        // 剩下的就是普通的任务啦
        $status = $this->getPeriodBigTypeMisIDStatus($periodID, $big_type, $misID);
	    if ($status == 1)
	    {
	        return true;
	    }
	    else
	    {
	       return false;
	    }
	}

	// 特殊检查充值任务完成没
	public function canRewardByCharge($periodID, $misID)
	{
	    // 可以领取的奖励
	    $toReward = FestivalActLogic::dealChargeInfo($this->uid);
	    if (isset($toReward[$periodID][$misID])
	        && $toReward[$periodID][$misID] > 0)
	    {
	        return true;
	    }
	    return false;
	}

	// 特殊检查登陆任务完成没
	// 要是登陆任务的话就需要先获取下 - 登陆奖励只能领取今天的
	public function canRewardByLoginTask($periodID, $misID, $buqian)
	{
	    // 看看是需要第几天
	    $need = FestivalActConf::getInstance()->getPeriodMisKey($periodID, $misID, FestivalActDef::NEED);
	    // 看看今天是第几天
	    $day = FestivalActConf::getInstance()->getCurDay();
	    // 还没到签到的日期
	    if (!$buqian)
	    {
	        if ($need != $day)
	        {
	            throw new FakeException('sign curday:%s != needday:%s.', $day, $need);
	        }
	    }
	    else
	    {
	        // 还没到签到的日期
	        if ($need >= $day)
	        {
	            throw new FakeException('fill in sign curday:%s <= needday:%s.', $day, $need);
	        }
	    }
	    // 排除本身不该领奖的状况，再看下数据可不可以领
	    $bigType = FestivalActDef::ACT_TYPE_TASK;
	    $status = $this->getPeriodBigTypeMisIDStatus($periodID, $bigType, $misID);
	    if ($status <= 1)
	    {
	        return true;
	    }
	    return false;
	}


	public static function getInstance($uid=0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
		}

		if ((NULL == self::$_instance) || (self::$_instance->getUid() != $uid))
		{
			self::$_instance = new self($uid);
		}

		return self::$_instance;
	}

	public static function release()
	{
		if (self::$_instance != NULL)
		{
			self::$_instance = NULL;
		}
	}

	public function getInfo()
	{
		return $this->dataModify;
	}

	/**
	 * 任务加数值，并判断完成与否，不更新
	 */
	public function notifyTask($type_id, $num, $isAll = FALSE)
	{
	    // 先获得当前季度
	    $confObj = FestivalActConf::getInstance();
	    $curPeriod = $confObj->getCurPeriod();
	    // 领奖期间就不计数啦
	    if ($curPeriod <= 0)
	    {
	    	Logger::debug('notifyTask return, FestivalAct is only reward, period = 0');
	    	return ;
	    }
	    // 从配置里取分类过的任务数据
	    $typeInfo = $confObj->getMisArrPartByPeriodBigType();
	    // 大类就是任务
	    $bigType = FestivalActDef::ACT_TYPE_TASK;
	    // 循环遍历当前季度的任务
	    foreach ($typeInfo[$curPeriod][$bigType] as $misID)
	    {
	        // 看看是不是这个类的
	        $misType = $confObj->getPeriodMisKey($curPeriod, $misID, FestivalActDef::TYPE_ID);
	        if ($misType == $type_id)
	        {
	            // 在改数据前要确认存在，没有的话初始化一下
	            $this->checkData($curPeriod, $bigType, $misID);
	            // 达标的数量
                $needNum = $confObj->getPeriodMisKey($curPeriod, $misID, FestivalActDef::NEED);
                // 是直接加数值的通知
                if (!$isAll)
                {
                    $this->addTaskNum($num, $needNum, $curPeriod, $bigType, $misID);
                }
                // 是更新总量的通知
                else
                {
                    $this->updateTaskNum($num, $needNum, $curPeriod, $bigType, $misID);
                }
                // 时间统一加一下
                $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$curPeriod][$bigType][$misID][3] = Util::getTime();
	        }
	    } 
	}

	/**
	 * 针对每天重置的数据，核对下上次更新的时间，然后保存数据
	 */
	protected function taskDataResetCheck($period, $bigType, $misID)
	{
	    $update_time = $this->getPeriodBigTypeMisIDUpdateTime($period, $bigType, $misID);
	    if (!Util::isSameDay($update_time))
	    {
	        $before = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1];
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][0] += $before;
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1] = 0;
	    }
	}

	/**
	 * 需要更新任务的总量
	 */
	protected function updateTaskNum($num, $needNum, $period, $bigType, $misID)
	{
	    // 看看是不是每天重置的
	    $reset = FestivalActConf::getInstance()->getPeriodMisKey($period, $misID, FestivalActDef::DAY_RESET);
	    // 是重置的话就检查下是否要重置
	    if ($reset)
	    {
	        $this->taskDataResetCheck($period, $bigType, $misID);
	    }
	    // 更新的量比之前的大才有效
	    $today = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1];
	    if ($num > $today)
	    {
	        $before = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][0];
	        $status = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][2];
	        // 不管什么状态都计量
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1] = $num;
	        // 完成啦
	        if (($before + $num >= $needNum) && $status == FestivalActDef::STATUS_UNFINISH)
	        {
	            $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][2] = FestivalActDef::STATUS_FINISH;
	        }
	    }
	}
	

	/**
	 * 直接加数值的任务
	 */
	protected function addTaskNum($num, $needNum, $period, $bigType, $misID)
	{
	    $beforeNum = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1];
	    // 完没完成都计量
	    $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][1] += $num;
	    // 当前的状态
	    $status = $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][2];
	    // 完成任务的话
	    if (($beforeNum + $num >= $needNum) && $status == FestivalActDef::STATUS_UNFINISH)
	    {
	        $this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$period][$bigType][$misID][2] = FestivalActDef::STATUS_FINISH;
	    }
	}

	public function rfrFestivalAct()
	{
		if ($this->dataModify[FestivalActDef::UPDATE_TIME] < FestivalActConf::getInstance()->getActStartTime())
		{
			$this->dataModify = $this->initData();
		}
	}

	/**
	 * 任务里有从0变1的就通知下前端红点
	 */
	protected function checkUpateRedTip()
	{
	    // 要推送的内容
	    $pushArr = array();
	    // 获取当前的时期
	    $curPeriod = FestivalActConf::getInstance()->getCurPeriod();
	    // 只检查任务类的
	    $bigType = FestivalActDef::ACT_TYPE_TASK;
	    // 数据库里没信息就直接返回吧
	    if (!isset($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$curPeriod][$bigType]))
	    {
	        return;
	    }
	    // 遍历新的
	    foreach ($this->dataModify[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$curPeriod][$bigType] as $misID => $info)
	    {
	        if ($info[2] == FestivalActDef::STATUS_FINISH)
	        {
	            // 原来就没有的
	            if (!isset($this->data[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$curPeriod][$bigType][$misID])
	                || ($this->data[FestivalActDef::VA_DATA][FestivalActDef::VA_PERIOD][$curPeriod][$bigType][$misID][2] == FestivalActDef::STATUS_UNFINISH))
	            {
	                $pushArr[$misID] = FestivalActDef::STATUS_FINISH;
	            }
	        }
	    }
	    Logger::debug('checkUpateRedTip pushArr:%s.', $pushArr);
	    // 有内容的话就推送
	    if (!empty($pushArr))
	    {
	        RPCContext::getInstance()->sendMsg(array($this->uid), PushInterfaceDef::FESTIVALACT_NEW_FINISH, $pushArr);
	    }
	}
	
	public function update()
	{
	    Logger::debug('FestivalActManager update dataModify:%s.', $this->dataModify);
		if (!empty($this->dataModify) && ($this->data != $this->dataModify))
		{
		    $this->dataModify[FestivalActDef::UPDATE_TIME] = Util::getTime();
			FestivalActDao::update($this->uid, $this->dataModify);
			// 任务里有从0变1的就通知下前端红点
			$this->checkUpateRedTip();			
			$this->data = $this->dataModify;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */