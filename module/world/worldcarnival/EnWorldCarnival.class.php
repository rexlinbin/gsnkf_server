<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnWorldCarnival.class.php 198211 2015-09-11 11:58:22Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldcarnival/EnWorldCarnival.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-09-11 11:58:22 +0000 (Fri, 11 Sep 2015) $
 * @version $Revision: 198211 $
 * @brief 
 *  
 **/
 
class EnWorldCarnival
{
	/**
	 * 读取跨服嘉年华活动配置
	 *
	 * @param array $arrData
	 * @return array
	 */
	public static function readWorldCarnivalCsv($arrData, $version, $startTime, $endTime, $needOpenTime)
	{
		$incre = 0;
		$tag = array
		(
				'id' => $incre++,
				'fighter' => $incre++,
				'watcher' => $incre++,
				'begin_time' => $incre++,
				'normal_period' => $incre++,
				'final_period' => $incre++,
		);

		$conf = array();
		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
				
			// id
			$id = intval($data[$tag['id']]);
			$conf['id'] = $id;
			
			// 参赛者配置
			$conf['fighters'] = array();
			$arrFighters = explode(',', $data[$tag['fighter']]);
			if (count($arrFighters) != 4) 
			{
				throw new ConfigException('fighters config count is error, config[%s]', $arrFighters);
			}
			for ($i = 0; $i < count($arrFighters); ++$i)
			{
				$arrDetail = explode('|', $arrFighters[$i]);
				if (count($arrDetail) < 4 || intval($arrDetail[0] != $i + 1)) 
				{
					throw new ConfigException('one fighter config count is error, config[%s]', $arrDetail);
				}
				$aPos = intval($arrDetail[0]);
				$aServerId = intval($arrDetail[1]);
				$aPid = intval($arrDetail[2]);
				$aName = $arrDetail[3];
				$visible = 1;
				if (isset($arrDetail[4]) && intval($arrDetail[4]) == 0) 
				{
					$visible = 0;
				}
				$conf['fighters'][$aPos] = array('server_id' => $aServerId, 'pid' => $aPid, 'pos' => $aPos, 'visible' => $visible);
			}
			
			// 围观者配置
			$conf['watchers'] = array();
			$arrWatchers = explode(',', $data[$tag['watcher']]);
			for ($i = 0; $i < count($arrWatchers); ++$i)
			{
				$arrDetail = explode('|', $arrWatchers[$i]);
				if (count($arrDetail) < 3)
				{
					throw new ConfigException('one watcher config count is error, config[%s]', $arrDetail);
				}
				$aServerId = intval($arrDetail[0]);
				$aPid = intval($arrDetail[1]);
				$aName = $arrDetail[2];
				$visible = 1;
				if (isset($arrDetail[3]) && intval($arrDetail[3]) == 0)
				{
					$visible = 0;
				}
				$conf['watchers'][] = array('server_id' => $aServerId, 'pid' => $aPid, 'visible' => $visible);
			}
			
			// 开始时间偏移
			$beginTime = intval($data[$tag['begin_time']]);
			$conf['begin_time'] = $beginTime;
			
			// 正常小轮比赛间隔
			$normalPeriod = intval($data[$tag['normal_period']]);
			$conf['normal_period'] = $normalPeriod;
			
			// 决赛前间隔
			$finalPeriod = intval($data[$tag['final_period']]);
			$conf['final_period'] = $finalPeriod;

			break;
		}
		
		if (ActivityConf::$STRICT_CHECK_CONF
			&& !Util::isInCross()
			&& EnActivity::isOpen(ActivityName::WORLDCARNIVAL))
		{
			$session = $conf['id'];
			if (!empty($conf['session']))
			{
				$session = $conf['session'];
			}
			
			$confNow = EnActivity::getConfByName(ActivityName::WORLDCARNIVAL);
			$sessionNow = $confNow['data']['id'];
			if (!empty($confNow['data']['session'])) 
			{
				$sessionNow = $confNow['data']['session'];
			}
			
			if ($session != $sessionNow)
			{
				throw new ConfigException('diff session, session[%d], sessionNow[%d], cur session not finished', $session, $sessionNow);
			}
			if ($startTime < $confNow['start_time'])
			{
				throw new ConfigException('start time[%s] earlier than cur start time[%s]', strftime('%Y%m%d %H:%M:%S', $startTime), strftime('%Y%m%d %H:%M:%S', $confNow['start_time']));
			}
		}
		
		return $conf;
	}
}

/*$csvFile = './script/kuafu_babeltimechallenge.csv';
$file = fopen($csvFile, 'r');
if (FALSE == $file)
{
	echo $argv[1] . "{$csvFile} open failed! exit!\n";
	exit;
}

$arrCsv = array();
fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
		break;
	$arrCsv[] = $data;
}

$ret = EnWorldCarnival::readWorldCarnivalCsv($arrCsv);
var_dump($ret);*/

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */