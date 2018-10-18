<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixCityWarTimer.php 140235 2014-11-17 05:46:04Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixCityWarTimer.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-17 05:46:04 +0000 (Mon, 17 Nov 2014) $
 * @version $Revision: 140235 $
 * @brief 
 *  
 **/
class FixCityWarTimer extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$cityId = intval($arrOption[1]);
		$cityInfo = CityWarDao::selectCity($cityId);
		$signupEndTimer = $cityInfo[CityWarDef::SIGNUP_END_TIMER];
		if (!empty($signupEndTimer)) 
		{
			$timerInfo = TimerDAO::getTask($signupEndTimer, array('execute_time'));
			if ($timerInfo['execute_time'] < Util::getTime() - CityWarConf::ROUND_DURATION) 
			{
				printf("city:%d signup end timer is invalid:%s\n", $cityId, strftime("%Y%m%d %H:%M:%S", $timerInfo['execute_time']));
				if ($fix) 
				{
					$arrField = array(CityWarDef::SIGNUP_END_TIMER => 0);
					CityWarDao::updateCity($cityId, $arrField);
				}
			}
		}
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */