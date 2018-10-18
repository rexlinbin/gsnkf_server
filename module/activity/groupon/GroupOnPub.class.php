<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GroupOnPub.class.php 153207 2015-01-16 14:28:26Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/GroupOnPub.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-01-16 14:28:26 +0000 (Fri, 16 Jan 2015) $$
 * @version $$Revision: 153207 $$
 * @brief 
 *  
 **/

/**
 * 团购公共数据管理类
 * protected $dataModify 公用团购数据
 * array
 * [
 *      goodslist=>array 活动期间所有天的商品列表
 *      [
 *          为了实现物品团购次数不重置，现在商品列表不分为每天的了，都放一起，获取某天商品列表的时候，再过滤出来
 *          goodid:int商品id => soldNum:售出数量
 *      ]
 *      refreshtime:int 最新刷新时间
 *      timerid:int timer
 * ]
 */
class GroupOnPub
{
    protected $data = NULL;
    protected $dataModify = NULL;
    private static $INSTANCE = NULL;
    
    protected $curDay = NULL;

    public static function getInstance()
    {
        if(self::$INSTANCE == NULL || !isset(self::$INSTANCE))
        {
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }

    public function __construct()
    {
    	$this->curDay = EnActivity::getActivityDay(ActivityName::GROUPON); 
    	if( $this->curDay < 0 )
    	{
    		throw new FakeException('not in activity time');
    	}
        $this->loadGroupOnData();
    }

    public function loadGroupOnData()
    {
        if($this->dataModify === null)
        {
            $ret = GroupOnDao::selectGroupOn(GroupOnConf::DEFAULT_AID);
            
            $conf = EnActivity::getConfByName(ActivityName::GROUPON);
            if(empty($ret) || $ret[GroupOnDef::VADATA][GroupOnDef::TBL_FIELD_REFRESHTIME]  < $conf['start_time'] )
            {
            	Logger::info('init data. curDay:%d', $this->curDay);
            	$data = self::getInitData($this->curDay);
            }
            else
            {
                if( isset($ret[GroupOnDef::VADATA][GroupOnDef::TBL_FIELD_GOODSLIST])
                    && count($ret[GroupOnDef::VADATA][GroupOnDef::TBL_FIELD_GOODSLIST]) !=
                        count($ret[GroupOnDef::VADATA][GroupOnDef::TBL_FIELD_GOODSLIST], 1))
                {
                    throw new InterException("the groupon code is changed, but the activity not, so it not work.");
                }
            	$data = $ret[GroupOnDef::VADATA];
            }
            
            $this->dataModify = $data;
            $this->data = $data;
        }
    }

    public static function getInitData($curDay)
    {
    	$goodList = array();
    	for($i = 0; $i <= $curDay; $i++)
    	{
    		$goodList = Util::arrayAdd2V( array($goodList, GroupOnUtil::getDayGoodListFromConf( $i )) );
    	}
    	 
    	$data = array(
            			GroupOnDef::TBL_FIELD_GOODSLIST => $goodList,
            			GroupOnDef::TBL_FIELD_REFRESHTIME => 0,
            			GroupOnDef::TBL_FIELD_TIMERID => 0,
            	);
    	return $data; 
    }
    
    public function needRefreshGoodList()
    {
    	if( Util::isSameDay( $this->dataModify[GroupOnDef::TBL_FIELD_REFRESHTIME] ) )
    	{
    		return false;
    	}
    	return true;
    }
    
    public function refreshGoodList()
    {
    	if( $this->needRefreshGoodList() == false )
    	{
    		throw new InterException('already refresh');
    	}

        $goodList = $this->dataModify[GroupOnDef::TBL_FIELD_GOODSLIST];
        /**
         * 核心逻辑
         */
        if( !empty($this->curDay) )
        {
            $goodList = Util::arrayAdd2V( array($goodList, GroupOnUtil::getDayGoodListFromConf( $this->curDay )) );
            $this->dataModify[GroupOnDef::TBL_FIELD_GOODSLIST] = $goodList;
        }
        $this->dataModify[GroupOnDef::TBL_FIELD_REFRESHTIME] = Util::getTime();

        if(empty($this->dataModify[GroupOnDef::TBL_FIELD_TIMERID]))
        {
            $this->addGroupOnTimerTaskForReward();
        }
        $this->checkdoReissueTimer();
    }
    

    public function updateGroupOnData()
    {
        if($this->dataModify != $this->data)
        {
            $arrField = array(
                GroupOnDef::AID => GroupOnConf::DEFAULT_AID,
                GroupOnDef::VADATA => $this->dataModify,
            );
            GroupOnDao::iOrUGroupOn($arrField);
        }
        $this->data = $this->dataModify;
    }

    /**
     * 获得某天的商品列表
     */
    public function getGoodsListOfDay($day = -1)
    {
    	if( $day < 0 )
    	{
    		$day = $this->curDay;
    	}
        $goodList = $this->dataModify[GroupOnDef::TBL_FIELD_GOODSLIST];
        $goodListIdsFromConf = GroupOnUtil::getDayGoodListIds($day);
        $ret = GroupOnUtil::getDayGoodListFromConf($day);
        foreach($goodList as $goodId => $soldNum)
        {
            if(in_array($goodId, $goodListIdsFromConf))
            {
                $ret[$goodId] = $soldNum;
            }
        }

        return $ret;
    }

    public function getGoodList()
    {
        return $this->dataModify[GroupOnDef::TBL_FIELD_GOODSLIST];
    }

	public function getGoodBuyNum($goodId, $day = -1)
	{
		$goodsList = $this->getGoodsListOfDay();
		if( !isset($goodsList[$goodId]) )
		{
			throw new FakeException('no goodId:%d in day:%d. goodList:%s', $goodId, $day, $goodsList);
		}
		return $goodsList[$goodId];
	}

	public function getDayIndex()
	{
		return $this->curDay;
	}
    
	public function addGoodBuyNum($goodId, $num = 1)
	{
		if( $num < 0 )
		{
			throw new InterException('invalid param. num:%d', $num);
		}
		if( !isset( $this->dataModify[GroupOnDef::TBL_FIELD_GOODSLIST][$goodId] ) )
		{
			throw new InterException('not refresh good list or invalid goodId. day:%d, goodId:%d', $this->curDay, $goodId);
		}
		
		$this->dataModify[GroupOnDef::TBL_FIELD_GOODSLIST][$goodId] += $num;
	}

    /**
     * 补发奖励时间--活动最后一天0点补发
     * @return array
     */
    public function getReissueTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        $endTime = $conf['end_time'] ;
        $date = date("Y-m-d", $endTime);
        //0点1分补发奖励 补偿时间
        return strtotime($date." "."00:01:00");
    }

    public function checkdoReissueTimer()
    {
        Logger::trace('GroupOnPub checkdoReissueTimer');
        if(EnActivity::isOpen(ActivityName::GROUPON) == FALSE)
        {
            throw new FakeException('GroupOnLogic::checkdoReissueTimer is not open');
        }
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        $startTime = $conf['start_time'] ;
        $taskName = GroupOnDef::GROUPON_REISSUE_REWARD_TASK_NAME;
        $reissueTime = $this->getReissueTime();
        $ret = EnTimer::getArrTaskByName($taskName, array(TimerStatus::RETRY,TimerStatus::UNDO), $startTime);
        $findValid = FALSE;
        foreach($ret as $index => $timer)
        {
            if($timer['status'] == TimerStatus::RETRY)
            {
                Logger::fatal('the timer %d is retry.but the groupon activity not end.',$timer['tid']);
                TimerTask::cancelTask($timer['tid']);
                continue;
            }
            if($timer['status'] == TimerStatus::UNDO)
            {
                if($timer['execute_time'] != $reissueTime)
                {
                    Logger::fatal('invalid timer %d.execute_time %d',$timer['tid'],$timer['execute_time']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else if($findValid)
                {
                    Logger::fatal('one more valid timer.timer %d.',$timer['tid']);
                    TimerTask::cancelTask($timer['tid']);
                }
                else
                {
                    Logger::trace('checkRewardTimer findvalid');
                    $findValid = TRUE;
                }
            }
        }
        if($findValid == FALSE)
        {
            Logger::fatal('no valid timer.addTask for groupon.reissueForTime.');
            $this->addGroupOnTimerTaskForReward();
        }
    }

    private function addGroupOnTimerTaskForReward()
    {
        $taskName = GroupOnDef::GROUPON_REISSUE_REWARD_TASK_NAME;
        $reissueTime = $this->getReissueTime();
        $this->dataModify[GroupOnDef::TBL_FIELD_TIMERID] = TimerTask::addTask(
            GroupOnConf::SPECIAL_UID, $reissueTime, $taskName, array());
        Logger::info('add reissue timer:%d, time:%s', $this->dataModify[GroupOnDef::TBL_FIELD_TIMERID], $reissueTime);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */