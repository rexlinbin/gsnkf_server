<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixCityWarGuild.php 197760 2015-09-10 05:22:23Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixCityWarGuild.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-10 05:22:23 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197760 $
 * @brief 
 *  
 **/
class FixCityWarGuild extends BaseScript
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
		if ($cityInfo[CityWarDef::LAST_GID] == 0 && $cityInfo[CityWarDef::CURR_GID] > 0) 
		{
			printf("cityId:%d last gid:%d curr gid:%d\n", $cityId, $cityInfo[CityWarDef::LAST_GID], $cityInfo[CityWarDef::CURR_GID]);
			Logger::info("cityId:%d last gid:%d curr gid:%d\n", $cityId, $cityInfo[CityWarDef::LAST_GID], $cityInfo[CityWarDef::CURR_GID]);
			if ($fix) 
			{
				$arrField = array(CityWarDef::LAST_GID => $cityInfo[CityWarDef::CURR_GID]);
				CityWarDao::updateCity($cityId, $arrField);
			}
		}

		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */