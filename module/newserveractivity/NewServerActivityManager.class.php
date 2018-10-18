<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivityManager.class.php 243508 2016-05-19 02:58:20Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/NewServerActivityManager.class.php $
 * @author $Author: MingTian $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-19 02:58:20 +0000 (Thu, 19 May 2016) $
 * @version $Revision: 243508 $
 * @brief “开服7天乐”数据管理类
 *  
 **/
class NewServerActivityManager
{
	private $uid;
	private $data = NULL;
	private $dataModify = NULL;
	private static $Instance = array();
	
	/**
	 * @param $uid
	 * @return NewServerActivityManager
	 */
	public static function getInstance($uid)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
			if (empty($uid))
			{
				throw new FakeException("the uid in session is null");
			}
		}
		if (empty(self::$Instance[$uid]))
		{
			self::$Instance[$uid] = new self($uid);
		}
		// 写库操作save函数的执行一律通过hook来调用，不管任务的更新是En引发的还是getInfo引发的
		NewServerActivityForHook::add("NewServerActivity.$uid", self::$Instance[$uid]);
		return self::$Instance[$uid];
	}
	
	private function __construct($uid)
	{
		$this->uid = $uid;
	
		$this->dataModify = RPCContext::getInstance()->getSession(NewServerActivityDef::KEY_NEW_SERVER_ACT);
		if (empty($this->dataModify))
		{
			$this->dataModify = NewServerActivityDao::getData($this->uid);
			if (empty($this->dataModify))
			{
				$this->dataModify = array(
						NewServerActivitySqlDef::UID => $this->uid,
						NewServerActivitySqlDef::VA_INFO => array(),
						NewServerActivitySqlDef::VA_GOODS => array(),
				);
			}
			else
			{
				//检查是否存在配置里没有的任务类型老数据
				$this->checkOldConf();
			}
			//玩家登陆后第一次进入“开服7天”， 设置session
			RPCContext::getInstance()->setSession(NewServerActivityDef::KEY_NEW_SERVER_ACT, $this->dataModify);
		}
	
		$this->data = $this->dataModify;
	}
	
	public function checkOldConf()
	{
		$confData = NewServerActivityUtil::getTaskConf();
		if (!empty($this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO]))
		{
			// 把策划配置表中删除的老数据的任务类型在dataModify中剔除掉
			foreach ( $this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO] as $taskType => $data )
			{
				if (!isset($confData[NewServerActivityCsvDef::TYPE][$taskType]))
				{
					Logger::warning('old taskType:%d of DB is not in this version of OpenServerCelebration, is deleted', $taskType);
					unset($this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$taskType]);
				}
				else if ( isset($data[NewServerActivityDef::STATUS]) )
				{
					foreach ( $data[NewServerActivityDef::STATUS] as $taskId => $status )
					{
						if (!isset($confData[NewServerActivityCsvDef::TYPE][$taskType][$taskId]))
						{
							Logger::warning('old taskId:%d of DB is not in this version of OpenServerCelebration, is deleted', $taskId);
							unset($this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$taskType][NewServerActivityDef::STATUS][$taskId]);
						}
					}
				}
			}
		}
	}
	
	public function getTaskStatus($taskId)
	{
		$type = NewServerActivityUtil::getType($taskId);
		if (empty($this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type][ NewServerActivityDef::STATUS][$taskId]))
		{
			$ret = NewServerActivityDef::WAIT;
		}
		else
		{
			$ret = $this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type][ NewServerActivityDef::STATUS][$taskId];
		}
		return $ret;
	}
	
	public function setTaskStatus($taskId, $status)
	{
		$type = NewServerActivityUtil::getType($taskId);
		$this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type][ NewServerActivityDef::STATUS][$taskId] = $status;
	}
	
	public function getTaskFinishNum($taskId)
	{
		// 判断是不是副本类型,因为前端未做处理所以在这里需要自己处理,补齐副本进度
		$flag = NewServerActivityUtil::isNCopyType($taskId);
		$status = $this->getTaskStatus($taskId);
		if ($flag)
		{
			return ( $status >= NewServerActivityDef::COMPLETE ) ? 1 : 0;	
		}
		
		// 非副本类型
		$type = NewServerActivityUtil::getType($taskId);
		if (empty($this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type][ NewServerActivityDef::FINISHNUM]))
		{
			$ret = 0;
		}
		else
		{
			$ret = $this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type][ NewServerActivityDef::FINISHNUM];
		}
		return $ret;
	}
	
	public function setTaskFinishNum($taskId, $finishNum)
	{
		// 这个标记 用来判断是否是副本, 如果是则返回不记录进度
		$flag = NewServerActivityUtil::isNCopyType($taskId);
		if ($flag)
		{
			return ;
		}
		$type = NewServerActivityUtil::getType($taskId);
		$this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type][ NewServerActivityDef::FINISHNUM] = $finishNum;
	}
	
/*
	 * 如果任务为非副本类型时，$finishNum表示进度，
	 * 如果任务为副本类型时，$finishNum表示副本的id
	 */
    public function updateTask($taskType, $finishNum, $finishType = NewServerActivityDef::DEFAULTTYPE, $force = false)
    {
    	$canUpdate = NewServerActivityUtil::canUpdateTask();
    	$isOpen = NewServerActivityUtil::isOpen();
    	if (!$canUpdate || !$isOpen)
    	{
    		// 活动过了任务更新期限，就把En中的触发任务更新的函数忽视
    		return ;
    	}
    	
    	if($this->uid != RPCContext::getInstance()->getUid() && !$force)
    	{
    		Logger::debug("NewServerActivityManager.updateType. re forward uid:%d, taskType:%d, finishType:%s finishNum:%d",
    		$this->uid, $taskType, $finishType, $finishNum);
    		RPCContext::getInstance()->executeTask($this->uid, 'newserveractivity.updateTypeByOtherUser',
    		array($this->uid, $taskType, $finishNum, $finishType));
    		return;
    	}
    	
        // 检查传入的任务类型是否在配置中
        $arrTaskType = NewServerActivityUtil::getTypeArrConf();
        if (!in_array($taskType, $arrTaskType))
        {
            throw new FakeException('taskType:%d not belongs to conf', $taskType);
        }
        
        Logger::debug("before update task, dataModify is %s", $this->dataModify);
        Logger::info("uid:%d taskType:%d finishType:%s finishNum:%d", $this->uid, $taskType, $finishType, $finishNum);

        if (NewServerActivityDef::ACCUMTYPE == $finishType) 
        {
        	// 如果任务是累加类型,则设置 $finishNum使用标记
        	$isFinishNumUsed = false;
        }
        
        $arrTaskOfType = NewServerActivityUtil::getArrTaskIdOfType($taskType);
        foreach ($arrTaskOfType as $taskId)
        {
            $status = $this->getTaskStatus($taskId);
            $confRqrFinishNum = NewServerActivityUtil::getTaskRqrConf($taskId);
            $curFn = $this->getTaskFinishNum($taskId);
            switch ($finishType)
            {
                case NewServerActivityDef::RANKTYPE:
                    // 如果数据库里没记录排名信息,默认初始化为最低排名
                    if (0 == $curFn)
                    {
                    	$curFn = NewServerActivityDef::MAX_BOSS_RANK;
                    }
                    // 完成任务时的情况
                    if (($finishNum <= $confRqrFinishNum) && (NewServerActivityDef::WAIT == $status))
                    {
                        $this->setTaskFinishNum($taskId, $confRqrFinishNum);
                        $this->setTaskStatus($taskId, NewServerActivityDef::COMPLETE);
                    }
                    else if (($finishNum < $curFn) && (NewServerActivityDef::WAIT == $status))
                    {	// 未完成任务但进度增加的情况, 虽然 status没有改变，也要设置回去，如果该任务第一次写库时，库里没有status
                        $this->setTaskFinishNum($taskId, $finishNum);
                    }
                    break;
                case NewServerActivityDef::COPYTYPE:
                	if (($finishNum == $confRqrFinishNum) && (NewServerActivityDef::WAIT == $status))
                	{
                		$this->setTaskStatus($taskId, NewServerActivityDef::COMPLETE);
                	}
                    break;
                case NewServerActivityDef::ACCUMTYPE:
                	if (!$isFinishNumUsed)
                	{
                		// 累加类型传过来的是增量,所以要累加库里的进度
                		$finishNum += $curFn;
                		// 累加增量在同一类型的任务数组中只能使用一次,所以使用后把标记置为true
                		$isFinishNumUsed = true;
                	}
                	
                default:
                    if (($finishNum >= $confRqrFinishNum) && (NewServerActivityDef::WAIT == $status))
                    {
                        $this->setTaskFinishNum($taskId, $confRqrFinishNum);
                        $this->setTaskStatus($taskId, NewServerActivityDef::COMPLETE);
                    }
                    else if (($finishNum > $curFn) && (NewServerActivityDef::WAIT == $status))
                    {
                        $this->setTaskFinishNum($taskId, $finishNum);
                    }
                    break;
            }
        }
        Logger::debug("after update task, dataModify is %s", $this->dataModify);
    }
	
    public function buy($day)
    {
    	if (in_array($day, $this->dataModify[NewServerActivitySqlDef::VA_GOODS]))
    	{
    		//TODO这里有必要抛fake吗
    		throw new FakeException('the goods of day:%d is already bought', $day);
    	}
    	$this->dataModify[NewServerActivitySqlDef::VA_GOODS][] = $day;
    }
    
    /**
     * 判断$day对应的商品是够可以购买
     * @param unknown $day
     * @return number
     */
    public function isHadBuy($day)
    {
    	if (in_array($day, $this->dataModify[NewServerActivitySqlDef::VA_GOODS]))
    	{
    		return true;
    	}
    	return false;
    }
    
	public function save()
	{

		if ($this->data == $this->dataModify)
		{
			return ;
		}
		
		//如果该玩家的信息之前为空，直接insert
		if(empty($this->data[NewServerActivitySqlDef::VA_INFO]) && empty($this->data[NewServerActivitySqlDef::VA_GOODS]))
		{
			NewServerActivityDao::insert($this->dataModify);
			Logger::debug("the dataModify to insert is %s", $this->dataModify);
		}
		else 
		{
			$arrNeedUpdate = array();
			foreach ($this->dataModify as $key => $value)
			{
				if($value != $this->data[$key])
				{
					$arrNeedUpdate[$key] = $value;
				}	
			}
			
			Logger::debug("the dataModify to update is %s", $arrNeedUpdate);
			NewServerActivityDao::update($this->uid, $arrNeedUpdate);
		}
		
		if ($this->data[NewServerActivitySqlDef::VA_INFO] != $this->dataModify[NewServerActivitySqlDef::VA_INFO])
		{
			$arrTaskNewFinish = array();
			$arrTaskNewFinish = $this->getArrNewFinishedTask();
			Logger::debug("want to sendMsg array is %s", $arrTaskNewFinish);
			if(!empty($arrTaskNewFinish))
			{
				RPCContext::getInstance()->sendMsg(array($this->uid), PushInterfaceDef::NEWSERVERACTIVITY_NEW_FINISH, $arrTaskNewFinish);
			}
		}
		
		$this->data = $this->dataModify;
		
		// 如果当前Manager对象的记录的uid等于session中的uid，回写session
		if ($this->uid == RPCContext::getInstance()->getUid())
		{
			RPCContext::getInstance()->setSession(NewServerActivityDef::KEY_NEW_SERVER_ACT, $this->dataModify);
		}
		
	}
	
	
	// 缺推送的函数
	private function getArrNewFinishedTask()
	{
		// 用于记录有状态变更,用来推送给前端
		$arrTaskNewFinish = array();
		
		$curDate = NewServerActivityUtil::getActivityCurDay();
		foreach ($this->dataModify[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO] as $type => $value)
		{
			if (empty($value[NewServerActivityDef::STATUS]))
			{
				continue;
			}
			foreach ($value[NewServerActivityDef::STATUS] as $taskId => $status)
			{
				$openDate = NewServerActivityUtil::getTaskDisplayDate($taskId);
				if ($curDate < $openDate)
				{
					continue;
				}
				if (NewServerActivityDef::COMPLETE == $status)
				{
					// 如果data中对应于dataModify中的类型不为空
					if (!empty($this->data[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type]))
					{
						$arrTaskData = $this->data[NewServerActivitySqlDef::VA_INFO][NewServerActivitySqlDef::TASKINFO][$type];
						// data中对应于dataModify中的类型不为空,但是具体的任务id为空
						if (empty($arrTaskData[NewServerActivityDef::STATUS][$taskId]))
						{
							$arrTaskNewFinish[] = $taskId;
						}
					}
					else
					{
						$arrTaskNewFinish[] = $taskId;
					}
				}
			}
		}
		
		return $arrTaskNewFinish;
	}
}


/**
 * Class NewServerActivityGoodsManager
 * 可以购买当天以及之前天数的商品,每个商品在整个“开服7天乐”中同一个玩家只能购买一次
 * $goodsData => array
 * {
 *  'day' => int,
 *  'buy_num' => int,
 * }
 */

class NewServerActivityGoodsManager
{
	private $day = 0;
	private $goodsData = array();
	private $goodsDataModify = array();
	private static $Instance = array();
	/**
	 * 返回“开服狂欢抢购商品数据管理类”对象
	 * @param $day
	 * @throws FakeException
	 * @return NewServerActivityGoodsManager
	*/
	public static function getInstance($day)
	{
		$curDay = NewServerActivityUtil::getCurDay();
		// 可以购买当天以及之前天数的商品
		if ((empty($day)) || ($day > $curDay) || ($day <= 0))
		{
			throw new FakeException('the param:day(%d) is error, must be <= curDay:%d', $day, $curDay);
		}
		if (empty(self::$Instance[$day]))
		{
			self::$Instance[$day] = new self($day);
		}
		return self::$Instance[$day];
	}

	private function __construct($day)
	{
		$this->day = $day;
		if (empty($this->goodsDataModify))
		{
			$this->goodsDataModify = NewServerActivityDao::getGoodsData($day);
			if (empty($this->goodsDataModify))
			{
				$this->goodsDataModify = array(
						NewServerActivitySqlDef::DAY => $day,
						NewServerActivitySqlDef::BUY_NUM => 0,
				);
			}
		}
		$this->goodsData = $this->goodsDataModify;
	}

	public function getGoodsRemainNum()
	{
		$confLimitNum = btstore_get()->NEW_SERVER_ACT[NewServerActivityCsvDef::GOODS][$this->day][NewServerActivityCsvDef::LIMITNUM];
		// 购买数量可能会因为同步问题超限,所以$remainNum可能为负数
		$remainNum = $confLimitNum - $this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM];
		return $remainNum > 0 ? $remainNum: 0;
	}

	public function isExceedLimit()
	{
		if (empty($this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM]))
		{
			return NewServerActivityDef::NOT_LIMIT;
		}
		$goodsLimitNum = $this->getGoodsRemainNum();
		return $goodsLimitNum ? NewServerActivityDef::NOT_LIMIT: NewServerActivityDef::LIMIT;
	}

	public function buy()
	{
		if (empty($this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM]))
		{
			$this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM] = 0;
		}
		$this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM] += 1;
	}

	public function update()
	{
		if( $this->goodsData[NewServerActivitySqlDef::BUY_NUM] == $this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM] )
		{
			Logger::debug('goods num no changed');
			return;
		}

		if (empty($this->goodsData[NewServerActivitySqlDef::BUY_NUM]))
		{
			NewServerActivityDao::insertGoods($this->goodsDataModify);
		}
		else
		{
			$arrValue = array();
			//需要用自增操作
			$deltNum = $this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM] - $this->goodsData[NewServerActivitySqlDef::BUY_NUM];
			if($deltNum <= 0 )
			{
				throw new InterException('invalid data. cur:%s, bak:%s', $this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM], $this->goodsData[NewServerActivitySqlDef::BUY_NUM]);
			}
			$arrValue[NewServerActivitySqlDef::BUY_NUM] = new IncOperator( $deltNum );

			NewServerActivityDao::updateGoods($this->day, $arrValue);
		}

		$this->goodsData[NewServerActivitySqlDef::BUY_NUM] = $this->goodsDataModify[NewServerActivitySqlDef::BUY_NUM];
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */