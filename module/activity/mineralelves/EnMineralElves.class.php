<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnMineralElves.class.php 256580 2016-08-16 02:41:49Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/mineralelves/EnMineralElves.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-08-16 02:41:49 +0000 (Tue, 16 Aug 2016) $
 * @version $Revision: 256580 $
 * @brief 
 *  
 **/
class EnMineralElves
{
	public static function readMineralElvesCSV($arrData, $version, $startTime, $endTime, $needOpenTime)
	{
		/*if(ActivityConf::$STRICT_CHECK_CONF
				&& ! Util::isInCross()
				&& EnActivity::isOpen(ActivityName::MINERALELVES))
		{
			$confData = EnActivity::getConfByName(ActivityName::MINERALELVES);
			if($confData['start_time'] != $startTime)
			{
				throw new ConfigException('start_time cannot change');
			}
		}*/
		
		$ZERO=0;
		$keyArr=array(
			'id'=>$ZERO,
				'start_time'=>++$ZERO,
				'end_time'=>++$ZERO,
				'page'=>++$ZERO,
				'num'=>++$ZERO,
				'last_time'=>++$ZERO,
				'wait_time'=>++$ZERO,
				'reward'=>++$ZERO,
				'npc'=>++$ZERO,
				'name'=>++$ZERO,
				'desc'=>++$ZERO,
				'expl'=>++$ZERO,
				'week_day'=>++$ZERO,//每周的这几天开
		);
		$arrConf=array();
		foreach ($arrData as $data)
		{
			if (empty($data))
			{
				break;
			}
			$conf=array();
			foreach ($keyArr as $key=>$v)
			{
				if ($key=='reward')
				{
					$firsttmp=explode(',',$data[$v]);
					foreach ($firsttmp as $info)
					{
						$secondtmp=explode('|',$info);
						if (count($secondtmp)!=4)
						{
							throw new FakeException('mineral elves config err!');
						}
						$conf[$key][]=array(
								'reward_info'=>array(intval($secondtmp[0]),intval($secondtmp[1]),intval($secondtmp[2])),
								'weight'=>intval($secondtmp[3]),
						);
					}
				}elseif ($key=='name'||$key=='desc'||$key=='expl')
				{
					$conf[$key]=$data[$v];
				}elseif ($key=='week_day')
				{
					$conf[$key]=array();
					$tmp=explode('|', $data[$v]);
					foreach ($tmp as $weekDay)
					{
						$conf[$key][]=intval($weekDay);
					}
				}
				else {
					if ($key=='wait_time'||$key=='last_time')
					{
						if (intval($data[$v])<60)
						{
							//不能接受小于一分钟的
							throw new FakeException('mineral elves wait time:%d can not less than 60s',intval($data[$v]));
						}
					}
					$conf[$key]=intval($data[$v]);
				}
			}
			if (empty($conf))
			{
				throw new FakeException('blank line!');
			}
		}
		$arrConf=$conf;
		
		if (!Util::isInCross()) 
		{
			self::genMineralElves($arrConf, $startTime, $endTime, $needOpenTime);
		}
		
		return $arrConf;
	}
	
	public static function genMineralElves($conf,$startTime,$endTime,$needOpenTime)
	{
		//先看开服时间
		$serverOpenTime=strtotime(GameConf::SERVER_OPEN_YMD.GameConf::SERVER_OPEN_TIME);
		if ($serverOpenTime>$needOpenTime)
		{
			Logger::info('can not gen mineral elves,serveropentime:%d,needopentime:%d',$serverOpenTime,$needOpenTime);
			return ;
		}
		$curtime=Util::getTime();
		//零点是活动开始的那天的零点
		$zerotime=strtotime(date("Ymd", $startTime).'000000');
		$dayStartTime=$zerotime+$conf['start_time'];
		$dayEndTime=$zerotime+$conf['end_time'];
		//确保下次生成精灵是在未来时间
		while  ($curtime>$dayStartTime ||! in_array(intval(date ( 'w', $dayStartTime )), $conf['week_day']))
		{
			$dayStartTime+=SECONDS_OF_DAY;
			$dayEndTime+=SECONDS_OF_DAY;
		}
		$stageStartTime=$dayStartTime;
		$stageEndTime=$stageStartTime+$conf['last_time'];
		
		if ($stageEndTime>$dayEndTime||$stageEndTime>$endTime)
		{
			Logger::info('activity not open!');
			return ;
		}
		
		$elvesTaskArr=EnTimer::getArrTaskByName('mineralelves.__genMineralElves',array(TimerStatus::UNDO),Util::getTime());
		if (!empty($elvesTaskArr))
		{
			foreach ($elvesTaskArr as $value)
			{
				TimerTask::cancelTask($value['tid']);
			}
		}
		
		$args=array(array(
				'start_time'=>$stageStartTime,
				'end_time'=>$stageEndTime,
				'act_start_time'=>$startTime,
				'act_end_time'=>$endTime
				)
		);
		//
		TimerTask::addTask(0, $stageStartTime-30, 'mineralelves.__genMineralElves', $args);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */