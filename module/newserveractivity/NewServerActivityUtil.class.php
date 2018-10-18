<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewServerActivityUtil.class.php 243504 2016-05-19 02:50:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/newserveractivity/NewServerActivityUtil.class.php $
 * @author $Author: MingTian $(linjiexin@babeltime.com)
 * @date $Date: 2016-05-19 02:50:46 +0000 (Thu, 19 May 2016) $
 * @version $Revision: 243504 $
 * @brief “开服7天乐”工具类
 *  
 **/
class NewServerActivityUtil
{
	public static function getTaskConf()
	{
		return btstore_get()->NEW_SERVER_ACT_REWARD;
	}
	
	/*
	 *  根据任务id获得任务的类型id
	*/
	public static function getType($taskId)
	{
		$type = substr($taskId, 0, 3);
		return $type;
	}
	
	public static function getTimeConf()
	{
		return btstore_get()->NEW_SERVER_ACT;
	}
	
	/*
	 *  获取配置中的任务类型id数组
	*/
	public static function getTypeArrConf()
	{
		$ret = array();
		$conf = self::getTaskConf();
		foreach ($conf[NewServerActivityCsvDef::TYPE] as $type => $info)
		{
			$ret[] = $type;
		}
		return $ret;
	}
	
	/*
	 *  获取任务的显示天数
	*/
	public static function getTaskDisplayDate($taskId)
	{
		$timeConf = self::getTimeConf();
		return $timeConf[NewServerActivityCsvDef::OPENDAY][$taskId];
	}
	
	/*
	 *  获取“开服7天”的开始天数
	*/
	public static function getMinOpenDate()
	{
		$timeConf = self::getTimeConf();
		return intval($timeConf['minOpenDay']);
	}
	
	/*
	 *  获取“开服7天”的任务更新截止天数
	*/
	public static function getTaskDeadLine()
	{
		$timeConf = self::getTimeConf();
		return $timeConf[NewServerActivityCsvDef::DEADLINE];
	}
	
	/*
	 *  判断任务是否是副本类型
	*  @para $taskId 任务id
	*/
	public static function isNCopyType($taskId)
	{
		// 如果是副本类型,则标记
		$flag = false;
		$type = self::getType($taskId);
		if (in_array($type, NewServerActivityDef::$NCOPY_TYPES))
		{
			$flag = true;
		}
		return $flag;
	}
	
 	/*
     *  计算今天是开服后的第几天
     */
    public static function getActivityCurDay()
    {
        $openServerDay = strtotime(GameConf::SERVER_OPEN_YMD);
        $curDayNum = (strtotime(date('Ymd', Util::getTime()) . '000000') - $openServerDay) / SECONDS_OF_DAY + 1;
        return $curDayNum;
    }
    
    /*
     *  用于判断当天是否能更新任务
    */
    public static function canUpdateTask()
    {
    	$minOpenDate = self::getMinOpenDate();
    	$deadLine = self::getTaskDeadLine();
    	$curDate = self::getActivityCurDay();
    	$ret = (($curDate >= $minOpenDate) && ($curDate <= $deadLine)) ? true : false;
    	return $ret;
    }
    
    /*
     *  获得任务id对应的完成条件
    */
    public static function getTaskRqrConf($taskId)
    {
    	$conf = self::getTaskConf();
    	$type = self::getType($taskId);
    	$ret = $conf[NewServerActivityCsvDef::TYPE][$type][$taskId][NewServerActivityCsvDef::RQE];
    	return $ret;
    }
    
    /*
     *  获得任务id对应的奖励数组
    */
    public static function getTaskRewardConf($taskId)
    {
    	$conf = self::getTaskConf();
    	$type = self::getType($taskId);
    	$ret = $conf[NewServerActivityCsvDef::TYPE][$type][$taskId][NewServerActivityCsvDef::REWARD];
    	return $ret;
    }
    
    /*
     *  获得同一任务类型id的任务id数组
    */
    public static function getArrTaskIdOfType($type)
    {
    	$ret = array();
    	$conf = self::getTaskConf();
    	$arrTask = $conf[NewServerActivityCsvDef::TYPE][$type]->toArray();
    	$ret = array_keys($arrTask);
    	return $ret;
    }
    
    /*
     *  获取“开服7天乐”的关闭时间
    */
    public static function getCloseDate()
    {
    	$timeConf = self::getTimeConf();
    	return $timeConf[NewServerActivityCsvDef::CLOSEDAY];
    }
    
    public static function getCurDay()
    {
    	// 不管开服时间是当天几点,都按照当天0点算
    	$openServerTime = strtotime(GameConf::SERVER_OPEN_YMD);
    	$curDay = intval((Util::getTime() - $openServerTime) / SECONDS_OF_DAY) + 1;
    	return $curDay;
    }
    
    /*
     *  用于判断当天是否能显示该功能
    */
    public static function canDisplay()
    {
    	$minOpenDate = self::getMinOpenDate();
    	$closeDate = self::getCloseDate();
    	$curDate = self::getActivityCurDay();
    	$ret = (($curDate >= $minOpenDate) && ($curDate <= $closeDate)) ? true : false;
    	return $ret;
    }
    
    /*
     *  获得open_seven_reward.csv配置中的所有任务id数组
    */
    public static function getArrTaskIdConf()
    {
    	$conf = self::getTaskConf();
    	return $conf[NewServerActivityCsvDef::TASKID]->toArray();
    }
    
	/*
     *  获得open_seven_act.csv配置中的所有任务id数组，
     *  在open_seven_act.csv里的所有任务id只是open_seven_reward.csv中所有任务id的子集
    */
    public static function getArrOpenTaskIdConf()
    {
    	$conf = self::getTimeConf();
    	$arr = $conf[NewServerActivityCsvDef::OPENDAY]->toArray();
    	$ret = array_keys($arr);
    	return $ret;
    }
    
    /*
     *  得到任务可以更新期间的所有充值的金币数量
    */
    public static function getDuringRechargeGoldSum()
    {
    	$confDeadLine = self::getTaskDeadLine();
    	$openTime = strtotime(GameConf::SERVER_OPEN_YMD);
    	$deadLine = $openTime + SECONDS_OF_DAY * $confDeadLine;
    	return EnUser::getRechargeGoldByTime($openTime, $deadLine);
    }
    
    public static function getGoodsData($day)
    {
    	$timeConf = self::getTimeConf();
    	return $timeConf[NewServerActivityCsvDef::GOODS][$day];
    }
    
    public static function getGoodsItem($day)
    {
    	$goodsData = self::getGoodsData($day);
    	return $goodsData[NewServerActivityCsvDef::ITEMS]->toArray();
    }
    
    /**
     * 判断En触发的任务类型是否在配置中配有,false表示在配置里找不到,true表示能找到
     */
    public static function isInConf($uid, $taskType)
    {
    	$arrTaskType = self::getTypeArrConf();
    	if (!in_array($taskType, $arrTaskType))
    	{
    		Logger::warning('taskType:%d not belongs to conf', $taskType);
    		return false;
    	}
    	return true;
    }
    
    /**
     * 判断当前任务是否在今天可见,如果不可见,即使任务提前完成了,也不能领奖
     * @param $taskId 任务id
     * @return boolean 
     */
    public static function isTaskDisplay($taskId)
    {
    	$curDay = self::getCurDay();
    	$timeConf = self::getTimeConf();
    	$needDay = $timeConf[NewServerActivityCsvDef::OPENDAY][$taskId];
    	return ($curDay >= $needDay) ? true: false;
    }
    
    /**
     * 开服时间大于更新新服活动时间，才能开启这个活动
     * @return boolean
     */
    public static function isOpen()
    {
    	$openServerTime = strtotime(GameConf::SERVER_OPEN_YMD);
    	$openActTime = strtotime(PlatformConfig::NEW_SERVER_ACTIVITY_TIME);
    	return $openServerTime >= $openActTime ? true : false;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */